<p class="tabs">
  <a href="<?= BASE_URL ?>/reports/low-stock.php">Low stock</a>
  <?php if ($_SESSION['role'] === 'admin'): ?>
    <a href="<?= BASE_URL ?>/reports/daily-sales.php">Daily sales</a>
  <?php endif; ?>
</p>