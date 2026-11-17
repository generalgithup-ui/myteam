<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
$pageTitle = 'Assignments';
$isAdminPage = true;
require __DIR__ . '/../includes/header.php';
?>
<section class="page-intro"><div><p class="eyebrow">ASSIGNMENT TRACKING</p><h1>Assignments</h1><p class="text-secondary mb-0">Review assignment progress by subject and team.</p></div><a class="btn btn-primary" href="<?= e(app_url('/admin/teams.php')) ?>"><i class="bi bi-plus-lg me-2"></i>Manage teams</a></section>
<section class="panel-card"><div class="toolbar"><div class="search-box"><i class="bi bi-search"></i><input id="assignmentSearch" class="form-control" type="search" placeholder="Search title, team, or subject"></div><select class="form-select status-filter" id="assignmentStatus"><option value="">All statuses</option><option>Not started</option><option>In progress</option><option>Submitted</option><option>Completed</option></select></div><div class="table-responsive"><table class="table data-table align-middle"><thead><tr><th>ASSIGNMENT</th><th>SUBJECT</th><th>TEAM</th><th>MEMBERS</th><th>STATUS</th><th></th></tr></thead><tbody id="assignmentRows"><tr><td colspan="6" class="text-center py-5 text-secondary">Loading assignments…</td></tr></tbody></table></div><div id="assignmentEmpty" class="empty-state d-none"><i class="bi bi-clipboard2"></i><h3>No matching assignments</h3><p>Create a team and add its assignment from the Teams page.</p></div></section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
