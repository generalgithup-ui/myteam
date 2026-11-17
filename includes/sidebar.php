<aside class="sidebar" id="appSidebar">
  <a class="sidebar-brand" href="<?= e(app_url('/dashboard.php')) ?>"><span class="brand-mark">T</span><span>TEAM <small>Assignment M5</small></span></a>
  <div class="sidebar-label">WORKSPACE</div>
  <nav class="sidebar-nav">
    <a href="<?= e(app_url('/dashboard.php')) ?>" class="<?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>"><i class="bi bi-grid-1x2"></i> Dashboard</a>
    <a href="<?= e(app_url('/admin/students.php')) ?>" class="<?= basename($_SERVER['PHP_SELF']) === 'students.php' ? 'active' : '' ?>"><i class="bi bi-people"></i> Students</a>
    <a href="<?= e(app_url('/admin/subjects.php')) ?>" class="<?= basename($_SERVER['PHP_SELF']) === 'subjects.php' ? 'active' : '' ?>"><i class="bi bi-book"></i> Subjects</a>
    <a href="<?= e(app_url('/admin/teams.php')) ?>" class="<?= basename($_SERVER['PHP_SELF']) === 'teams.php' ? 'active' : '' ?>"><i class="bi bi-diagram-3"></i> Teams</a>
    <a href="<?= e(app_url('/admin/assignments.php')) ?>" class="<?= basename($_SERVER['PHP_SELF']) === 'assignments.php' ? 'active' : '' ?>"><i class="bi bi-clipboard-check"></i> Assignments</a>
  </nav>
  <div class="sidebar-label mt-4">SYSTEM</div>
  <nav class="sidebar-nav">
    <a href="<?= e(app_url('/admin/settings.php')) ?>"><i class="bi bi-gear"></i> Settings</a>
    <a href="<?= e(app_url('/student/index.php')) ?>"><i class="bi bi-box-arrow-up-right"></i> Student portal</a>
  </nav>
  <div class="sidebar-bottom">
    <div class="sidebar-user"><span class="user-avatar"><?= e(strtoupper(substr(current_admin()['username'] ?? 'A', 0, 1))) ?></span><span><?= e(current_admin()['username'] ?? 'Admin') ?><small>Administrator</small></span></div>
    <form method="post" action="<?= e(app_url('/logout.php')) ?>"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button class="logout-link" type="submit"><i class="bi bi-box-arrow-right"></i> Sign out</button></form>
  </div>
</aside>
