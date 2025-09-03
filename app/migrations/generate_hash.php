<?php
// Generate password hash for admin user
$password = 'BTTAdmin2025!';
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
echo "Password: $password\n";
echo "Hash: $hash\n";
