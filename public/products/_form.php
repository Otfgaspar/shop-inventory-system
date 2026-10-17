<?php if ($errors): ?>
  <div class="errors"><?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?></div>
<?php endif; ?>
<form method="post" class="form">
  <?= csrf_field() ?>
  <label>Category</label>
  <select name="category_id" required>
    <option value="">Choose...</option>
    <?php foreach (categories() as $c): ?>
      <option value="<?= (int) $c['id'] ?>" <?= (int) $p['category_id'] === (int) $c['id'] ? 'selected' : '' ?>>
        <?= e($c['name']) ?>
      </option>
    <?php endforeach; ?>
  </select>
  <label>Name</label>
  <input name="name" value="<?= e($p['name']) ?>" required>
  <label>SKU / barcode</label>
  <input name="sku" value="<?= e($p['sku']) ?>" required>
  <label>Selling price</label>
  <input name="price" type="number" step="0.01" min="0" value="<?= e((string) $p['price']) ?>" required>
  <label>Cost price (optional)</label>
  <input name="cost_price" type="number" step="0.01" min="0" value="<?= e((string) $p['cost_price']) ?>">
  <?php if ($isNew): ?>
    <label>Opening stock</label>
    <input name="stock_quantity" type="number" min="0" value="<?= (int) $p['stock_quantity'] ?>">
  <?php endif; ?>
  <label>Reorder level</label>
  <input name="reorder_level" type="number" min="0" value="<?= (int) $p['reorder_level'] ?>">
  <p><button class="btn">Save</button> <a href="index.php">Cancel</a></p>
</form>