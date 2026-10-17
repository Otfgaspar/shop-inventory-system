<?php
require __DIR__ . '/../src/auth.php';
require_login();
?>
<h1>Welcome, <?= htmlspecialchars($_SESSION['username']) ?> (<?= $_SESSION['role'] ?>)</h1>
<a href="logout.php">Log out</a>