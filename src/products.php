<?php
require_once __DIR__ . '/db.php';

function product_list(string $search = ''): array
{
    $stmt = db()->prepare(
        "SELECT p.*, c.name AS category FROM products p
         JOIN categories c ON c.id = p.category_id
         WHERE p.is_active = 1 AND (p.name LIKE :q1 OR p.sku LIKE :q2)
         ORDER BY p.name"
    );
    $stmt->execute(['q1' => "%$search%", 'q2' => "%$search%"]);
    return $stmt->fetchAll();
}

function product_find(int $id): ?array
{
    $stmt = db()->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function categories(): array
{
    return db()->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
}

function product_input(array $p): array
{
    $d = [
        'category_id'    => (int) ($p['category_id'] ?? 0),
        'name'           => trim($p['name'] ?? ''),
        'sku'            => trim($p['sku'] ?? ''),
        'price'          => $p['price'] ?? '',
        'cost_price'     => ($p['cost_price'] ?? '') === '' ? null : $p['cost_price'],
        'stock_quantity' => (int) ($p['stock_quantity'] ?? 0),
        'reorder_level'  => (int) ($p['reorder_level'] ?? 5),
    ];
    $err = [];
    if ($d['category_id'] < 1) $err[] = 'Choose a category.';
    if ($d['name'] === '')     $err[] = 'Name is required.';
    if ($d['sku'] === '')      $err[] = 'SKU is required.';
    if (!is_numeric($d['price']) || $d['price'] < 0) $err[] = 'Price must be 0 or more.';
    if ($d['cost_price'] !== null && (!is_numeric($d['cost_price']) || $d['cost_price'] < 0))
        $err[] = 'Cost price must be 0 or more.';
    if ($d['stock_quantity'] < 0 || $d['reorder_level'] < 0)
        $err[] = 'Stock and reorder level cannot be negative.';
    return [$d, $err];
}

function stock_log(int $productId, int $userId, int $qty, string $type, ?string $note = null, ?int $saleId = null): void
{
    db()->prepare(
        "INSERT INTO stock_movements (product_id, user_id, sale_id, change_quantity, movement_type, note)
         VALUES (?, ?, ?, ?, ?, ?)"
    )->execute([$productId, $userId, $saleId, $qty, $type, $note]);
}

function product_create(array $d, int $userId): int
{
    $db = db();
    $db->beginTransaction();
    try {
        $db->prepare(
            "INSERT INTO products (category_id, name, sku, price, cost_price, stock_quantity, reorder_level)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        )->execute([$d['category_id'], $d['name'], $d['sku'], $d['price'],
                    $d['cost_price'], $d['stock_quantity'], $d['reorder_level']]);
        $id = (int) $db->lastInsertId();
        if ($d['stock_quantity'] > 0) {
            stock_log($id, $userId, $d['stock_quantity'], 'restock', 'Opening stock');
        }
        $db->commit();
        return $id;
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

function product_update(int $id, array $d): void
{
    db()->prepare(
        "UPDATE products SET category_id = ?, name = ?, sku = ?, price = ?,
                cost_price = ?, reorder_level = ? WHERE id = ?"
    )->execute([$d['category_id'], $d['name'], $d['sku'], $d['price'],
                $d['cost_price'], $d['reorder_level'], $id]);
}

function product_restock(int $id, int $userId, int $qty, ?string $note): void
{
    $db = db();
    $db->beginTransaction();
    try {
        $db->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?")
           ->execute([$qty, $id]);
        stock_log($id, $userId, $qty, 'restock', $note);
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}