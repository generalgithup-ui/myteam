<?php
$pageTitle = 'Student details';
require __DIR__ . '/../includes/header.php';
?>
<main class="container public-page"><div class="page-intro public-intro"><div><p class="eyebrow">STUDENT PORTAL</p><h1>Student details</h1><p class="text-secondary mb-0">View a student’s team assignments across subjects.</p></div><a class="btn btn-outline-primary" href="<?= e(app_url('/student/teams.php')) ?>"><i class="bi bi-search me-1"></i>Search another student</a></div><section class="panel-card" id="studentDetails"><div class="loading-state">Loading student details…</div></section></main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
