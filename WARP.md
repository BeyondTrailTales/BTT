# WARP.md

This file provides guidance to WARP (warp.dev) when working with code in this repository.

# BeyondTrailTales (BTT) — Development Guide

This repository powers a PHP/XAMPP hiking trip planning application with backpack management, using SQLite as primary storage with optional JSON fallback. The app is ADA compliant and follows mobile-first responsive design principles.

**Repository Root:** `C:\xampp2\htdocs\BTT`  
**Primary Local URL:** `http://localhost/BTT/public/`  
**UI/UX Guide:** `http://localhost/beyondtrailtalesfinal/public/ui-ux-guide.php`

## Quick Start (XAMPP)

1. Start Apache via XAMPP Control Panel (single instance on port 80)
2. Ensure PHP extensions enabled in `php.ini`:
   - `extension=sqlite3`
   - `extension=pdo_sqlite`
3. Verify storage directories exist and are writable:
   ```
   storage/sqlite/
   storage/logs/
   storage/cache/
   storage/json/
   ```
4. Initialize database:
   ```bash
   # Using migration helper (recommended)
   php app\tools\migrate.php up
   
   # Or direct SQLite (if sqlite3 CLI available)
   sqlite3 storage\sqlite\btt.db < app\migrations\001_init.sql
   ```
5. Seed sample data (optional):
   ```bash
   php test\seed.php
   ```
6. Visit: `http://localhost/BTT/public/`

## Essential Commands

### Development Server
```bash
# Primary: Use XAMPP Control Panel (Apache on port 80)
# Fallback (if Apache stopped): 
php -S localhost:8080 -t public

# IMPORTANT: Only run ONE instance to avoid port conflicts
```

### Database Migrations
```bash
# Check migration status
php app\tools\migrate.php status

# Apply pending migrations
php app\tools\migrate.php up

# Reset database (deletes all data!)
php app\tools\migrate.php reset

# Manual SQLite operations
sqlite3 storage\sqlite\btt.db ".tables"
sqlite3 storage\sqlite\btt.db ".schema backpacks"
```

### Testing
```bash
# API Tests
curl -s http://localhost/BTT/api/index.php?route=health
curl -s http://localhost/BTT/api/index.php?route=backpacks
curl -s -X POST http://localhost/BTT/api/index.php?route=backpacks \
  -H "Content-Type: application/json" \
  -d "{\"name\":\"Day Pack\",\"capacity\":30,\"base_weight\":2.5}"

# Seed test data
php test\seed.php

# Browser test pages
http://localhost/BTT/test/
```

## Architecture Overview

### Directory Structure
```
BTT/
├── api/                # REST API backend
│   ├── index.php      # API router/entry point
│   ├── config.php     # API configuration
│   ├── routes/        # Endpoint handlers
│   │   ├── backpacks.php
│   │   ├── trips.php
│   │   ├── gear.php
│   │   └── gamification.php
│   ├── classes/       # Utility classes
│   │   ├── Database.php
│   │   └── Response.php
│   └── schemas/       # JSON schemas
├── public/            # Frontend PHP pages
│   ├── index.php     # Landing page
│   ├── backpacks.php # Backpack management
│   ├── trips.php     # Trip planning
│   └── includes/     # Shared components
├── app/              # Application core
│   ├── config.php    # Main configuration
│   ├── bootstrap.php # App initialization
│   ├── classes/      # Core classes
│   ├── helpers/      # Helper functions
│   └── migrations/   # Database migrations
├── assets/           # Static resources
│   ├── css/         # Stylesheets
│   ├── js/          # JavaScript files
│   └── img/         # Images/uploads
├── storage/         # Data storage
│   ├── sqlite/      # SQLite database
│   ├── json/        # JSON storage (fallback)
│   ├── logs/        # Application logs
│   └── cache/       # Temporary cache
├── test/           # Test files (deletable)
└── vendor/         # Third-party libraries
```

### Database Schema (from 001_init.sql)

**Core Tables:**
- `backpacks` - Backpack configurations (id, name, description, capacity, base_weight, image_url, image_alt, timestamps)
- `gear_items` - Gear inventory (id, name, weight, category, brand, notes, price, created_at)
- `trips` - Trip plans (id, title, location, dates, distance, elevation, difficulty, type, description, backpack_id, completed, favorite, image, timestamps)
- `backpack_gear` - M2M relationship (backpack_id, gear_id, quantity, section)
- `trip_gear` - Trip-specific gear (trip_id, gear_id, quantity)
- `migrations` - Migration tracking (id, filename, executed_at)

**Relationships:**
- Backpacks ↔ Gear Items (many-to-many via backpack_gear)
- Trips → Backpack (optional, foreign key)
- Trips ↔ Gear Items (many-to-many via trip_gear)
- Foreign keys enforced with `PRAGMA foreign_keys = ON`

## Development Guidelines

### PHP Conventions
- **Style:** PSR-12 aligned
- **Typing:** Use `declare(strict_types=1)` where appropriate
- **Error Handling:** Log to `storage/logs/`, display errors only in development
- **Database:** Always use prepared statements (PDO)
- **Sessions:** Secure settings configured in `app/config.php`

### API Patterns
```php
// RESTful endpoints
GET    /api/index.php?route=backpacks       # List all
GET    /api/index.php?route=backpacks&id=1  # Get one
POST   /api/index.php?route=backpacks       # Create
PUT    /api/index.php?route=backpacks&id=1  # Update
DELETE /api/index.php?route=backpacks&id=1  # Delete

// Response format
{
  "success": true,
  "data": {...},
  "error": null
}
```

### Frontend Guidelines
- **Framework:** Vanilla PHP with minimal JavaScript
- **Styling:** Utility-first CSS (Tailwind-inspired)
- **Components:** Reusable PHP includes
- **Accessibility:** WCAG AA compliant, keyboard navigable
- **Responsive:** Mobile-first design
- **Images:** Required alt text for accessibility

### JavaScript Patterns
```javascript
// Pack manager components
- pack-manager-pro.js         # Main backpack UI
- trips-enhanced.js          # Trip planning features
- gamification.js            # Achievement system
- ultimate-pack-manager.js   # Advanced features

// Event handling
document.addEventListener('DOMContentLoaded', () => {
    // Initialize components
});
```

## 🔐 Authentication & Session Management

### Authentication System Overview
The BTT application uses a custom session-based authentication system with SQLite database storage.

**Key Components:**
- `AuthService` (`/app/Services/AuthService.php`) - Handles login, logout, registration
- `DbSessionHandler` (`/app/Services/DbSessionHandler.php`) - Custom SQLite session storage
- `Bootstrap` (`/app/bootstrap.php`) - Session initialization and configuration

### Session Configuration
```php
// Session settings in bootstrap.php
Session Name: BTTSESSID
Cookie Path: /BTT/  (IMPORTANT: Must be /BTT/ not /)
Lifetime: Session cookie (expires on browser close)
HttpOnly: true (prevents JavaScript access)
SameSite: Lax (CSRF protection)
```

### Login Flow
1. User submits form at `/public/auth/login.php`
2. AJAX request to `/api/index.php?route=auth&id=login`
3. AuthService validates credentials and sets session:
   ```php
   $_SESSION['user_id'] = $user['id'];
   $_SESSION['username'] = $user['username'];
   $_SESSION['logged_in'] = true;
   ```
4. Session regenerated for security: `session_regenerate_id(true)`
5. Force session save: `session_write_close()` then `session_start()`
6. JavaScript redirects to `/BTT/dashboard` (not dashboard.php)

### Common Authentication Issues & Fixes

**Issue: Login successful but doesn't redirect**
- **Fix**: Redirect URL should be `/BTT/dashboard` not `/BTT/dashboard.php`

**Issue: Session not persisting after login**
- **Fix**: Set session variables BEFORE `session_regenerate_id()`
- **Fix**: Use `session_write_close()` and `session_start()` to force save
- **Fix**: Ensure cookie path is `/BTT/` not `/`

**Issue: Multiple session cookies created**
- **Fix**: All files must include bootstrap.php first
- **Fix**: Don't start new sessions - bootstrap handles it

### Test Credentials
```
Username: admin     Password: Admin123!
Username: testuser  Password: Test123!
Username: demo      Password: Demo123!
```

### Authentication Testing Tools
- `/test/session-debug.html` - Complete session testing interface
- `/test/test-login.html` - Login flow tester
- `/test/check-auth.php` - Check current auth status
- `/test/session-test.php` - Session operations API

### Protected Pages
```php
// Add to any page requiring authentication
require_auth();  // Redirects to login if not authenticated

// Check authentication without redirect
if (is_authenticated()) {
    // User is logged in
}
```

### Session Debugging
```php
// View current session data
echo '<pre>';
print_r($_SESSION);
print_r($_COOKIE);
echo '</pre>';

// Check session in SQLite
sqlite3 storage/sqlite/btt.db "SELECT * FROM sessions WHERE user_id IS NOT NULL;"
```

## Common Development Tasks

### Adding a New API Endpoint
1. Create route handler in `api/routes/feature.php`
2. Register in `api/index.php` router
3. Add data model in `app/models/` if needed
4. Create migration if schema changes required
5. Test with curl, add examples to `/test/`
6. Update API documentation

### Creating Database Migration
1. Create `app/migrations/NNN_description.sql`
2. Include schema changes and migration record:
   ```sql
   -- Schema changes here
   CREATE TABLE ...;
   
   -- Required: Register migration
   INSERT INTO migrations (filename) VALUES ('NNN_description.sql');
   ```
3. Apply: `php app\tools\migrate.php up`
4. Update affected models and routes

### Adding Frontend Page
1. Create PHP page in `public/`
2. Include header/footer from `includes/`
3. Follow design system (`assets/css/main.css`)
4. Ensure ADA compliance:
   - Semantic HTML
   - ARIA labels
   - Keyboard navigation
   - Color contrast
5. Test on mobile viewports

### Testing Workflow
1. Keep all test files in `/test/` directory
2. Create test pages for new features
3. Add API test scripts with curl examples
4. Before production: Delete entire `/test/` folder
5. Use browser DevTools for debugging

## Troubleshooting

### Common Issues

**500 Error / White Screen**
- Check `storage/logs/app.log`
- Enable error display: `ini_set('display_errors', 1)`
- Verify file permissions on `storage/` directories

**Database Locked**
- Close any open SQLite connections
- Check for hung PHP processes
- Restart Apache if needed

**SQLite Driver Missing**
```bash
# Check if enabled
php -m | findstr sqlite

# Enable in php.ini
extension=sqlite3
extension=pdo_sqlite

# Restart Apache
```

**Foreign Key Constraint Failed**
- Ensure `PRAGMA foreign_keys = ON` after connection
- Check referential integrity before deletes
- Use CASCADE or SET NULL appropriately

**Session Issues**
- Verify `session_save_path` is writable
- Check session cookie settings
- Clear browser cookies

## Performance Optimization

### Database
- Indexes created on foreign keys and common lookups
- Use transactions for bulk operations
- Keep long-running queries minimal

### Frontend
- Minimize JavaScript bundles
- Use lazy loading for images
- Cache static assets appropriately
- Optimize database queries with proper indexes

### Debugging
```php
// Enable debug mode
define('BTT_DEBUG', true);

// Log custom messages
btt_log('Debug message', 'DEBUG');

// Profile database queries
$start = microtime(true);
// ... query ...
$time = microtime(true) - $start;
btt_log("Query took: {$time}s", 'PERFORMANCE');
```

## Production Deployment Checklist

1. **Environment**
   - Set `BTT_ENV` to 'production' in `app/config.php`
   - Disable error display
   - Enable error logging

2. **Security**
   - Remove `/test/` directory
   - Set proper file permissions
   - Enable HTTPS if deployed online
   - Add authentication if needed

3. **Database**
   - Backup existing data
   - Run all migrations
   - Verify foreign key constraints
   - Test rollback procedures

4. **Performance**
   - Enable OpCache
   - Minify CSS/JS assets
   - Configure proper caching headers
   - Optimize images

## Team Conventions (from Rules)

### Critical Requirements
- **ADA Compliant:** All UI must meet accessibility standards
- **Single Instance:** Only run one local server instance (XAMPP on port 80)
- **Responsive Design:** Mobile-first approach required
- **Test Organization:** All tests in `/test/` for easy cleanup
- **MCP Context 7:** Use for code quality and debugging
- **UI/UX Guide:** Reference `http://localhost/beyondtrailtalesfinal/public/ui-ux-guide.php`

### Workflow Discipline
- Complete TODO lists before moving to next task
- Write cohesive code with whole project in mind
- Implement tests and move them to `/test/` folder
- Prefer MCP solutions over ad-hoc fixes
- Follow established patterns consistently

## Migration Helper Script

If not present, create `app/tools/migrate.php`:

```php
<?php
declare(strict_types=1);

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
