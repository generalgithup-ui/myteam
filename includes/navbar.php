<header class="topbar">
  <button class="btn btn-link d-lg-none text-dark p-0 me-3" id="sidebarToggle" aria-label="Toggle navigation"><i class="bi bi-list fs-3"></i></button>
  <div><div class="topbar-eyebrow">TEAM ASSIGNMENT · M5</div><div class="topbar-title"><?= e($pageTitle) ?></div></div>
  <div class="ms-auto d-flex align-items-center gap-3"><span class="d-none d-sm-inline text-secondary small"><?= date('D, M j, Y') ?></span><span class="user-avatar"><?= e(strtoupper(substr(current_admin()['username'] ?? 'A', 0, 1))) ?></span></div>
</header>
