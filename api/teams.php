<?php
require_once __DIR__ . '/common.php';
$method = require_method('GET', 'POST', 'PUT', 'DELETE');

if ($method === 'GET') {
    $subjectId = isset($_GET['subject_id']) ? checked_id($_GET['subject_id'], 'Subject ID') : null;
    $sql = 'SELECT t.id, t.subject_id, t.team_name, t.leader_student_id, s.code AS subject_code, s.name AS subject_name,
                   a.title AS assignment_title, a.description AS assignment_description, a.status AS assignment_status,
                   tm.student_id, tm.member_role, st.student_code, st.first_name, st.last_name, st.class_name
            FROM teams t JOIN subjects s ON s.id=t.subject_id
            LEFT JOIN assignments a ON a.team_id=t.id
            LEFT JOIN team_members tm ON tm.team_id=t.id
            LEFT JOIN students st ON st.id=tm.student_id';
    if ($subjectId !== null) {
        $statement = db()->prepare($sql . ' WHERE t.subject_id=? ORDER BY s.sort_order, t.team_name, st.last_name, st.first_name');
        $statement->execute([$subjectId]);
        $rows = $statement->fetchAll();
    } else {
        $rows = db()->query($sql . ' ORDER BY s.sort_order, t.team_name, st.last_name, st.first_name')->fetchAll();
    }
    $teams = [];
    foreach ($rows as $row) {
        $id = (int)$row['id'];
        if (!isset($teams[$id])) {
            $teams[$id] = [
                'id' => $id,
                'subject_id' => (int)$row['subject_id'],
                'subject_code' => $row['subject_code'],
                'subject_name' => $row['subject_name'],
                'team_name' => $row['team_name'],
                'leader_student_id' => $row['leader_student_id'] === null ? null : (int)$row['leader_student_id'],
                'assignment_title' => $row['assignment_title'],
                'assignment_description' => $row['assignment_description'],
                'assignment_status' => $row['assignment_status'],
                'members' => [],
            ];
        }
        if ($row['student_id'] !== null) {
            $teams[$id]['members'][] = [
                'id' => (int)$row['student_id'],
                'student_code' => $row['student_code'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'class_name' => $row['class_name'],
                'role' => $row['member_role'],
            ];
        }
    }
    json_response(['teams' => array_values($teams)]);
}

$admin = require_admin();
$data = in_array($method, ['POST', 'PUT'], true) ? request_json() : [];
if ($method === 'POST' || $method === 'PUT') {
    verify_csrf();
    $id = $method === 'PUT' ? checked_id($_GET['id'] ?? ($data['id'] ?? null), 'Team ID') : null;
    $subjectId = checked_id($data['subject_id'] ?? null, 'Subject ID');
    $teamName = text_field($data, 'team_name', 100);
    $leaderId = ($data['leader_student_id'] ?? '') === '' ? null : checked_id($data['leader_student_id'], 'Team leader');
    $memberIds = $data['member_ids'] ?? [];
    if (!is_array($memberIds)) {
        json_response(['error' => 'Team members must be a list of student IDs.'], 422);
    }
    $memberIds = array_values(array_unique(array_map(static fn($v) => checked_id($v, 'Student ID'), $memberIds)));
    if ($leaderId !== null && !in_array($leaderId, $memberIds, true)) {
        $memberIds[] = $leaderId;
    }
    $title = text_field($data, 'assignment_title', 180, false);
    $description = text_field($data, 'assignment_description', 5000, false);
    $status = (string)($data['assignment_status'] ?? 'Not started');
    $statuses = ['Not started', 'In progress', 'Submitted', 'Completed'];
    if (!in_array($status, $statuses, true)) {
        json_response(['error' => 'Select a valid assignment status.'], 422);
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $oldSubjectId = null;
        if ($id !== null) {
            $lookup = $pdo->prepare('SELECT subject_id FROM teams WHERE id=? FOR UPDATE');
            $lookup->execute([$id]);
            $oldSubjectId = $lookup->fetchColumn();
            if ($oldSubjectId === false) {
                $pdo->rollBack();
                json_response(['error' => 'Team not found.'], 404);
            }
        }
        $lockIds = array_unique(array_filter([$subjectId, $oldSubjectId === null ? null : (int)$oldSubjectId]));
        sort($lockIds);
        $lockSubject = $pdo->prepare('SELECT id FROM subjects WHERE id=? FOR UPDATE');
        foreach ($lockIds as $lockId) {
            $lockSubject->execute([$lockId]);
            if (!$lockSubject->fetchColumn()) {
                $pdo->rollBack();
                json_response(['error' => 'Subject not found.'], 404);
            }
        }
        if ($memberIds) {
            $placeholders = implode(',', array_fill(0, count($memberIds), '?'));
            $exists = $pdo->prepare("SELECT COUNT(*) FROM students WHERE id IN ({$placeholders})");
            $exists->execute($memberIds);
            if ((int)$exists->fetchColumn() !== count($memberIds)) {
                $pdo->rollBack();
                json_response(['error' => 'One or more selected students no longer exist.'], 422);
            }
            $conflictSql = "SELECT st.student_code FROM team_members tm JOIN teams t ON t.id=tm.team_id
                            JOIN students st ON st.id=tm.student_id
                            WHERE t.subject_id=? AND tm.student_id IN ({$placeholders})";
            $params = [$subjectId, ...$memberIds];
            if ($id !== null) {
                $conflictSql .= ' AND t.id<>?';
                $params[] = $id;
            }
            $conflict = $pdo->prepare($conflictSql);
            $conflict->execute($params);
            $codes = $conflict->fetchAll(PDO::FETCH_COLUMN);
            if ($codes) {
                $pdo->rollBack();
                json_response(['error' => 'Student(s) already assigned to another team in this subject: ' . implode(', ', $codes)], 409);
            }
        }

        if ($id === null) {
            $statement = $pdo->prepare('INSERT INTO teams (subject_id, team_name, leader_student_id) VALUES (?, ?, ?)');
            $statement->execute([$subjectId, $teamName, $leaderId]);
            $id = (int)$pdo->lastInsertId();
        } else {
            $statement = $pdo->prepare('UPDATE teams SET subject_id=?, team_name=?, leader_student_id=? WHERE id=?');
            $statement->execute([$subjectId, $teamName, $leaderId, $id]);
        }
        $pdo->prepare('DELETE FROM team_members WHERE team_id=?')->execute([$id]);
        $memberInsert = $pdo->prepare('INSERT INTO team_members (team_id, student_id, member_role) VALUES (?, ?, ?)');
        foreach ($memberIds as $memberId) {
            $memberInsert->execute([$id, $memberId, $memberId === $leaderId ? 'leader' : 'member']);
        }
        if ($title === null) {
            $pdo->prepare('DELETE FROM assignments WHERE team_id=?')->execute([$id]);
        } else {
            $assignment = $pdo->prepare(
                'INSERT INTO assignments (team_id, title, description, status) VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE title=VALUES(title), description=VALUES(description), status=VALUES(status)'
            );
            $assignment->execute([$id, $title, $description, $status]);
        }
        $pdo->commit();
    } catch (PDOException $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($exception->getCode() === '23000') {
            json_response(['error' => 'A team with that name already exists in this subject.'], 409);
        }
        error_log('Team save failed: ' . $exception->getMessage());
        json_response(['error' => 'The team could not be saved. Please try again.'], 500);
    }
    log_activity($method === 'POST' ? 'create' : 'update', 'team', $id, ($method === 'POST' ? 'Created ' : 'Updated ') . $teamName . '.');
    json_response(['message' => $method === 'POST' ? 'Team created.' : 'Team updated.', 'id' => $id], $method === 'POST' ? 201 : 200);
}

$id = checked_id($_GET['id'] ?? ($data['id'] ?? null), 'Team ID');
verify_csrf();
$lookup = db()->prepare('SELECT team_name FROM teams WHERE id=?');
$lookup->execute([$id]);
$name = $lookup->fetchColumn();
if ($name === false) {
    json_response(['error' => 'Team not found.'], 404);
}
$delete = db()->prepare('DELETE FROM teams WHERE id=?');
$delete->execute([$id]);
log_activity('delete', 'team', $id, "Deleted {$name}.");
json_response(['message' => 'Team deleted.']);
