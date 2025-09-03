<?php
/**
 * Clear migration records to retry
 */

require_once dirname(dirname(__DIR__)) . '/app/config.php';

try {
    $db = new PDO('sqlite:' . BTT_SQLITE_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Delete migration records
    $db->exec("DELETE FROM schema_migrations");
    echo "Cleared migration records.\n";
    
    // Drop tables that were created by previous partial migrations
    $tablesToDrop = [
        'user_activity_log',
        'email_verifications',
        'login_attempts',
        'password_resets',
        'sessions',
        'users',
        'notification_queue',
        'trip_presence',
        'trip_edit_events',
        'trip_collaborators',
        'trip_shares',
        'share_codes',
        'backpack_collaborators'
    ];
    
    foreach ($tablesToDrop as $table) {
        try {
            $db->exec("DROP TABLE IF EXISTS $table");
            echo "Dropped table: $table\n";
        } catch (Exception $e) {
            echo "Could not drop $table: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\nDatabase cleaned. Ready to run migrations again.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
