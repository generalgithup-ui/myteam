<?php
require_once __DIR__ . '/includes/bootstrap.php';

if ((int)db()->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0) {
    header('Location: ' . app_url('/login.php'));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    if (!preg_match('/^[A-Za-z0-9._-]{3,80}$/', $username)) {
        $error = 'Username must be 3–80 characters and use letters, numbers, dots, underscores, or hyphens.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ((int)db()->query("SELECT GET_LOCK('team_assignment_m5_initial_setup', 5)")->fetchColumn() !== 1) {
        $error = 'Setup is currently busy. Refresh the page and try again.';
    } else {
        try {
            if ((int)db()->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0) {
                header('Location: ' . app_url('/login.php'));
                exit;
            }
            $statement = db()->prepare('INSERT INTO admins (username, password_hash, must_change_password) VALUES (?, ?, 1)');
            $statement->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
        } catch (PDOException $exception) {
            error_log('Initial admin setup failed: ' . $exception->getMessage());
            $error = 'Could not create the administrator. Confirm that setup has not already been completed.';
        } finally {
            db()->query("SELECT RELEASE_LOCK('team_assignment_m5_initial_setup')");
        }
        if ($error === '') {
            header('Location: ' . app_url('/login.php?setup=complete'));
            exit;
        }
    }
}
$pageTitle = 'Initial setup';
require __DIR__ . '/includes/header.php';
?>
<main class="auth-page">
  <div class="auth-card">
    <a class="navbar-brand fw-bold" href="<?= e(app_url('/')) ?>"><span class="brand-mark me-2">T</span>TEAM Assignment <span class="text-primary">M5</span></a>
    <div class="mt-4"><div class="auth-icon"><i class="bi bi-shield-lock"></i></div><h1 class="h3 mt-3">Create your administrator</h1><p class="text-secondary">This one-time setup is available only while there are no admin accounts.</p></div>
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <label class="form-label" for="username">Username</label><input class="form-control mb-3" id="username" name="username" value="<?= e($_POST['username'] ?? 'admin') ?>" required minlength="3" maxlength="80" autocomplete="username">
      <label class="form-label" for="password">Password</label><input class="form-control mb-2" id="password" name="password" value="<?= e($_POST['password'] ?? 'admin123') ?>" required minlength="8" autocomplete="new-password">
      <div class="form-text mb-3">Development default: <strong>admin / admin123</strong>. You will be required to change this password at first sign-in.</div>
      <button class="btn btn-primary w-100" type="submit">Create admin account</button>
    </form>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
