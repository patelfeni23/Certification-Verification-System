<?php
// ============================================================
//  CertVerify — Helper Functions
//  File: config/helpers.php
// ============================================================

require_once __DIR__ . '/db.php';

// ---- Response helpers ----

function json_response(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function success(array $data = [], string $message = 'OK'): void {
    json_response(['success' => true, 'message' => $message, 'data' => $data]);
}

function error(string $message, int $code = 400, array $extra = []): void {
    json_response(array_merge(['success' => false, 'error' => $message], $extra), $code);
}

// ---- CORS & common headers ----

function set_headers(): void {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Content-Type: application/json; charset=utf-8');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

// ---- Input helpers ----

function get_json_body(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function require_method(string ...$methods): void {
    if (!in_array($_SERVER['REQUEST_METHOD'], $methods, true)) {
        error('Method not allowed. Expected: ' . implode(', ', $methods), 405);
    }
}

function sanitize(string $str): string {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

// ---- Validation ----

function validate_cert_id(string $id): bool {
    // Allows formats like: CERT-2024-001, INT-001, CV-2024-ABC
    return (bool) preg_match('/^[A-Z0-9][A-Z0-9\-]{1,48}[A-Z0-9]$/i', $id);
}

function validate_date(string $date): bool {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

// ---- Session / Auth ----

function session_start_safe(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name('cv_sess');
        session_set_cookie_params([
            'lifetime' => 7200,
            'path'     => '/',
            'secure'   => false,   // Set true if HTTPS
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function is_admin_logged_in(): bool {
    session_start_safe();
    return !empty($_SESSION['admin_id']) && !empty($_SESSION['admin_token']);
}

function require_admin(): void {
    if (!is_admin_logged_in()) {
        error('Unauthorized. Please login as admin.', 401);
    }
}

// ---- Logging ----

function log_verification(string $cert_id): void {
    try {
        $pdo = DB::conn();
        $stmt = $pdo->prepare(
            'INSERT INTO verification_logs (cert_id, ip_address, user_agent) VALUES (?, ?, ?)'
        );
        $stmt->execute([
            strtoupper($cert_id),
            $_SERVER['REMOTE_ADDR']     ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        ]);
    } catch (Throwable $e) {
        // Non-critical — don't break the response
    }
}

// ---- Duration helper ----

function get_duration(string $start, string $end): string {
    $s = new DateTime($start);
    $e = new DateTime($end);
    $diff = $s->diff($e);
    if ($diff->m > 0 || $diff->y > 0) {
        $months = $diff->y * 12 + $diff->m;
        return $months . ' Month' . ($months > 1 ? 's' : '');
    }
    return $diff->days . ' Days';
}
?>
