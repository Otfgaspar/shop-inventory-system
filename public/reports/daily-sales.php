<?php
require __DIR__ . '/../../src/auth.php';
require_role('admin');

$pageTitle = 'Daily sales';
$db = db();

// Validate the chosen date, fall back to today
$date = $_GET['date'] ?? date('Y-m-d');
$d = DateTime::createFromFormat('Y-m-d', $date);
if (!$d || $d->format('Y-m-d') !== $date) {
    $date = date('Y-m-d');
}
$start = $date . ' 00:00:00';
$end   = date('Y-m-d', strtotime($date . ' +1 day')) . ' 00:00:00';

// Totals for the day
$stmt = $db->prepare(
    "SELECT COUNT(*) AS n, COALESCE(SUM(total_amount), 0) AS revenue
     FROM sales WHERE status = 'completed' AND created_at >= ? AND created_at < ?"
);
$stmt->execute([$start, $end]);
$totals = $stmt->fetch();

// By payment method
$stmt = $db->prepare(
    "SELECT payment_method, COUNT(*) AS n, SUM(total_amount) AS revenue
     FROM sales WHERE status = 'completed' AND created_at >= ? AND created_at < ?
     GROUP BY payment_method ORDER BY revenue DESC"
);
$stmt->execute([$start, $end]);
$byMethod = $stmt->fetchAll();

// Products sold that day
$stmt = $db->prepare(
    "SELECT p.name, SUM(si.quantity) AS units, SUM(si.quantity * si.unit_price) AS revenue
     FROM sale_items si
     JOIN sales s ON s.id = si.sale_id
     JOIN products p ON p.id = si.product_id
     WHERE s.status = 'completed' AND s.created_at >= ? AND s.created_at < ?
     GROUP BY p.id, p.name ORDER BY units DESC"
);
$stmt->execute([$start, $end]);
$sold = $stmt->fetchAll();

// Last 14 days from the view
$recent = $db->query("SELECT * FROM v_daily_sales ORDER BY sale_date DESC LIMIT 14")->fetchAll();

require __DIR__ . '/../../templates/header.php';
require __DIR__ . '/_tabs.php';
?>
<h1>Daily sales</h1>
<form method="get" class="toolbar">
  <input type="date" name="date" value="<?= e($date) ?>" max="<?= date('Y-m-d') ?>">
  <button class="btn">Show</button>
</form>

<div class="cards">
  <div class="card"><h3>Revenue</h3><div class="big"><?= money($totals['revenue']) ?></div></div>
  <div class="card"><h3>Transactions</h3><div class="big"><?= (int) $totals['n'] ?></div></div>
</div>

<h2>By payment method</h2>
<table>
  <tr><th>Method</th><th>Sales</th><th>Revenue</th></tr>
  <?php foreach ($byMethod as $m): ?>
    <tr>
      <td><?= e(str_replace('_', ' ', $m['payment_method'])) ?></td>
      <td><?= (int) $m['n'] ?></td>
      <td><?= money($m['revenue']) ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$byMethod): ?><tr><td colspan="3">No sales on this day.</td></tr><?php endif; ?>
</table>

<h2>Products sold</h2>
<table>
  <tr><th>Product</th><th>Units</th><th>Revenue</th></tr>
  <?php foreach ($sold as $s): ?>
    <tr>
      <td><?= e($s['name']) ?></td>
      <td><?= (int) $s['units'] ?></td>
      <td><?= money($s['revenue']) ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$sold): ?><tr><td colspan="3">Nothing sold.</td></tr><?php endif; ?>
</table>

<h2>Last 14 days</h2>
<table>
  <tr><th>Date</th><th>Transactions</th><th>Revenue</th></tr>
  <?php foreach ($recent as $r): ?>
    <tr>
      <td><a href="?date=<?= e($r['sale_date']) ?>"><?= e($r['sale_date']) ?></a></td>
      <td><?= (int) $r['transactions'] ?></td>
      <td><?= money($r['revenue']) ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$recent): ?><tr><td colspan="3">No sales yet.</td></tr><?php endif; ?>
</table>
<?php require __DIR__ . '/../../templates/footer.php'; ?>