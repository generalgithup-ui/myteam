<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (current_admin()) {
    header('Location: ' . app_url('/dashboard.php'));
    exit;
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $ipHash = current_ip_hash();
    $attempt = db()->prepare('SELECT attempts, first_attempt >= NOW() - INTERVAL 15 MINUTE AS in_window FROM login_attempts WHERE ip_hash=?');
    $attempt->execute([$ipHash]);
    $rate = $attempt->fetch();
    $locked = $rate && (int)$rate['attempts'] >= 10 && (bool)$rate['in_window'];
    $statement = db()->prepare('SELECT id, username, password_hash, must_change_password FROM admins WHERE username = ? LIMIT 1');
    $statement->execute([$username]);
    $admin = $statement->fetch();
    if (!$locked && $admin && password_verify($password, $admin['password_hash'])) {
        db()->prepare('DELETE FROM login_attempts WHERE ip_hash=?')->execute([$ipHash]);
        session_regenerate_id(true);
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['admin'] = [
            'id' => (int)$admin['id'],
            'username' => $admin['username'],
            'must_change_password' => (bool)$admin['must_change_password'],
        ];
        header('Location: ' . app_url($admin['must_change_password'] ? '/admin/settings.php?change_password=1' : '/dashboard.php'));
        exit;
    }
    if (!$locked) {
        db()->prepare(
            'INSERT INTO login_attempts (ip_hash, attempts, first_attempt) VALUES (?, 1, NOW())
             ON DUPLICATE KEY UPDATE attempts=IF(first_attempt < NOW() - INTERVAL 15 MINUTE, 1, attempts + 1),
                                     first_attempt=IF(first_attempt < NOW() - INTERVAL 15 MINUTE, NOW(), first_attempt)'
        )->execute([$ipHash]);
    }
    db()->exec('DELETE FROM login_attempts WHERE first_attempt < NOW() - INTERVAL 1 DAY');
    $error = $locked ? 'Too many sign-in attempts. Please wait 15 minutes and try again.' : 'The username or password is incorrect.';
}
$pageTitle = 'Admin login';
require __DIR__ . '/includes/header.php';
?>
<main class="auth-page">
  <div class="auth-card">
    <a class="navbar-brand fw-bold" href="<?= e(app_url('/')) ?>"><span class="brand-mark me-2">T</span>TEAM Assignment <span class="text-primary">M5</span></a>
    <div class="mt-4"><div class="auth-icon"><i class="bi bi-person-lock"></i></div><h1 class="h3 mt-3">Welcome back</h1><p class="text-secondary">Sign in to manage students and teams.</p></div>
    <?php if (isset($_GET['setup'])): ?><div class="alert alert-success">Admin account created. Sign in with your new credentials.</div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <label class="form-label" for="username">Username</label><input class="form-control mb-3" id="username" name="username" required autocomplete="username" autofocus>
      <label class="form-label" for="password">Password</label><input class="form-control mb-4" id="password" name="password" type="password" required autocomplete="current-password">
      <button class="btn btn-primary w-100" type="submit">Sign in <i class="bi bi-arrow-right ms-1"></i></button>
    </form>
    <p class="text-center small text-secondary mt-4 mb-0">Student? <a href="<?= e(app_url('/student/index.php')) ?>">Browse the public portal</a></p>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
