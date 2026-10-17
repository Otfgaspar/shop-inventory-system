<?php
require __DIR__ . '/../../src/auth.php';
require __DIR__ . '/../../src/sales.php';
require_login();

$pageTitle = 'Sales history';
$sales = sales_list($_SESSION['role'] === 'admin' ? null : (int) $_SESSION['user_id']);

require __DIR__ . '/../../templates/header.php';
?>
<h1>Sales history</h1>
<table>
  <tr><th>#</th><th>Date</th><th>Cashier</th><th>Payment</th><th>Total</th><th>Status</th><th></th></tr>
  <?php foreach ($sales as $s): ?>
    <tr>
      <td><?= (int) $s['id'] ?></td>
      <td><?= e($s['created_at']) ?></td>
      <td><?= e($s['cashier']) ?></td>
      <td><?= e(str_replace('_', ' ', $s['payment_method'])) ?></td>
      <td><?= money($s['total_amount']) ?></td>
      <td><?= e($s['status']) ?></td>
      <td><a href="receipt.php?id=<?= (int) $s['id'] ?>">View</a></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$sales): ?><tr><td colspan="7">No sales yet.</td></tr><?php endif; ?>
</table>
<?php require __DIR__ . '/../../templates/footer.php'; ?>