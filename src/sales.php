<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/products.php';

function sale_create(int $userId, array $cart, string $method): int
{
    if (!in_array($method, ['cash', 'card', 'mobile_money'], true)) {
        throw new RuntimeException('Invalid payment method.');
    }
    $cart = array_filter(array_map('intval', $cart), fn($q) => $q > 0);
    if (!$cart) {
        throw new RuntimeException('Add at least one item.');
    }
    ksort($cart); // lock rows in a fixed order to avoid deadlocks

    $db = db();
    $db->beginTransaction();
    try {
        $db->prepare("INSERT INTO sales (user_id, total_amount, payment_method) VALUES (?, 0, ?)")
           ->execute([$userId, $method]);
        $saleId = (int) $db->lastInsertId();

        $get    = $db->prepare("SELECT id, name, price, stock_quantity FROM products
                                WHERE id = ? AND is_active = 1 FOR UPDATE");
        $item   = $db->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, unit_price)
                                VALUES (?, ?, ?, ?)");
        $deduct = $db->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");

        $pesewas = 0; // money as whole pesewas, so no floating-point errors
        foreach ($cart as $pid => $qty) {
            $get->execute([$pid]);
            $p = $get->fetch();
            if (!$p) {
                throw new RuntimeException('A product is no longer available.');
            }
            if ($p['stock_quantity'] < $qty) {
                throw new RuntimeException("Not enough stock for {$p['name']} (only {$p['stock_quantity']} left).");
            }
            $item->execute([$saleId, $pid, $qty, $p['price']]);
            $deduct->execute([$qty, $pid]);
            stock_log($pid, $userId, -$qty, 'sale', null, $saleId);
            $pesewas += (int) round($p['price'] * 100) * $qty;
        }

        $db->prepare("UPDATE sales SET total_amount = ? WHERE id = ?")
           ->execute([number_format($pesewas / 100, 2, '.', ''), $saleId]);
        $db->commit();
        return $saleId;
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

function sale_find(int $id): ?array
{
    $stmt = db()->prepare("SELECT s.*, u.username AS cashier FROM sales s
                           JOIN users u ON u.id = s.user_id WHERE s.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function sale_lines(int $saleId): array
{
    $stmt = db()->prepare("SELECT p.name, si.quantity, si.unit_price,
                                  si.quantity * si.unit_price AS line_total
                           FROM sale_items si JOIN products p ON p.id = si.product_id
                           WHERE si.sale_id = ? ORDER BY p.name");
    $stmt->execute([$saleId]);
    return $stmt->fetchAll();
}

function sales_list(?int $onlyUserId = null): array
{
    $sql = "SELECT s.*, u.username AS cashier FROM sales s JOIN users u ON u.id = s.user_id";
    $args = [];
    if ($onlyUserId !== null) {
        $sql .= " WHERE s.user_id = ?";
        $args[] = $onlyUserId;
    }
    $stmt = db()->prepare($sql . " ORDER BY s.created_at DESC LIMIT 100");
    $stmt->execute($args);
    return $stmt->fetchAll();
}