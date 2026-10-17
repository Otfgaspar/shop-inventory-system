<?php
require __DIR__ . '/../../src/auth.php';
require_login();

$pageTitle = 'Low stock';
$items   = db()->query("SELECT * FROM v_low_stock ORDER BY stock_quantity, name")->fetchAll();
$isAdmin = $_SESSION['role'] === 'admin';

require __DIR__ . '/../../templates/header.php';
require __DIR__ . '/_tabs.php';
?>
<h1>Low stock</h1>
<table>
  <tr><th>Product</th><th>SKU</th><th>Category</th><th>In stock</th><th>Reorder at</th><th></th></tr>
  <?php foreach ($items as $i): ?>
    <tr>
      <td><?= e($i['name']) ?></td>
      <td><?= e($i['sku']) ?></td>
      <td><?= e($i['category']) ?></td>
      <td class="low"><?= (int) $i['stock_quantity'] ?></td>
      <td><?= (int) $i['reorder_level'] ?></td>
      <td>
        <?php if ($isAdmin): ?>
          <a href="<?= BASE_URL ?>/products/edit.php?id=<?= (int) $i['id'] ?>">Restock</a>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$items): ?><tr><td colspan="6">Everything is well stocked.</td></tr><?php endif; ?>
</table>
<?php require __DIR__ . '/../../templates/footer.php'; ?>