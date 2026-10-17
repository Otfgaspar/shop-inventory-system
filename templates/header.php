<?php
require_once __DIR__ . '/../src/helpers.php';
$pageTitle = $pageTitle ?? 'Shop Inventory';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?></title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<nav class="navbar">
  <a class="brand" href="<?= BASE_URL ?>/dashboard.php">ShopTrack</a>
  <div class="links">
    <a href="<?= BASE_URL ?>/dashboard.php">Dashboard</a>
    <a href="<?= BASE_URL ?>/sales/new.php">New Sale</a>
    <a href="<?= BASE_URL ?>/products/index.php">Products</a>
    <a href="<?= BASE_URL ?>/reports/low-stock.php">Reports</a>
    <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
      <a href="<?= BASE_URL ?>/users/index.php">Users</a>
    <?php endif; ?>
  </div>
  <div class="user">
    <?= e($_SESSION['username'] ?? '') ?> (<?= e($_SESSION['role'] ?? '') ?>)
    <a href="<?= BASE_URL ?>/logout.php">Log out</a>
  </div>
</nav>
<main class="container">
    <?php if ($msg = flash()): ?><p class="flash"><?= e($msg) ?></p><?php endif; ?>