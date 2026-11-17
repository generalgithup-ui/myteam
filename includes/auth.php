<?php
declare(strict_types=1);

function start_app_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $secure,
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

function csrf_token(): string
{
    start_app_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    start_app_session();
    $provided = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
    if (!is_string($provided) || !hash_equals(csrf_token(), $provided)) {
        http_response_code(419);
        if (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Your session token expired. Refresh the page and try again.']);
        } else {
            exit('Your session token expired. Refresh the page and try again.');
        }
        exit;
    }
}

function current_admin(): ?array
{
    start_app_session();
    return isset($_SESSION['admin']) && is_array($_SESSION['admin']) ? $_SESSION['admin'] : null;
}

function require_admin(bool $allowPasswordChange = false): array
{
    $admin = current_admin();
    if (!$admin) {
        if (str_contains(str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? ''), '/api/')) {
            json_response(['error' => 'Authentication required.'], 401);
        }
        header('Location: ' . app_url('/login.php'));
        exit;
    }
    if (!$allowPasswordChange && !empty($admin['must_change_password'])) {
        header('Location: ' . app_url('/admin/settings.php?change_password=1'));
        exit;
    }
    return $admin;
}

function app_base_path(): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $directory = trim(str_replace('\\', '/', dirname($script)), '/.');
    $parts = $directory === '' ? [] : explode('/', $directory);
    if ($parts && in_array(end($parts), ['admin', 'student', 'api'], true)) {
        array_pop($parts);
    }
    return $parts ? '/' . implode('/', $parts) : '';
}

function app_url(string $path): string
{
    return app_base_path() . '/' . ltrim($path, '/');
}

function current_ip_hash(): string
{
    return hash('sha256', (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
}

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function request_json(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '', true);
    if (!is_array($data)) {
        json_response(['error' => 'A valid JSON request body is required.'], 400);
    }
    return $data;
}

function require_method(string ...$methods): string
{
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (!in_array($method, $methods, true)) {
        header('Allow: ' . implode(', ', $methods));
        json_response(['error' => 'Method not allowed.'], 405);
    }
    return $method;
}

function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
