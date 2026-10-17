<?php
require __DIR__ . '/../../src/auth.php';
require __DIR__ . '/../../src/products.php';
require_role('admin');

$id = (int) ($_GET['id'] ?? 0);
$product = product_find($id);
if (!$product) { http_response_code(404); exit('Product not found.'); }

$pageTitle = 'Edit product';
$isNew  = false;
$errors = [];
$p = $product;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['action'] ?? '') === 'restock') {
        $qty = (int) ($_POST['qty'] ?? 0);
        if ($qty < 1) {
            $errors[] = 'Restock quantity must be at least 1.';
        } else {
            $note = trim($_POST['note'] ?? '') ?: null;
            product_restock($id, (int) $_SESSION['user_id'], $qty, $note);
            flash("Added $qty units to stock.");
            header("Location: edit.php?id=$id");
            exit;
        }
    } else {
        [$p, $errors] = product_input($_POST);
        $p['stock_quantity'] = $product['stock_quantity'];
        if (!$errors) {
            try {
                product_update($id, $p);
                flash('Product updated.');
                header('Location: index.php');
                exit;
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') $errors[] = 'That SKU already exists.';
                else throw $e;
            }
        }
    }
}

require __DIR__ . '/../../templates/header.php';
?>
<h1>Edit product</h1>
<p>Current stock: <strong><?= (int) $product['stock_quantity'] ?></strong></p>

<form method="post" class="form">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="restock">
  <label>Restock quantity</label>
  <input name="qty" type="number" min="1" required>
  <label>Note (optional)</label>
  <input name="note" placeholder="e.g. Delivery from supplier">
  <p><button class="btn">Add stock</button></p>
</form>
<hr>
<?php
require __DIR__ . '/_form.php';
require __DIR__ . '/../../templates/footer.php';