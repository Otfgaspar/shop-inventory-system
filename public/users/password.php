<?php
require __DIR__ . '/../../src/auth.php';
require __DIR__ . '/../../src/users.php';
require_role('admin');

$user = user_find((int) ($_GET['id'] ?? 0));
if (!$user) { http_response_code(404); exit('User not found.'); }

$pageTitle = 'Reset password';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pw = $_POST['password'] ?? '';
    if (strlen($pw) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    } else {
        user_set_password((int) $user['id'], $pw);
        flash('Password updated for ' . $user['username'] . '.');
        header('Location: index.php');
        exit;
    }
}

require __DIR__ . '/../../templates/header.php';
?>
<h1>Reset password: <?= e($user['username']) ?></h1>
<?php if ($errors): ?>
  <div class="errors"><?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?></div>
<?php endif; ?>
<form method="post" class="form">
  <?= csrf_field() ?>
  <label>New password (min 8 characters)</label>
  <input name="password" type="password" required>
  <p><button class="btn">Save</button> <a href="index.php">Cancel</a></p>
</form>
<?php require __DIR__ . '/../../templates/footer.php'; ?>