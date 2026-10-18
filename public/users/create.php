<?php
require __DIR__ . '/../../src/auth.php';
require __DIR__ . '/../../src/users.php';
require_role('admin');

$pageTitle = 'Add user';
$errors = [];
$d = ['username' => '', 'email' => '', 'password' => '', 'role' => 'cashier'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    [$d, $errors] = user_input($_POST);
    if (!$errors) {
        try {
            user_create($d);
            flash('User created.');
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') $errors[] = 'That username or email is already taken.';
            else throw $e;
        }
    }
}

require __DIR__ . '/../../templates/header.php';
?>
<h1>Add user</h1>
<?php if ($errors): ?>
  <div class="errors"><?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?></div>
<?php endif; ?>
<form method="post" class="form">
  <?= csrf_field() ?>
  <label>Username</label>
  <input name="username" value="<?= e($d['username']) ?>" required>
  <label>Email</label>
  <input name="email" type="email" value="<?= e($d['email']) ?>" required>
  <label>Password (min 8 characters)</label>
  <input name="password" type="password" required>
  <label>Role</label>
  <select name="role">
    <option value="cashier" <?= $d['role'] === 'cashier' ? 'selected' : '' ?>>Cashier</option>
    <option value="admin"   <?= $d['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
  </select>
  <p><button class="btn">Create user</button> <a href="index.php">Cancel</a></p>
</form>
<?php require __DIR__ . '/../../templates/footer.php'; ?>