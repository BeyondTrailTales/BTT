<?php
/**
 * Test configuration values
 */

// Load bootstrap
require_once dirname(__DIR__) . '/app/bootstrap.php';

header('Content-Type: text/plain');

echo "Configuration Values:\n";
echo "=====================\n\n";

echo "BTT_BASE_URL: " . BTT_BASE_URL . "\n";
echo "BASE_URL: " . BASE_URL . "\n";
echo "BTT_PUBLIC_URL: " . BTT_PUBLIC_URL . "\n";
echo "BTT_API_URL: " . BTT_API_URL . "\n";
echo "BASE_PATH: " . BASE_PATH . "\n";
echo "BTT_ROOT: " . BTT_ROOT . "\n\n";

echo "Routing Examples:\n";
echo "=================\n\n";

echo "Dashboard URL: " . BASE_URL . "/dashboard\n";
echo "Login URL: " . BASE_URL . "/public/auth/login.php\n";
echo "Trips URL: " . route_url('trips') . "\n";
echo "Backpacks URL: " . route_url('backpacks') . "\n\n";

echo "Current Request Info:\n";
echo "=====================\n\n";

echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'Not set') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'Not set') . "\n";
echo "PHP_SELF: " . ($_SERVER['PHP_SELF'] ?? 'Not set') . "\n";
?>
