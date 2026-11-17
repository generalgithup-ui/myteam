<?php
require_once __DIR__ . '/../includes/bootstrap.php';
set_exception_handler(static function (Throwable $exception): void {
    error_log('API request failed: ' . $exception->getMessage());
    json_response(['error' => 'The request could not be completed. Please try again.'], 500);
});

function log_activity(string $action, string $entity, ?int $entityId, string $description): void
{
    $admin = current_admin();
    $statement = db()->prepare(
        'INSERT INTO activity_logs (admin_id, action, entity_type, entity_id, description) VALUES (?, ?, ?, ?, ?)'
    );
    $statement->execute([$admin['id'] ?? null, $action, $entity, $entityId, mb_substr($description, 0, 255)]);
}

function text_field(array $data, string $key, int $maxLength, bool $required = true): ?string
{
    $raw = $data[$key] ?? '';
    if (!is_scalar($raw)) {
        json_response(['error' => ucfirst(str_replace('_', ' ', $key)) . ' must be a text value.'], 422);
    }
    $value = trim((string)$raw);
    if ($value === '') {
        if ($required) {
            json_response(['error' => ucfirst(str_replace('_', ' ', $key)) . ' is required.'], 422);
        }
        return null;
    }
    if (mb_strlen($value) > $maxLength) {
        json_response(['error' => ucfirst(str_replace('_', ' ', $key)) . " must be {$maxLength} characters or fewer."], 422);
    }
    return $value;
}

function student_fields(array $data): array
{
    $code = text_field($data, 'student_code', 40);
    $first = text_field($data, 'first_name', 100);
    $last = text_field($data, 'last_name', 100);
    $class = text_field($data, 'class_name', 80);
    $gender = text_field($data, 'gender', 30);
    $allowed = ['Female', 'Male', 'Other', 'Prefer not to say'];
    if (!in_array($gender, $allowed, true)) {
        json_response(['error' => 'Select a valid gender.'], 422);
    }
    $phone = text_field($data, 'phone', 40, false);
    $photo = text_field($data, 'photo_url', 500, false);
    $photoScheme = $photo === null ? null : strtolower((string)parse_url($photo, PHP_URL_SCHEME));
    if ($photo !== null && (!filter_var($photo, FILTER_VALIDATE_URL) || !in_array($photoScheme, ['http', 'https'], true))) {
        json_response(['error' => 'Photo URL must be a valid absolute URL.'], 422);
    }
    return [$code, $first, $last, $gender, $phone, $class, $photo];
}

function checked_id(mixed $value, string $label): int
{
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false) {
        json_response(['error' => "{$label} must be a positive integer."], 422);
    }
    return (int)$id;
}
