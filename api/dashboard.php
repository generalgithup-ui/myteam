<?php
require_once __DIR__ . '/common.php';
require_method('GET');
require_admin();
$pdo = db();
$stats = [
    'students' => (int)$pdo->query('SELECT COUNT(*) FROM students')->fetchColumn(),
    'subjects' => (int)$pdo->query('SELECT COUNT(*) FROM subjects')->fetchColumn(),
    'teams' => (int)$pdo->query('SELECT COUNT(*) FROM teams')->fetchColumn(),
    'assigned' => (int)$pdo->query('SELECT COUNT(DISTINCT student_id) FROM team_members')->fetchColumn(),
];
$stats['unassigned'] = max(0, $stats['students'] - $stats['assigned']);
$subjects = $pdo->query(
    'SELECT s.code, COUNT(DISTINCT t.id) AS teams, COUNT(DISTINCT tm.student_id) AS assigned
     FROM subjects s LEFT JOIN teams t ON t.subject_id=s.id
     LEFT JOIN team_members tm ON tm.team_id=t.id GROUP BY s.id ORDER BY s.sort_order'
)->fetchAll();
$activity = $pdo->query('SELECT action, entity_type, description, created_at FROM activity_logs ORDER BY created_at DESC LIMIT 8')->fetchAll();
json_response(['stats' => $stats, 'subjects' => $subjects, 'activity' => $activity]);
