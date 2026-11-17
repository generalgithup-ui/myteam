<?php
$pageTitle = 'Teams and students';
require __DIR__ . '/../includes/header.php';
?>
<main class="container public-page"><div class="page-intro public-intro"><div><p class="eyebrow">STUDENT PORTAL</p><h1>Find your team</h1><p class="text-secondary mb-0">Search your name, or browse all teams by subject.</p></div></div>
<div class="panel-card public-filter-card"><div class="toolbar"><div class="search-box flex-grow-1"><i class="bi bi-search"></i><input id="publicStudentSearch" type="search" class="form-control" placeholder="Search student name or ID"></div><select class="form-select public-filter" id="publicSubjectFilter"><option value="">All subjects</option></select></div><div id="publicTeamResults" class="public-team-results"><div class="loading-state">Loading teams…</div></div></div></main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
