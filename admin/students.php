<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
$pageTitle = 'Students';
$isAdminPage = true;
require __DIR__ . '/../includes/header.php';
?>
<section class="page-intro"><div><p class="eyebrow">ROSTER MANAGEMENT</p><h1>Students</h1><p class="text-secondary mb-0">Manage student details and maintain your class roster.</p></div><button class="btn btn-primary" id="addStudentBtn"><i class="bi bi-plus-lg me-2"></i>Add student</button></section>
<section class="panel-card">
  <div class="toolbar"><div class="search-box"><i class="bi bi-search"></i><input id="studentSearch" type="search" class="form-control" placeholder="Search by name or student ID"></div><div class="d-flex gap-2 flex-wrap"><a class="btn btn-outline-secondary" href="<?= e(app_url('/api/students.php?export=csv')) ?>"><i class="bi bi-download me-1"></i>Export CSV</a><button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#importModal"><i class="bi bi-upload me-1"></i>Import CSV</button></div></div>
  <div class="table-responsive"><table class="table data-table align-middle"><thead><tr><th>STUDENT</th><th>STUDENT ID</th><th>GENDER</th><th>PHONE</th><th>CLASS</th><th class="text-end">ACTIONS</th></tr></thead><tbody id="studentRows"><tr><td colspan="6" class="text-center py-5 text-secondary">Loading students…</td></tr></tbody></table></div>
  <div class="table-footer"><span id="studentCount">0 students</span><a href="<?= e(app_url('/assets/templates/students.csv')) ?>" download>Download CSV template</a></div>
</section>
<div class="modal fade" id="studentModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><form class="modal-content" id="studentForm"><div class="modal-header"><div><h2 class="modal-title fs-5" id="studentModalTitle">Add student</h2><p class="text-secondary small mb-0">Enter the student’s roster information.</p></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body"><input type="hidden" name="id"><div class="row g-3">
  <div class="col-sm-6"><label class="form-label">Student ID *</label><input class="form-control" name="student_code" maxlength="40" required></div>
  <div class="col-sm-6"><label class="form-label">Class *</label><input class="form-control" name="class_name" maxlength="80" required></div>
  <div class="col-sm-6"><label class="form-label">First name *</label><input class="form-control" name="first_name" maxlength="100" required></div>
  <div class="col-sm-6"><label class="form-label">Last name *</label><input class="form-control" name="last_name" maxlength="100" required></div>
  <div class="col-sm-6"><label class="form-label">Gender *</label><select class="form-select" name="gender" required><option value="">Select gender</option><option>Female</option><option>Male</option><option>Other</option><option>Prefer not to say</option></select></div>
  <div class="col-sm-6"><label class="form-label">Phone <span class="text-secondary">(optional)</span></label><input class="form-control" name="phone" maxlength="40"></div>
  <div class="col-12"><label class="form-label">Photo URL <span class="text-secondary">(optional)</span></label><input class="form-control" name="photo_url" type="url" maxlength="500" placeholder="https://example.com/photo.jpg"></div>
</div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" type="submit">Save student</button></div></form></div></div>
<div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><form class="modal-content" id="importForm"><div class="modal-header"><h2 class="modal-title fs-5">Import students from CSV</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body"><p class="text-secondary">Upload a UTF-8 CSV using the provided template. Existing student IDs are skipped; no records are overwritten.</p><input class="form-control" type="file" name="file" accept=".csv,text/csv" required><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Import CSV</button></div></form></div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
