(() => {
  'use strict';
  const base = document.querySelector('meta[name="app-base"]')?.content || '';
  const url = path => `${base}${path.startsWith('/') ? path : `/${path}`}`;
  const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const esc = value => String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
  const initials = name => String(name || '?').trim().split(/\s+/).slice(0, 2).map(part => part[0] || '').join('').toUpperCase();
  const showToast = (message, type = 'success') => {
    const container = document.getElementById('toastContainer');
    if (!container) return;
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-bg-${type === 'success' ? 'success' : 'danger'} border-0`;
    toast.setAttribute('role', 'status');
    toast.innerHTML = `<div class="d-flex"><div class="toast-body">${esc(message)}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
    container.append(toast);
    const instance = new bootstrap.Toast(toast, {delay: 3600});
    instance.show();
    toast.addEventListener('hidden.bs.toast', () => toast.remove());
  };
  const api = async (path, options = {}) => {
    const headers = new Headers(options.headers || {});
    if (options.body && !(options.body instanceof FormData) && !(options.body instanceof URLSearchParams)) headers.set('Content-Type', 'application/json');
    if (!['GET', 'HEAD'].includes((options.method || 'GET').toUpperCase())) headers.set('X-CSRF-Token', token);
    const response = await fetch(url(path), {...options, headers, credentials: 'same-origin'});
    const type = response.headers.get('content-type') || '';
    const result = type.includes('application/json') ? await response.json() : {};
    if (!response.ok) throw new Error(result.error || `Request failed (${response.status}).`);
    return result;
  };
  const modal = (id, method = 'show') => {
    const element = document.getElementById(id);
    if (element) bootstrap.Modal.getOrCreateInstance(element)[method]();
  };
  const formObject = form => {
    const data = Object.fromEntries(new FormData(form).entries());
    data.member_ids = [...form.querySelector('[name="member_ids[]"]').selectedOptions].map(option => Number(option.value));
    if (data.leader_student_id) data.leader_student_id = Number(data.leader_student_id);
    else data.leader_student_id = null;
    return data;
  };

  const sidebarToggle = document.getElementById('sidebarToggle');
  sidebarToggle?.addEventListener('click', () => document.getElementById('appSidebar')?.classList.toggle('open'));
  document.addEventListener('click', event => {
    const sidebar = document.getElementById('appSidebar');
    if (sidebar?.classList.contains('open') && !sidebar.contains(event.target) && !sidebarToggle?.contains(event.target)) sidebar.classList.remove('open');
  });

  if (document.getElementById('dashboardStats')) {
    api('/api/dashboard.php').then(({stats, subjects, activity}) => {
      Object.entries(stats).forEach(([key, value]) => {
        const element = document.querySelector(`[data-stat="${key}"]`);
        if (element) element.textContent = Number(value).toLocaleString();
      });
      const assignedFoot = document.querySelector('[data-foot="assigned"]');
      if (assignedFoot) assignedFoot.textContent = `${Number(stats.unassigned).toLocaleString()} students not yet assigned`;
      const total = Math.max(1, ...subjects.map(subject => Number(subject.teams)));
      document.getElementById('subjectOverview').innerHTML = subjects.length ? subjects.map(subject => {
        const teams = Number(subject.teams);
        const width = teams ? Math.max(4, teams / total * 100) : 0;
        return `<div class="subject-row"><span class="subject-code">${esc(subject.code)}</span><div class="subject-meter"><span style="width:${width}%"></span></div><span class="subject-meta">${teams} teams · ${Number(subject.assigned)} students</span></div>`;
      }).join('') : '<div class="empty-state">No subjects are configured.</div>';
      const icons = {student:'bi-person', team:'bi-diagram-3', subject:'bi-book'};
      document.getElementById('activityList').innerHTML = activity.length ? activity.map(item => `<div class="activity-item"><span class="activity-dot"><i class="bi ${icons[item.entity_type] || 'bi-check2'}"></i></span><div class="activity-copy">${esc(item.description)}<small>${esc(new Date(item.created_at.replace(' ', 'T')).toLocaleString())}</small></div></div>`).join('') : '<div class="loading-state">No activity yet. Updates will appear here.</div>';
    }).catch(error => showToast(error.message, 'error'));
  }

  const studentsBody = document.getElementById('studentRows');
  if (studentsBody) {
    const form = document.getElementById('studentForm');
    const studentModal = document.getElementById('studentModal');
    const drawStudents = async () => {
      try {
        const data = await api(`/api/students.php?q=${encodeURIComponent(document.getElementById('studentSearch').value.trim())}`);
        const {students} = data;
        document.getElementById('studentCount').textContent = `${students.length} student${students.length === 1 ? '' : 's'}`;
        studentsBody.innerHTML = students.length ? students.map(student => {
          const fullName = `${student.first_name} ${student.last_name}`;
          const avatar = student.photo_url ? `<img src="${esc(student.photo_url)}" alt="">` : esc(initials(fullName));
          return `<tr><td><div class="student-cell"><span class="student-avatar">${avatar}</span><span><strong>${esc(fullName)}</strong><small>Added ${esc(String(student.created_at).slice(0,10))}</small></span></div></td><td><span class="fw-semibold">${esc(student.student_code)}</span></td><td>${esc(student.gender)}</td><td>${esc(student.phone || '—')}</td><td>${esc(student.class_name)}</td><td><div class="row-actions"><button class="icon-btn" title="Edit student" data-edit-student="${student.id}"><i class="bi bi-pencil"></i></button><button class="icon-btn danger" title="Delete student" data-delete-student="${student.id}"><i class="bi bi-trash3"></i></button></div></td></tr>`;
        }).join('') : '<tr><td colspan="6"><div class="empty-state"><i class="bi bi-person"></i><h3>No students found</h3><p>Add students or import the class roster from CSV.</p></div></td></tr>';
      } catch (error) {
        studentsBody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">${esc(error.message)}</td></tr>`;
      }
    };
    document.getElementById('studentSearch').addEventListener('input', drawStudents);
    document.getElementById('addStudentBtn').addEventListener('click', () => {
      form.reset(); form.elements.id.value = ''; document.getElementById('studentModalTitle').textContent = 'Add student'; modal('studentModal');
    });
    studentsBody.addEventListener('click', async event => {
      const edit = event.target.closest('[data-edit-student]');
      const remove = event.target.closest('[data-delete-student]');
      if (edit) {
        const {students} = await api('/api/students.php');
        const student = students.find(item => Number(item.id) === Number(edit.dataset.editStudent));
        if (!student) return showToast('Student could not be found.', 'error');
        for (const [key, value] of Object.entries(student)) if (form.elements[key]) form.elements[key].value = value ?? '';
        document.getElementById('studentModalTitle').textContent = 'Edit student'; modal('studentModal');
      }
      if (remove && confirm('Delete this student? Their team memberships will also be removed.')) {
        try { await api(`/api/students.php?id=${remove.dataset.deleteStudent}`, {method:'DELETE'}); showToast('Student deleted.'); await drawStudents(); }
        catch (error) { showToast(error.message, 'error'); }
      }
    });
    form.addEventListener('submit', async event => {
      event.preventDefault();
      const data = Object.fromEntries(new FormData(form).entries());
      const id = data.id; delete data.id;
      try {
        await api(id ? `/api/students.php?id=${id}` : '/api/students.php', {method:id ? 'PUT' : 'POST', body:JSON.stringify(data)});
        modal('studentModal', 'hide'); showToast(id ? 'Student updated.' : 'Student added.'); await drawStudents();
      } catch (error) { showToast(error.message, 'error'); }
    });
    document.getElementById('importForm').addEventListener('submit', async event => {
      event.preventDefault();
      try {
        const result = await api('/api/students.php?import=csv', {method:'POST', body:new FormData(event.currentTarget)});
        modal('importModal', 'hide'); event.currentTarget.reset(); showToast(result.message); await drawStudents();
      } catch (error) { showToast(error.message, 'error'); }
    });
    drawStudents();
  }

  const subjectCardHtml = (subject, admin = false) => {
    const href = admin ? url(`/admin/teams.php?subject=${subject.id}`) : url(`/student/teams.php?subject=${subject.id}`);
    return `<a class="${admin ? 'subject-admin-card' : 'public-subject-card'}" href="${href}"><span class="subject-symbol">${esc(subject.code.slice(0,2))}</span><span><h3>${esc(subject.name)}</h3><p>${Number(subject.team_count)} teams · ${Number(subject.assigned_students)} students</p></span><i class="bi bi-arrow-up-right"></i></a>`;
  };
  const subjectPromise = () => api('/api/subjects.php').then(result => result.subjects);
  const adminSubjectGrid = document.getElementById('adminSubjects');
  const publicSubjectGrids = document.querySelectorAll('#publicSubjectCards');
  if (adminSubjectGrid || publicSubjectGrids.length) {
    subjectPromise().then(subjects => {
      if (adminSubjectGrid) adminSubjectGrid.innerHTML = subjects.map(subject => subjectCardHtml(subject, true)).join('');
      publicSubjectGrids.forEach(grid => grid.innerHTML = subjects.map(subject => subjectCardHtml(subject)).join(''));
    }).catch(error => {
      if (adminSubjectGrid) adminSubjectGrid.innerHTML = `<div class="alert alert-danger">${esc(error.message)}</div>`;
      publicSubjectGrids.forEach(grid => grid.innerHTML = `<div class="alert alert-danger">${esc(error.message)}</div>`);
    });
  }

  const teamCards = document.getElementById('teamCards');
  if (teamCards) {
    const form = document.getElementById('teamForm');
    const subjectFilter = document.getElementById('teamSubjectFilter');
    const studentCache = [];
    let teamsCache = [];
    let subjectsCache = [];
    const studentName = student => `${student.first_name} ${student.last_name}`;
    const loadTeams = async () => {
      try {
        const [teamData, studentData] = await Promise.all([api('/api/teams.php'), api('/api/students.php')]);
        teamsCache = teamData.teams; studentCache.splice(0, studentCache.length, ...studentData.students);
        const selected = new URLSearchParams(location.search).get('subject');
        subjectFilter.innerHTML = '<option value="">All subjects</option>' + subjectsCache.map(item => `<option value="${item.id}">${esc(item.name)}</option>`).join('');
        if (selected) subjectFilter.value = selected;
        form.elements.subject_id.innerHTML = subjectsCache.map(item => `<option value="${item.id}">${esc(item.name)}</option>`).join('');
        renderTeams();
        const editId = new URLSearchParams(location.search).get('edit');
        if (editId) openEdit(Number(editId));
      } catch (error) { teamCards.innerHTML = `<div class="empty-state text-danger">${esc(error.message)}</div>`; }
    };
    const renderTeams = () => {
      const search = document.getElementById('teamSearch').value.trim().toLowerCase();
      const filter = subjectFilter.value;
      const list = teamsCache.filter(team => (!filter || Number(team.subject_id) === Number(filter)) && (!search || `${team.team_name} ${team.subject_code} ${team.members.map(studentName).join(' ')}`.toLowerCase().includes(search)));
      teamCards.innerHTML = list.length ? list.map(team => {
        const leader = team.members.find(member => member.role === 'leader');
        const status = team.assignment_status || 'No assignment';
        const statusClass = status === 'Completed' ? 'completed' : status === 'Submitted' ? 'submitted' : status === 'In progress' ? 'in-progress' : '';
        const members = team.members.length ? team.members.map(member => `<span class="member-chip">${member.role === 'leader' ? '<i class="bi bi-star-fill me-1 text-warning"></i>' : ''}${esc(studentName(member))}</span>`).join('') : '<span class="text-secondary small">No members assigned</span>';
        return `<article class="team-card"><div class="team-card-head"><div><span class="team-subject">${esc(team.subject_code)}</span><h3>${esc(team.team_name)}</h3></div><span class="team-count">${team.members.length} member${team.members.length === 1 ? '' : 's'}</span></div><div class="team-leader"><i class="bi bi-star me-1"></i>Leader: ${esc(leader ? studentName(leader) : 'Not assigned')}</div><div class="member-chips">${members}</div><div class="team-card-foot"><span class="status-badge ${statusClass}">${esc(team.assignment_title ? status : 'No assignment')}</span><span class="row-actions"><button class="icon-btn" data-edit-team="${team.id}" title="Edit team"><i class="bi bi-pencil"></i></button><button class="icon-btn danger" data-delete-team="${team.id}" title="Delete team"><i class="bi bi-trash3"></i></button></span></div></article>`;
      }).join('') : '<div class="empty-state"><i class="bi bi-diagram-3"></i><h3>No teams yet</h3><p>Create a team to start assigning students.</p></div>';
      renderUnassigned();
    };
    const renderUnassigned = () => {
      const node = document.getElementById('unassignedStudents');
      if (!subjectFilter.value) { node.innerHTML = '<span class="text-secondary small">Choose a subject to view unassigned students.</span>'; return; }
      const assigned = new Set(teamsCache.filter(team => Number(team.subject_id) === Number(subjectFilter.value)).flatMap(team => team.members.map(student => Number(student.id))));
      const unassigned = studentCache.filter(student => !assigned.has(Number(student.id)));
      node.innerHTML = unassigned.length ? unassigned.map(student => `<span class="unassigned-person">${esc(studentName(student))} · ${esc(student.student_code)}</span>`).join('') : '<span class="text-secondary small">Every student is assigned for this subject.</span>';
    };
    const setMembers = (members, selected = []) => {
      const chosen = new Set(selected.map(Number));
      members.innerHTML = studentCache.map(student => `<option value="${student.id}" ${chosen.has(Number(student.id)) ? 'selected' : ''}>${esc(studentName(student))} · ${esc(student.student_code)}</option>`).join('');
    };
    const setLeaders = (select, selected = '') => {
      select.innerHTML = '<option value="">No leader selected</option>' + studentCache.map(student => `<option value="${student.id}" ${Number(selected) === Number(student.id) ? 'selected' : ''}>${esc(studentName(student))} · ${esc(student.student_code)}</option>`).join('');
    };
    const openEdit = id => {
      const team = teamsCache.find(item => Number(item.id) === Number(id));
      if (!team) return showToast('Team could not be found.', 'error');
      form.reset(); form.elements.id.value = team.id;
      form.elements.subject_id.value = team.subject_id; form.elements.team_name.value = team.team_name;
      form.elements.assignment_title.value = team.assignment_title || '';
      form.elements.assignment_description.value = team.assignment_description || '';
      form.elements.assignment_status.value = team.assignment_status || 'Not started';
      setMembers(form.querySelector('[name="member_ids[]"]'), team.members.map(member => member.id));
      setLeaders(form.elements.leader_student_id, team.leader_student_id || '');
      document.getElementById('teamModalTitle').textContent = 'Edit team'; modal('teamModal');
    };
    const openAdd = () => {
      form.reset(); form.elements.id.value = ''; form.elements.assignment_status.value = 'Not started';
      if (subjectFilter.value) form.elements.subject_id.value = subjectFilter.value;
      setMembers(form.querySelector('[name="member_ids[]"]')); setLeaders(form.elements.leader_student_id);
      document.getElementById('teamModalTitle').textContent = 'Create team'; modal('teamModal');
    };
    subjectFilter.addEventListener('change', renderTeams);
    document.getElementById('teamSearch').addEventListener('input', renderTeams);
    document.getElementById('addTeamBtn').addEventListener('click', openAdd);
    teamCards.addEventListener('click', async event => {
      const edit = event.target.closest('[data-edit-team]');
      const remove = event.target.closest('[data-delete-team]');
      if (edit) openEdit(Number(edit.dataset.editTeam));
      if (remove && confirm('Delete this team and its assignment? Team member assignments will be removed.')) {
        try { await api(`/api/teams.php?id=${remove.dataset.deleteTeam}`, {method:'DELETE'}); showToast('Team deleted.'); await loadTeams(); }
        catch (error) { showToast(error.message, 'error'); }
      }
    });
    form.elements.leader_student_id.addEventListener('change', event => {
      const select = form.querySelector('[name="member_ids[]"]');
      const leader = String(event.target.value);
      if (leader && ![...select.options].some(option => option.value === leader && option.selected)) {
        const option = [...select.options].find(item => item.value === leader);
        if (option) option.selected = true;
      }
    });
    form.addEventListener('submit', async event => {
      event.preventDefault();
      const data = formObject(form); const id = data.id; delete data.id;
      try {
        await api(id ? `/api/teams.php?id=${id}` : '/api/teams.php', {method:id ? 'PUT' : 'POST', body:JSON.stringify(data)});
        modal('teamModal', 'hide'); showToast(id ? 'Team updated.' : 'Team created.'); await loadTeams();
      } catch (error) { showToast(error.message, 'error'); }
    });
    subjectPromise().then(subjects => { subjectsCache = subjects; return loadTeams(); }).catch(error => showToast(error.message, 'error'));
  }

  const assignmentRows = document.getElementById('assignmentRows');
  if (assignmentRows) {
    let allTeams = [];
    const renderAssignments = () => {
      const search = document.getElementById('assignmentSearch').value.trim().toLowerCase();
      const status = document.getElementById('assignmentStatus').value;
      const rows = allTeams.filter(team => team.assignment_title && (!status || team.assignment_status === status) && `${team.assignment_title} ${team.team_name} ${team.subject_code}`.toLowerCase().includes(search));
      assignmentRows.innerHTML = rows.length ? rows.map(team => `<tr><td><strong class="text-dark">${esc(team.assignment_title)}</strong><div class="small text-secondary mt-1">${esc(team.assignment_description || 'No description')}</div></td><td><span class="subject-code">${esc(team.subject_code)}</span></td><td>${esc(team.team_name)}</td><td>${team.members.length}</td><td><span class="status-badge ${(team.assignment_status || '').toLowerCase().replace(' ','-')}">${esc(team.assignment_status)}</span></td><td><a class="btn btn-sm btn-outline-primary" href="${url(`/admin/teams.php?edit=${team.id}`)}">Edit</a></td></tr>`).join('') : '';
      document.getElementById('assignmentEmpty').classList.toggle('d-none', rows.length > 0);
    };
    Promise.all([api('/api/teams.php'), subjectPromise()]).then(([data]) => { allTeams = data.teams; renderAssignments(); }).catch(error => { assignmentRows.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">${esc(error.message)}</td></tr>`; });
    document.getElementById('assignmentSearch').addEventListener('input', renderAssignments);
    document.getElementById('assignmentStatus').addEventListener('change', renderAssignments);
  }

  const publicTeamsNode = document.getElementById('publicTeamResults');
  if (publicTeamsNode) {
    let teams = [];
    let subjects = [];
    const subjectSelect = document.getElementById('publicSubjectFilter');
    const querySubject = new URLSearchParams(location.search).get('subject');
    const renderPublicTeams = (highlightIds = null) => {
      const subject = subjectSelect.value;
      const list = teams.filter(team => (!subject || Number(team.subject_id) === Number(subject)) && (!highlightIds || highlightIds.has(Number(team.id))));
      publicTeamsNode.innerHTML = list.length ? list.map(team => {
        const members = team.members.map(member => `<span class="public-member ${member.role === 'leader' ? 'leader' : ''}">${member.role === 'leader' ? '★ ' : ''}${esc(`${member.first_name} ${member.last_name}`)}${member.role === 'leader' ? ' · Leader' : ''}</span>`).join('');
        const assignment = team.assignment_title ? `<div class="public-assignment"><strong>${esc(team.assignment_title)}</strong> · ${esc(team.assignment_status || 'Not started')}${team.assignment_description ? `<div class="mt-1">${esc(team.assignment_description)}</div>` : ''}</div>` : '';
        return `<article class="public-team-card"><div class="public-team-title"><div><small>${esc(team.subject_code)}</small><h2>${esc(team.team_name)}</h2></div><span class="team-count">${team.members.length} members</span></div><div class="small text-secondary mt-2">Leader: ${esc((team.members.find(member => member.role === 'leader') || {}).first_name ? `${team.members.find(member => member.role === 'leader').first_name} ${team.members.find(member => member.role === 'leader').last_name}` : 'Not assigned')}</div><div class="public-member-list">${members || '<span class="text-secondary small">No members assigned.</span>'}</div>${assignment}</article>`;
      }).join('') : '<div class="empty-state"><i class="bi bi-search"></i><h3>No matching teams</h3><p>Try a different search or subject.</p></div>';
    };
    Promise.all([api('/api/teams.php'), subjectPromise()]).then(([teamData, subjectData]) => {
      teams = teamData.teams; subjects = subjectData;
      subjectSelect.innerHTML = '<option value="">All subjects</option>' + subjects.map(subject => `<option value="${subject.id}">${esc(subject.name)}</option>`).join('');
      if (querySubject) subjectSelect.value = querySubject;
      renderPublicTeams();
    }).catch(error => publicTeamsNode.innerHTML = `<div class="alert alert-danger">${esc(error.message)}</div>`);
    subjectSelect.addEventListener('change', () => renderPublicTeams());
    const search = document.getElementById('publicStudentSearch');
    let timer;
    search.addEventListener('input', () => {
      clearTimeout(timer);
      const query = search.value.trim();
      if (query.length < 2) { renderPublicTeams(); return; }
      timer = setTimeout(async () => {
        try {
          const {students} = await api(`/api/public-students.php?q=${encodeURIComponent(query)}`);
          const ids = new Set();
          const matches = [];
          for (const student of students) {
            const studentTeams = teams.filter(team => team.members.some(member => Number(member.id) === Number(student.id)));
            if (studentTeams.length) studentTeams.forEach(team => ids.add(Number(team.id)));
            else matches.push(`<a class="public-team-card d-flex align-items-center gap-3" href="${url(`/student/student-details.php?id=${student.id}`)}"><span class="student-avatar">${esc(initials(`${student.first_name} ${student.last_name}`))}</span><span><strong>${esc(`${student.first_name} ${student.last_name}`)}</strong><small class="d-block text-secondary">${esc(student.student_code)} · Not assigned to a team yet</small></span></a>`);
          }
          renderPublicTeams(ids);
          if (matches.length) publicTeamsNode.insertAdjacentHTML('afterbegin', `<div class="small text-secondary">Students with no current team</div>${matches.join('')}`);
          if (!students.length) publicTeamsNode.innerHTML = '<div class="empty-state"><i class="bi bi-search"></i><h3>No students found</h3><p>Check the spelling or search by student ID.</p></div>';
        } catch (error) { showToast(error.message, 'error'); }
      }, 220);
    });
  }

  const details = document.getElementById('studentDetails');
  if (details) {
    const id = new URLSearchParams(location.search).get('id');
    if (!id) { details.innerHTML = '<div class="empty-state"><i class="bi bi-person"></i><h3>Select a student to view</h3><p>Search for a student from the team page.</p></div>'; }
    else api(`/api/public-student-details.php?id=${encodeURIComponent(id)}`).then(({student, teams}) => {
      details.innerHTML = `<div class="student-details-head"><span class="student-avatar">${esc(initials(`${student.first_name} ${student.last_name}`))}</span><span><h2>${esc(`${student.first_name} ${student.last_name}`)}</h2><p>${esc(student.student_code)} · ${esc(student.class_name)}</p></span></div><div class="student-details-list">${teams.length ? teams.map(team => `<article class="detail-team"><span class="team-subject">${esc(team.subject_code)}</span><h3 class="mt-1">${esc(team.team_name)}</h3><p>Team leader: ${esc(team.leader_name || 'Not assigned')}</p>${team.assignment_title ? `<p>Assignment: ${esc(team.assignment_title)} · ${esc(team.assignment_status)}</p>` : '<p>No assignment posted yet.</p>'}</article>`).join('') : '<div class="empty-state">This student is not assigned to a team yet.</div>'}</div>`;
    }).catch(error => details.innerHTML = `<div class="alert alert-danger">${esc(error.message)}</div>`);
  }
})();
