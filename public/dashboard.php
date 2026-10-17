<?php
require __DIR__ . '/../src/auth.php';
require_login();

$pageTitle = 'Dashboard';
$db = db();

$today = $db->query(
    "SELECT COUNT(*) AS n, COALESCE(SUM(total_amount), 0) AS revenue
     FROM sales WHERE status = 'completed' AND DATE(created_at) = CURDATE()"
)->fetch();

$lowStock = $db->query("SELECT COUNT(*) FROM v_low_stock")->fetchColumn();
$products = $db->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetchColumn();

require __DIR__ . '/../templates/header.php';
?>
<h1>Dashboard</h1>
<div class="cards">
  <div class="card"><h3>Today's sales</h3><div class="big"><?= money($today['revenue']) ?></div></div>
  <div class="card"><h3>Transactions today</h3><div class="big"><?= (int) $today['n'] ?></div></div>
  <div class="card"><h3>Active products</h3><div class="big"><?= (int) $products ?></div></div>
  <div class="card warn"><h3>Low-stock items</h3><div class="big"><?= (int) $lowStock ?></div></div>
</div>
<?php require __DIR__ . '/../templates/footer.php'; ?>