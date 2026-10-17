<?php
require __DIR__ . '/../../src/auth.php';
require __DIR__ . '/../../src/sales.php';
require_login();

$pageTitle = 'New sale';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    try {
        $id = sale_create((int) $_SESSION['user_id'], $_POST['qty'] ?? [], $_POST['payment_method'] ?? '');
        flash('Sale completed.');
        header('Location: receipt.php?id=' . $id);
        exit;
    } catch (RuntimeException $e) {
        $error = $e->getMessage();
    }
}

$products = product_list();
$products = array_filter($products, fn($p) => $p['stock_quantity'] > 0);

require __DIR__ . '/../../templates/header.php';
?>
<h1>New sale</h1>
<?php if ($error): ?><div class="errors"><?= e($error) ?></div><?php endif; ?>

<input id="filter" placeholder="Filter products..." class="toolbar">

<form method="post">
  <?= csrf_field() ?>
  <table id="till">
    <tr><th>Product</th><th>Price</th><th>In stock</th><th>Qty</th></tr>
    <?php foreach ($products as $p): ?>
      <tr data-name="<?= e(strtolower($p['name'] . ' ' . $p['sku'])) ?>">
        <td><?= e($p['name']) ?></td>
        <td><?= money($p['price']) ?></td>
        <td><?= (int) $p['stock_quantity'] ?></td>
        <td>
          <input class="qty" type="number" min="0" max="<?= (int) $p['stock_quantity'] ?>"
                 name="qty[<?= (int) $p['id'] ?>]" data-price="<?= e((string) $p['price']) ?>"
                 value="<?= (int) ($_POST['qty'][$p['id']] ?? 0) ?>">
        </td>
      </tr>
    <?php endforeach; ?>
  </table>

  <div class="total-bar">
    <select name="payment_method">
      <option value="cash">Cash</option>
      <option value="mobile_money">Mobile money</option>
      <option value="card">Card</option>
    </select>
    <strong>Total: GH₵ <span id="total">0.00</span></strong>
    <button class="btn">Complete sale</button>
  </div>
</form>

<script>
const qtys = document.querySelectorAll('.qty');
const total = document.getElementById('total');
function recalc() {
  let sum = 0;
  qtys.forEach(q => sum += (parseFloat(q.dataset.price) || 0) * (parseInt(q.value) || 0));
  total.textContent = sum.toFixed(2);
}
qtys.forEach(q => q.addEventListener('input', recalc));
document.getElementById('filter').addEventListener('input', e => {
  const term = e.target.value.toLowerCase();
  document.querySelectorAll('#till tr[data-name]').forEach(r =>
    r.style.display = r.dataset.name.includes(term) ? '' : 'none');
});
recalc();
</script>
<?php require __DIR__ . '/../../templates/footer.php'; ?>