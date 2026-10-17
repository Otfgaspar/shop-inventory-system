<?php
require __DIR__ . '/../src/db.php';

[$script, $username, $email, $password] = $argv + [null, null, null, null];
if (!$password) {
    exit("Usage: php create_admin.php <username> <email> <password>\n");
}

$stmt = db()->prepare(
    "INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'admin')"
);
$stmt->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT)]);
echo "Admin created.\n";