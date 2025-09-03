<?php
declare(strict_types=1);

/**
 * Database Migration Tool for BeyondTrailTales
 * 
 * Usage:
 *   php app\tools\migrate.php status  - Show migration status
 *   php app\tools\migrate.php up      - Apply pending migrations
 *   php app\tools\migrate.php reset   - Delete database file
 */

$projectRoot = dirname(dirname(__DIR__));
$dbPath = $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'sqlite' . DIRECTORY_SEPARATOR . 'btt.db';
$migrationsDir = $projectRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'migrations';

// Ensure directories exist
$dbDir = dirname($dbPath);
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0777, true);
}

// Connect to database
try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON');
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . PHP_EOL);
}

// Helper functions
function out($msg) { echo $msg . PHP_EOL; }
function err($msg) { fwrite(STDERR, '[ERROR] ' . $msg . PHP_EOL); }

function migrationsTableExists(PDO $pdo): bool {
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='migrations'");
    return (bool) $stmt->fetchColumn();
}

function getExecutedMigrations(PDO $pdo): array {
    if (!migrationsTableExists($pdo)) return [];
    $stmt = $pdo->query("SELECT filename FROM migrations ORDER BY filename");
    return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
}

function getPendingMigrations(string $dir, array $executed): array {
    $files = glob($dir . DIRECTORY_SEPARATOR . '*.sql');
    if (!$files) return [];
    
    $pending = [];
    foreach ($files as $file) {
        $filename = basename($file);
        if (!in_array($filename, $executed, true)) {
            $pending[] = $file;
        }
    }
    
    natsort($pending);
    return array_values($pending);
}

// Parse command
$command = $argv[1] ?? 'status';

switch ($command) {
    case 'status':
        $executed = getExecutedMigrations($pdo);
        $allFiles = glob($migrationsDir . DIRECTORY_SEPARATOR . '*.sql');
        
        out('Database: ' . $dbPath);
        out('Executed migrations: ' . count($executed));
        
        if ($allFiles) {
            $pending = getPendingMigrations($migrationsDir, $executed);
            out('Pending migrations: ' . count($pending));
            out('');
            
            foreach ($allFiles as $file) {
                $filename = basename($file);
                $status = in_array($filename, $executed, true) ? '[✓]' : '[ ]';
                out($status . ' ' . $filename);
            }
        } else {
            out('No migration files found in: ' . $migrationsDir);
        }
        break;
        
    case 'up':
        $executed = getExecutedMigrations($pdo);
        $pending = getPendingMigrations($migrationsDir, $executed);
        
        if (empty($pending)) {
            out('No pending migrations.');
            break;
        }
        
        out('Found ' . count($pending) . ' pending migration(s).');
        
        foreach ($pending as $file) {
            $filename = basename($file);
            out('Applying: ' . $filename);
            
            $sql = file_get_contents($file);
            if ($sql === false) {
                err('Failed to read file: ' . $file);
                exit(1);
            }
            
            $pdo->beginTransaction();
            try {
                $pdo->exec($sql);
                $pdo->commit();
                out('  ✓ Applied successfully');
            } catch (PDOException $e) {
                $pdo->rollBack();
                err('Migration failed: ' . $e->getMessage());
                exit(1);
            }
        }
        
        out('All migrations applied successfully.');
        break;
        
    case 'reset':
        if (file_exists($dbPath)) {
            unlink($dbPath);
            out('Database deleted: ' . $dbPath);
        } else {
            out('No database file to delete.');
        }
        break;
        
    default:
        out('Usage: php app\tools\migrate.php [command]');
        out('');
        out('Commands:');
        out('  status  - Show migration status');
        out('  up      - Apply pending migrations');
        out('  reset   - Delete database file');
        exit(0);
}
