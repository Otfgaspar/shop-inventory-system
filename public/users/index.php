<?php
require __DIR__ . '/../../src/auth.php';
require __DIR__ . '/../../src/users.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int) ($_POST['id'] ?? 0);
    if ($id === (int) $_SESSION['user_id']) {
        flash('You cannot deactivate your own account.');
    } else {
        user_set_active($id, ($_POST['active'] ?? '') === '1');
        flash('User updated.');
    }
    header('Location: index.php');
    exit;
}

$pageTitle = 'Users';
$users = user_list();

require __DIR__ . '/../../templates/header.php';
?>
<h1>Users</h1>
<p class="toolbar"><a class="btn" href="create.php">+ Add user</a></p>
<table>
  <tr><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th></th></tr>
  <?php foreach ($users as $u): ?>
    <tr>
      <td><?= e($u['username']) ?></td>
      <td><?= e($u['email']) ?></td>
      <td><?= e($u['role']) ?></td>
      <td><?= $u['is_active'] ? 'Active' : '<span class="low">Inactive</span>' ?></td>
      <td>
        <a href="password.php?id=<?= (int) $u['id'] ?>">Reset password</a>
        <?php if ((int) $u['id'] !== (int) $_SESSION['user_id']): ?>
          <form method="post" class="inline">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
            <input type="hidden" name="active" value="<?= $u['is_active'] ? '0' : '1' ?>">
            <button class="link"><?= $u['is_active'] ? 'Deactivate' : 'Activate' ?></button>
          </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
</table>
<?php require __DIR__ . '/../../templates/footer.php'; ?>