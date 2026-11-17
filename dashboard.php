<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_admin();
$pageTitle = 'Dashboard';
$isAdminPage = true;
require __DIR__ . '/includes/header.php';
?>
<section class="page-intro"><div><p class="eyebrow">OVERVIEW</p><h1>Good <?= date('H') < 12 ? 'morning' : (date('H') < 18 ? 'afternoon' : 'evening') ?>, <?= e(current_admin()['username']) ?></h1><p class="text-secondary mb-0">Here is your student and team assignment summary.</p></div><a class="btn btn-primary" href="<?= e(app_url('/admin/teams.php')) ?>"><i class="bi bi-plus-lg me-2"></i>Create a team</a></section>
<div class="stats-grid" id="dashboardStats">
  <?php foreach ([['students','Total students','bi-people','blue'],['subjects','Subjects','bi-book','violet'],['teams','Total teams','bi-diagram-3','orange'],['assigned','Students assigned','bi-person-check','green'],['unassigned','Unassigned students','bi-person-dash','slate']] as [$key,$label,$icon,$color]): ?>
  <article class="stat-card"><div class="stat-icon <?= $color ?>"><i class="bi <?= $icon ?>"></i></div><div class="stat-label"><?= $label ?></div><div class="stat-value" data-stat="<?= $key ?>">—</div><div class="stat-foot" data-foot="<?= $key ?>">Across all subjects</div></article>
  <?php endforeach; ?>
</div>
<div class="dashboard-grid">
  <section class="panel-card"><div class="panel-heading"><div><h2>Subject overview</h2><p>Team coverage across all six subjects</p></div><a href="<?= e(app_url('/admin/subjects.php')) ?>">View subjects <i class="bi bi-arrow-right"></i></a></div><div id="subjectOverview" class="subject-overview"><div class="loading-state">Loading subject statistics…</div></div></section>
  <section class="panel-card activity-panel"><div class="panel-heading"><div><h2>Recent activity</h2><p>Latest changes by administrators</p></div></div><div id="activityList" class="activity-list"><div class="loading-state">Loading activity…</div></div></section>
</div>
<div class="d-flex justify-content-between align-items-center mt-4"><div><h2 class="section-title">Quick actions</h2><p class="text-secondary small">Manage your roster and assignments</p></div></div>
<div class="quick-actions">
  <a href="<?= e(app_url('/admin/students.php')) ?>"><span class="quick-icon"><i class="bi bi-person-plus"></i></span><span><b>Manage students</b><small>Add, import, or update your roster</small></span><i class="bi bi-arrow-up-right ms-auto"></i></a>
  <a href="<?= e(app_url('/admin/teams.php')) ?>"><span class="quick-icon"><i class="bi bi-diagram-3"></i></span><span><b>Organize teams</b><small>Assign students by subject</small></span><i class="bi bi-arrow-up-right ms-auto"></i></a>
  <a href="<?= e(app_url('/student/index.php')) ?>"><span class="quick-icon"><i class="bi bi-globe2"></i></span><span><b>Open student portal</b><small>Preview public team information</small></span><i class="bi bi-arrow-up-right ms-auto"></i></a>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
