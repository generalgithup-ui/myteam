<?php
require_once __DIR__ . '/common.php';
require_method('GET');
$query = trim((string)($_GET['q'] ?? ''));
if (mb_strlen($query) < 2) {
    json_response(['students' => []]);
}
$term = '%' . $query . '%';
$statement = db()->prepare(
    'SELECT id, student_code, first_name, last_name FROM students
     WHERE student_code LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR CONCAT(first_name, " ", last_name) LIKE ?
     ORDER BY last_name, first_name LIMIT 30'
);
$statement->execute([$term, $term, $term, $term]);
json_response(['students' => $statement->fetchAll()]);
