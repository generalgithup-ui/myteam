<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$admin = require_admin(true);
$error = '';
$success = '';
$mustChange = !empty($admin['must_change_password']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $current = (string)($_POST['current_password'] ?? '');
    $new = (string)($_POST['new_password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');
    if (!$mustChange) {
        $check = db()->prepare('SELECT password_hash FROM admins WHERE id=?');
        $check->execute([$admin['id']]);
        if (!password_verify($current, (string)$check->fetchColumn())) {
            $error = 'Current password is incorrect.';
        }
    }
    if ($error === '' && strlen($new) < 8) {
        $error = 'New password must be at least 8 characters.';
    } elseif ($error === '' && $new !== $confirm) {
        $error = 'New password and confirmation do not match.';
    } elseif ($error === '' && !$mustChange && hash_equals($current, $new)) {
        $error = 'Choose a password different from your current password.';
    }
    if ($error === '') {
        $update = db()->prepare('UPDATE admins SET password_hash=?, must_change_password=0 WHERE id=?');
        $update->execute([password_hash($new, PASSWORD_DEFAULT), $admin['id']]);
        $_SESSION['admin']['must_change_password'] = false;
        $mustChange = false;
        $success = 'Password updated successfully.';
    }
}
$pageTitle = 'Settings';
$isAdminPage = true;
require __DIR__ . '/../includes/header.php';
?>
<section class="page-intro"><div><p class="eyebrow">ACCOUNT & SECURITY</p><h1>Settings</h1><p class="text-secondary mb-0">Manage the administrator account and security settings.</p></div></section>
<?php if ($mustChange): ?><div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i><strong>Password change required.</strong> Update the default password before using the admin workspace.</div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?><?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
<section class="panel-card settings-card"><div class="panel-heading"><div><h2>Administrator account</h2><p>Signed in as <strong><?= e($admin['username']) ?></strong></p></div><span class="settings-badge"><i class="bi bi-shield-check me-1"></i> Password protected</span></div>
<form method="post" class="settings-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<?php if (!$mustChange): ?><div class="mb-3"><label class="form-label">Current password</label><input class="form-control" name="current_password" type="password" required autocomplete="current-password"></div><?php endif; ?>
<div class="mb-3"><label class="form-label">New password</label><input class="form-control" name="new_password" type="password" required minlength="8" autocomplete="new-password"><div class="form-text">Use at least 8 characters.</div></div>
<div class="mb-4"><label class="form-label">Confirm new password</label><input class="form-control" name="confirm_password" type="password" required minlength="8" autocomplete="new-password"></div>
<button class="btn btn-primary" type="submit">Update password</button></form></section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
