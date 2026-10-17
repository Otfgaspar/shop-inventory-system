<?php
require __DIR__ . '/../../src/auth.php';
require __DIR__ . '/../../src/sales.php';
require_login();

$sale = sale_find((int) ($_GET['id'] ?? 0));
if (!$sale) { http_response_code(404); exit('Sale not found.'); }
if ($_SESSION['role'] !== 'admin' && (int) $sale['user_id'] !== (int) $_SESSION['user_id']) {
    http_response_code(403);
    exit('Access denied.');
}

$pageTitle = 'Receipt #' . $sale['id'];
$lines = sale_lines((int) $sale['id']);
require __DIR__ . '/../../templates/header.php';
?>
<div class="receipt">
  <h1>Receipt #<?= (int) $sale['id'] ?></h1>
  <p>
    <?= e($sale['created_at']) ?><br>
    Cashier: <?= e($sale['cashier']) ?><br>
    Payment: <?= e(str_replace('_', ' ', $sale['payment_method'])) ?><br>
    Status: <?= e($sale['status']) ?>
  </p>
  <table>
    <tr><th>Item</th><th>Qty</th><th>Price</th><th>Total</th></tr>
    <?php foreach ($lines as $l): ?>
      <tr>
        <td><?= e($l['name']) ?></td>
        <td><?= (int) $l['quantity'] ?></td>
        <td><?= money($l['unit_price']) ?></td>
        <td><?= money($l['line_total']) ?></td>
      </tr>
    <?php endforeach; ?>
    <tr><th colspan="3">Total</th><th><?= money($sale['total_amount']) ?></th></tr>
  </table>
  <p class="no-print">
    <button class="btn" onclick="window.print()">Print</button>
    <a href="new.php">New sale</a> | <a href="index.php">Sales history</a>
  </p>
</div>
<?php require __DIR__ . '/../../templates/footer.php'; ?>