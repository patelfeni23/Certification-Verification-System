<?php
// ============================================================
//  CertVerify API — Admin Authentication
//
//  POST /api/auth.php          Body: {action, username, password}
//  Actions: login | logout | check
// ============================================================

require_once __DIR__ . '/../config/helpers.php';
set_headers();
require_method('POST', 'GET');

// ---- GET: session check ----
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    session_start_safe();
    success(['logged_in' => is_admin_logged_in()], 'Session status');
}

// ---- POST ----
$body   = get_json_body();
$action = $body['action'] ?? '';

// ---- LOGIN ----
if ($action === 'login') {
    $username = sanitize($body['username'] ?? '');
    $password = $body['password'] ?? '';

    if ($username === '' || $password === '') {
        error('Username and password are required.', 400);
    }

    try {
        $pdo  = DB::conn();
        $stmt = $pdo->prepare('SELECT id, username, email, password FROM admins WHERE username = ? OR email = ? LIMIT 1');
        $stmt->execute([$username, $username]);
        $admin = $stmt->fetch();
    } catch (PDOException $e) {
        error('Database error.', 500);
    }

    if (!$admin || !password_verify($password, $admin['password'])) {
        // Slow down brute force
        sleep(1);
        error('Invalid username or password.', 401);
    }

    // Create session
    session_start_safe();
    session_regenerate_id(true);
    $_SESSION['admin_id']    = $admin['id'];
    $_SESSION['admin_user']  = $admin['username'];
    $_SESSION['admin_token'] = bin2hex(random_bytes(16));

    success([
        'admin_id'  => $admin['id'],
        'username'  => $admin['username'],
        'email'     => $admin['email'],
    ], 'Login successful');
}

// ---- LOGOUT ----
if ($action === 'logout') {
    session_start_safe();
    $_SESSION = [];
    session_destroy();
    success([], 'Logged out successfully');
}

error('Unknown action. Valid actions: login, logout, check', 400);
?>
