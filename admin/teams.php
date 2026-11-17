<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
$pageTitle = 'Teams';
$isAdminPage = true;
require __DIR__ . '/../includes/header.php';
?>
<section class="page-intro"><div><p class="eyebrow">TEAM ORGANIZATION</p><h1>Teams</h1><p class="text-secondary mb-0">Organize students by subject and track team membership.</p></div><button class="btn btn-primary" id="addTeamBtn"><i class="bi bi-plus-lg me-2"></i>Create team</button></section>
<section class="panel-card"><div class="toolbar"><div class="search-box"><i class="bi bi-search"></i><input id="teamSearch" class="form-control" type="search" placeholder="Search teams or students"></div><div class="d-flex gap-2 align-items-center"><label class="small text-secondary" for="teamSubjectFilter">Subject</label><select class="form-select" id="teamSubjectFilter"><option value="">All subjects</option></select></div></div>
<div id="teamCards" class="team-grid"><div class="loading-state">Loading teams…</div></div>
<div class="panel-subheading mt-4"><div><h2>Unassigned students</h2><p>Students not yet placed in a team for the selected subject.</p></div></div><div class="unassigned-list" id="unassignedStudents"><span class="text-secondary small">Choose a subject to view unassigned students.</span></div></section>
<div class="modal fade" id="teamModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><form class="modal-content" id="teamForm"><div class="modal-header"><div><h2 class="modal-title fs-5" id="teamModalTitle">Create team</h2><p class="text-secondary small mb-0">Assign students and add an optional assignment.</p></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body"><input type="hidden" name="id"><div class="row g-3">
<div class="col-md-6"><label class="form-label">Subject *</label><select class="form-select" name="subject_id" required></select></div><div class="col-md-6"><label class="form-label">Team name *</label><input class="form-control" name="team_name" required maxlength="100" placeholder="Team 1"></div>
<div class="col-md-6"><label class="form-label">Team leader</label><select class="form-select" name="leader_student_id"><option value="">No leader selected</option></select><div class="form-text">The selected leader will automatically be added as a member.</div></div>
<div class="col-md-6"><label class="form-label">Team members</label><select class="form-select member-select" name="member_ids[]" multiple size="5"></select><div class="form-text">Hold Ctrl (Windows) or Command (Mac) to select multiple students.</div></div>
<div class="col-12"><hr><h3 class="h6">Assignment <span class="text-secondary fw-normal">(optional)</span></h3></div>
<div class="col-md-8"><label class="form-label">Assignment title</label><input class="form-control" name="assignment_title" maxlength="180"></div><div class="col-md-4"><label class="form-label">Status</label><select class="form-select" name="assignment_status"><option>Not started</option><option>In progress</option><option>Submitted</option><option>Completed</option></select></div>
<div class="col-12"><label class="form-label">Description</label><textarea class="form-control" name="assignment_description" rows="3" maxlength="5000"></textarea></div>
</div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save team</button></div></form></div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
