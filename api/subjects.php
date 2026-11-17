<?php
require_once __DIR__ . '/common.php';
require_method('GET');
$subjects = db()->query(
    'SELECT s.id, s.code, s.name, s.sort_order, COUNT(DISTINCT t.id) AS team_count, COUNT(DISTINCT tm.student_id) AS assigned_students
     FROM subjects s LEFT JOIN teams t ON t.subject_id = s.id
     LEFT JOIN team_members tm ON tm.team_id = t.id
     GROUP BY s.id ORDER BY s.sort_order'
)->fetchAll();
json_response(['subjects' => $subjects]);
