<?php
require_once __DIR__ . '/common.php';
require_method('GET');
$id = checked_id($_GET['id'] ?? null, 'Student ID');
$student = db()->prepare('SELECT id, student_code, first_name, last_name, class_name FROM students WHERE id=?');
$student->execute([$id]);
$record = $student->fetch();
if (!$record) {
    json_response(['error' => 'Student not found.'], 404);
}
$teams = db()->prepare(
    'SELECT t.id AS team_id, t.team_name, s.code AS subject_code, a.title AS assignment_title, a.status AS assignment_status,
            (SELECT CONCAT(leader.first_name, " ", leader.last_name) FROM students leader WHERE leader.id=t.leader_student_id) AS leader_name
     FROM team_members tm JOIN teams t ON t.id=tm.team_id JOIN subjects s ON s.id=t.subject_id
     LEFT JOIN assignments a ON a.team_id=t.id
     WHERE tm.student_id=? ORDER BY s.sort_order'
);
$teams->execute([$id]);
json_response(['student' => $record, 'teams' => $teams->fetchAll()]);
