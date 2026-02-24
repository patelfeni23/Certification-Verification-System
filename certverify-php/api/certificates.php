<?php
// ============================================================
//  CertVerify API — Certificates CRUD
//  All routes REQUIRE admin session.
//
//  GET    /api/certificates.php              → list all
//  GET    /api/certificates.php?id=CERT-XXX → get one
//  POST   /api/certificates.php              → create
//  PUT    /api/certificates.php              → update
//  DELETE /api/certificates.php?id=CERT-XXX → delete
// ============================================================

require_once __DIR__ . '/../config/helpers.php';
set_headers();
require_admin();

$pdo    = DB::conn();
$method = $_SERVER['REQUEST_METHOD'];

// ============================================================
// GET — list or single
// ============================================================
if ($method === 'GET') {
    $id = strtoupper(trim($_GET['id'] ?? ''));

    // ---- Single certificate ----
    if ($id !== '') {
        $stmt = $pdo->prepare(
            'SELECT c.*, a.username AS issued_by_name
             FROM   certificates c
             LEFT   JOIN admins a ON c.issued_by = a.id
             WHERE  c.cert_id = ?'
        );
        $stmt->execute([$id]);
        $cert = $stmt->fetch();
        if (!$cert) error("Certificate {$id} not found.", 404);
        $cert['duration'] = get_duration($cert['start_date'], $cert['end_date']);
        success($cert, 'Certificate found');
    }

    // ---- List with optional search & filter ----
    $search = trim($_GET['search'] ?? '');
    $domain = trim($_GET['domain'] ?? '');
    $page   = max(1, (int)($_GET['page'] ?? 1));
    $limit  = min(100, max(1, (int)($_GET['limit'] ?? 50)));
    $offset = ($page - 1) * $limit;

    $where  = [];
    $params = [];

    if ($search !== '') {
        $where[]  = '(c.cert_id LIKE ? OR c.student_name LIKE ? OR c.domain LIKE ?)';
        $like     = "%{$search}%";
        $params   = array_merge($params, [$like, $like, $like]);
    }
    if ($domain !== '') {
        $where[]  = 'c.domain = ?';
        $params[] = $domain;
    }

    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    // Count total
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM certificates c {$whereSQL}");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    // Fetch page
    $stmt = $pdo->prepare(
        "SELECT c.cert_id, c.student_name, c.domain, c.start_date, c.end_date, c.created_at,
                a.username AS issued_by_name
         FROM   certificates c
         LEFT   JOIN admins a ON c.issued_by = a.id
         {$whereSQL}
         ORDER  BY c.created_at DESC
         LIMIT  {$limit} OFFSET {$offset}"
    );
    $stmt->execute($params);
    $certs = $stmt->fetchAll();

    // Add duration to each
    foreach ($certs as &$c) {
        $c['duration'] = get_duration($c['start_date'], $c['end_date']);
    }

    success([
        'certificates' => $certs,
        'pagination'   => [
            'total'       => $total,
            'page'        => $page,
            'limit'       => $limit,
            'total_pages' => ceil($total / $limit),
        ],
    ], 'Certificates fetched');
}

// ============================================================
// POST — create
// ============================================================
if ($method === 'POST') {
    $body    = get_json_body();
    $cert_id = strtoupper(sanitize($body['cert_id'] ?? ''));
    $name    = sanitize($body['student_name'] ?? '');
    $domain  = sanitize($body['domain'] ?? '');
    $start   = sanitize($body['start_date'] ?? '');
    $end     = sanitize($body['end_date'] ?? '');

    // Validation
    if (!$cert_id || !$name || !$domain || !$start || !$end)
        error('All fields are required: cert_id, student_name, domain, start_date, end_date.', 400);
    if (!validate_cert_id($cert_id))
        error('Certificate ID format invalid. Use uppercase letters, numbers, and hyphens.', 400);
    if (!validate_date($start) || !validate_date($end))
        error('Dates must be in YYYY-MM-DD format.', 400);
    if ($end <= $start)
        error('end_date must be after start_date.', 400);

    // Duplicate check
    $dup = $pdo->prepare('SELECT 1 FROM certificates WHERE cert_id = ?');
    $dup->execute([$cert_id]);
    if ($dup->fetchColumn()) error("Certificate ID {$cert_id} already exists.", 409);

    // Insert
    session_start_safe();
    $admin_id = $_SESSION['admin_id'];

    $stmt = $pdo->prepare(
        'INSERT INTO certificates (cert_id, student_name, domain, start_date, end_date, issued_by)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$cert_id, $name, $domain, $start, $end, $admin_id]);

    success([
        'cert_id'      => $cert_id,
        'student_name' => $name,
        'domain'       => $domain,
        'start_date'   => $start,
        'end_date'     => $end,
        'duration'     => get_duration($start, $end),
    ], "Certificate {$cert_id} created successfully");
}

// ============================================================
// PUT — update
// ============================================================
if ($method === 'PUT') {
    $body    = get_json_body();
    $cert_id = strtoupper(sanitize($body['cert_id'] ?? ''));
    $name    = sanitize($body['student_name'] ?? '');
    $domain  = sanitize($body['domain'] ?? '');
    $start   = sanitize($body['start_date'] ?? '');
    $end     = sanitize($body['end_date'] ?? '');

    if (!$cert_id) error('cert_id is required.', 400);

    // Check exists
    $exists = $pdo->prepare('SELECT 1 FROM certificates WHERE cert_id = ?');
    $exists->execute([$cert_id]);
    if (!$exists->fetchColumn()) error("Certificate {$cert_id} not found.", 404);

    // Build dynamic update
    $sets   = [];
    $params = [];

    if ($name) { $sets[] = 'student_name = ?'; $params[] = $name; }
    if ($domain){ $sets[] = 'domain = ?';       $params[] = $domain; }
    if ($start) {
        if (!validate_date($start)) error('Invalid start_date format.', 400);
        $sets[] = 'start_date = ?'; $params[] = $start;
    }
    if ($end) {
        if (!validate_date($end)) error('Invalid end_date format.', 400);
        $sets[] = 'end_date = ?'; $params[] = $end;
    }
    if (!$sets) error('No fields provided to update.', 400);

    // Date cross-check
    if ($start && $end && $end <= $start) error('end_date must be after start_date.', 400);

    $params[] = $cert_id;
    $pdo->prepare('UPDATE certificates SET ' . implode(', ', $sets) . ' WHERE cert_id = ?')
        ->execute($params);

    // Fetch updated record
    $row = $pdo->prepare('SELECT * FROM certificates WHERE cert_id = ?');
    $row->execute([$cert_id]);
    $updated = $row->fetch();
    $updated['duration'] = get_duration($updated['start_date'], $updated['end_date']);

    success($updated, "Certificate {$cert_id} updated successfully");
}

// ============================================================
// DELETE
// ============================================================
if ($method === 'DELETE') {
    $id = strtoupper(trim($_GET['id'] ?? ''));
    if (!$id) error('Certificate ID required as query param ?id=CERT-XXX', 400);

    $exists = $pdo->prepare('SELECT 1 FROM certificates WHERE cert_id = ?');
    $exists->execute([$id]);
    if (!$exists->fetchColumn()) error("Certificate {$id} not found.", 404);

    $pdo->prepare('DELETE FROM certificates WHERE cert_id = ?')->execute([$id]);

    success(['cert_id' => $id], "Certificate {$id} deleted successfully");
}

error('Method not supported', 405);
?>
