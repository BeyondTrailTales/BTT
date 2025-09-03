<?php
require_once dirname(__DIR__) . '/api/database.php';

$db = Database::getInstance();
$users = $db->fetchAll('SELECT id, email, username FROM users');

echo "Current users in database:\n";
echo "==========================\n\n";

foreach($users as $user) {
    echo "ID: {$user['id']}\n";
    echo "Email: {$user['email']}\n";
    echo "Username: {$user['username']}\n";
    echo "---\n";
}
