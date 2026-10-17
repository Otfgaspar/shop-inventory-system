<?php
require __DIR__ . '/../../src/auth.php';
require __DIR__ . '/../../src/products.php';
require_role('admin');

$pageTitle = 'Add product';
$isNew  = true;
$errors = [];
$p = ['category_id' => '', 'name' => '', 'sku' => '', 'price' => '',
      'cost_price' => '', 'stock_quantity' => 0, 'reorder_level' => 5];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    [$p, $errors] = product_input($_POST);
    if (!$errors) {
        try {
            product_create($p, (int) $_SESSION['user_id']);
            flash('Product added.');
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') $errors[] = 'That SKU already exists.';
            else throw $e;
        }
    }
}

require __DIR__ . '/../../templates/header.php';
echo '<h1>Add product</h1>';
require __DIR__ . '/_form.php';
require __DIR__ . '/../../templates/footer.php';