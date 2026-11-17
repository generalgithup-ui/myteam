<?php
require_once __DIR__ . '/common.php';
$method = require_method('GET', 'POST', 'PUT', 'DELETE');
$admin = require_admin();

if ($method === 'GET') {
    if (isset($_GET['export']) && $_GET['export'] === 'csv') {
        $rows = db()->query('SELECT student_code, first_name, last_name, gender, phone, class_name, photo_url FROM students ORDER BY last_name, first_name')->fetchAll();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="team-assignment-students.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['student_code', 'first_name', 'last_name', 'gender', 'phone', 'class_name', 'photo_url']);
        foreach ($rows as $row) {
            fputcsv($output, array_map(static function ($value) {
                $value = (string)($value ?? '');
                return preg_match('/^[\s]*[=+\-@]/', $value) ? "'" . $value : $value;
            }, array_values($row)));
        }
        fclose($output);
        exit;
    }
    $search = trim((string)($_GET['q'] ?? ''));
    $query = 'SELECT s.id, s.student_code, s.first_name, s.last_name, s.gender, s.phone, s.class_name, s.photo_url, s.created_at FROM students s';
    if ($search !== '') {
        $query .= ' WHERE s.student_code LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR CONCAT(s.first_name, " ", s.last_name) LIKE ?';
        $term = '%' . $search . '%';
        $statement = db()->prepare($query . ' ORDER BY s.last_name, s.first_name');
        $statement->execute([$term, $term, $term, $term]);
    } else {
        $statement = db()->query($query . ' ORDER BY s.last_name, s.first_name');
    }
    json_response(['students' => $statement->fetchAll()]);
}

if ($method === 'POST' && isset($_GET['import'])) {
    verify_csrf();
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        json_response(['error' => 'Choose a readable CSV file to import.'], 422);
    }
    if ($_FILES['file']['size'] > 2 * 1024 * 1024) {
        json_response(['error' => 'CSV files must be 2 MB or smaller.'], 413);
    }
    $handle = fopen($_FILES['file']['tmp_name'], 'r');
    if ($handle === false) {
        json_response(['error' => 'The uploaded CSV could not be opened.'], 422);
    }
    $headers = fgetcsv($handle);
    $expected = ['student_code', 'first_name', 'last_name', 'gender', 'phone', 'class_name', 'photo_url'];
    if ($headers && isset($headers[0])) {
        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string)$headers[0]);
    }
    if (!$headers || array_map(static fn($v) => strtolower(trim((string)$v)), $headers) !== $expected) {
        fclose($handle);
        json_response(['error' => 'CSV header must be: ' . implode(',', $expected)], 422);
    }
    $entries = [];
    $line = 1;
    while (($row = fgetcsv($handle)) !== false) {
        $line++;
        if ($row === [null] || count(array_filter($row, static fn($value) => trim((string)$value) !== '')) === 0) {
            continue;
        }
        if (count($row) !== count($expected)) {
            fclose($handle);
            json_response(['error' => "CSV row {$line} has an incorrect number of columns."], 422);
        }
        $entries[] = array_combine($expected, array_map(static fn($value) => trim((string)$value), $row));
        if (count($entries) > 500) {
            fclose($handle);
            json_response(['error' => 'Import is limited to 500 records per file.'], 413);
        }
    }
    fclose($handle);
    if (!$entries) {
        json_response(['error' => 'The CSV contains no student records.'], 422);
    }
    $validated = [];
    foreach ($entries as $index => $entry) {
        try {
            $validated[] = student_fields($entry);
        } catch (Throwable $exception) {
            json_response(['error' => 'CSV row ' . ($index + 2) . ': ' . $exception->getMessage()], 422);
        }
    }
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $insert = $pdo->prepare('INSERT IGNORE INTO students (student_code, first_name, last_name, gender, phone, class_name, photo_url) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $imported = 0;
        foreach ($validated as $values) {
            $insert->execute($values);
            $imported += $insert->rowCount();
        }
        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        error_log('Student CSV import failed: ' . $exception->getMessage());
        json_response(['error' => 'Import failed. No records were added.'], 500);
    }
    $skipped = count($validated) - $imported;
    log_activity('import', 'student', null, "Imported {$imported} student records; {$skipped} duplicates skipped.");
    json_response(['message' => "Imported {$imported} students; {$skipped} duplicate IDs skipped.", 'imported' => $imported, 'skipped' => $skipped], 201);
}

if ($method === 'POST') {
    verify_csrf();
    $values = student_fields(request_json());
    try {
        $statement = db()->prepare('INSERT INTO students (student_code, first_name, last_name, gender, phone, class_name, photo_url) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $statement->execute($values);
    } catch (PDOException $exception) {
        if ($exception->getCode() === '23000') {
            json_response(['error' => 'A student with that ID already exists.'], 409);
        }
        throw $exception;
    }
    $id = (int)db()->lastInsertId();
    log_activity('create', 'student', $id, "Added student {$values[0]}.");
    json_response(['message' => 'Student added.', 'id' => $id], 201);
}

$data = $method === 'PUT' ? request_json() : [];
$id = checked_id($_GET['id'] ?? ($data['id'] ?? null), 'Student ID');
if ($method === 'PUT') {
    verify_csrf();
    $values = student_fields($data);
    try {
        $statement = db()->prepare('UPDATE students SET student_code=?, first_name=?, last_name=?, gender=?, phone=?, class_name=?, photo_url=? WHERE id=?');
        $statement->execute([...$values, $id]);
    } catch (PDOException $exception) {
        if ($exception->getCode() === '23000') {
            json_response(['error' => 'A student with that ID already exists.'], 409);
        }
        throw $exception;
    }
    if ($statement->rowCount() === 0 && !student_exists($id)) {
        json_response(['error' => 'Student not found.'], 404);
    }
    log_activity('update', 'student', $id, "Updated student {$values[0]}.");
    json_response(['message' => 'Student updated.']);
}

verify_csrf();
$lookup = db()->prepare('SELECT student_code FROM students WHERE id=?');
$lookup->execute([$id]);
$code = $lookup->fetchColumn();
if ($code === false) {
    json_response(['error' => 'Student not found.'], 404);
}
$delete = db()->prepare('DELETE FROM students WHERE id=?');
$delete->execute([$id]);
log_activity('delete', 'student', $id, "Deleted student {$code}.");
json_response(['message' => 'Student deleted.']);

function student_exists(int $id): bool
{
    $statement = db()->prepare('SELECT 1 FROM students WHERE id=?');
    $statement->execute([$id]);
    return (bool)$statement->fetchColumn();
}
