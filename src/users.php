<?php
require_once __DIR__ . '/db.php';

function user_list(): array
{
    return db()->query(
        "SELECT id, username, email, role, is_active, created_at FROM users ORDER BY username"
    )->fetchAll();
}

function user_find(int $id): ?array
{
    $stmt = db()->prepare("SELECT id, username, email, role, is_active FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function user_input(array $p): array
{
    $d = [
        'username' => trim($p['username'] ?? ''),
        'email'    => trim($p['email'] ?? ''),
        'password' => $p['password'] ?? '',
        'role'     => $p['role'] ?? 'cashier',
    ];
    $err = [];
    if (strlen($d['username']) < 3 || strlen($d['username']) > 50) $err[] = 'Username must be 3 to 50 characters.';
    if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL))           $err[] = 'Enter a valid email.';
    if (strlen($d['password']) < 8)                                $err[] = 'Password must be at least 8 characters.';
    if (!in_array($d['role'], ['admin', 'cashier'], true))         $err[] = 'Invalid role.';
    return [$d, $err];
}

function user_create(array $d): void
{
    db()->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)")
        ->execute([$d['username'], $d['email'], password_hash($d['password'], PASSWORD_DEFAULT), $d['role']]);
}

function user_set_active(int $id, bool $active): void
{
    db()->prepare("UPDATE users SET is_active = ? WHERE id = ?")->execute([(int) $active, $id]);
}

function user_set_password(int $id, string $password): void
{
    db()->prepare("UPDATE users SET password_hash = ? WHERE id = ?")
        ->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
}