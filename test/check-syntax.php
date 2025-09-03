<?php
/**
 * Check PHP syntax in backpacks.php
 */

// Check for PHP syntax errors
$file = dirname(__DIR__) . '/api/routes/backpacks.php';

// Use PHP's built-in syntax checker
$output = shell_exec("php -l \"$file\" 2>&1");
echo "Syntax check for backpacks.php:\n";
echo $output . "\n";

// Check helper file too
$helperFile = dirname(__DIR__) . '/api/routes/backpacks_helpers.php';
$output = shell_exec("php -l \"$helperFile\" 2>&1");
echo "\nSyntax check for backpacks_helpers.php:\n";
echo $output . "\n";

// Check Database class
$dbFile = dirname(__DIR__) . '/api/classes/Database.php';
$output = shell_exec("php -l \"$dbFile\" 2>&1");
echo "\nSyntax check for Database.php:\n";
echo $output . "\n";

// Check Response class
$responseFile = dirname(__DIR__) . '/api/classes/Response.php';
$output = shell_exec("php -l \"$responseFile\" 2>&1");
echo "\nSyntax check for Response.php:\n";
echo $output . "\n";
