<?php
require __DIR__ . '/../../src/auth.php';
require __DIR__ . '/../../src/products.php';
require_login();

$pageTitle = 'Products';
$search    = trim($_GET['q'] ?? '');
$products  = product_list($search);
$isAdmin   = $_SESSION['role'] === 'admin';

require __DIR__ . '/../../templates/header.php';
?>
<h1>Products</h1>
<form method="get" class="toolbar">
  <input name="q" value="<?= e($search) ?>" placeholder="Search name or SKU">
  <button class="btn">Search</button>
  <?php if ($isAdmin): ?><a class="btn" href="create.php">+ Add product</a><?php endif; ?>
</form>

<table>
  <tr><th>Name</th><th>SKU</th><th>Category</th><th>Price</th><th>Stock</th><th></th></tr>
  <?php foreach ($products as $p): ?>
    <tr>
      <td><?= e($p['name']) ?></td>
      <td><?= e($p['sku']) ?></td>
      <td><?= e($p['category']) ?></td>
      <td><?= money($p['price']) ?></td>
      <td class="<?= $p['stock_quantity'] <= $p['reorder_level'] ? 'low' : '' ?>">
        <?= (int) $p['stock_quantity'] ?>
      </td>
      <td><?php if ($isAdmin): ?><a href="edit.php?id=<?= (int) $p['id'] ?>">Edit</a><?php endif; ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$products): ?><tr><td colspan="6">No products found.</td></tr><?php endif; ?>
</table>
<?php require __DIR__ . '/../../templates/footer.php'; ?>