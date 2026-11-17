<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
$pageTitle = 'Subjects';
$isAdminPage = true;
require __DIR__ . '/../includes/header.php';
?>
<section class="page-intro"><div><p class="eyebrow">CURRICULUM</p><h1>Subjects</h1><p class="text-secondary mb-0">The six configured subjects used for team assignments.</p></div></section>
<div class="subject-card-grid" id="adminSubjects"><div class="loading-state">Loading subjects…</div></div>
<div class="alert alert-light border mt-4"><i class="bi bi-info-circle text-primary me-2"></i>The subject list is fixed at six subjects to keep assignments consistent. Create and manage teams from the Teams page.</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
