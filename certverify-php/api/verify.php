<?php
// ============================================================
//  CertVerify API — Verify Certificate
//  Endpoint : GET  /api/verify.php?id=CERT-2024-001
//  Public   : Yes (no auth required)
// ============================================================

require_once __DIR__ . '/../config/helpers.php';
set_headers();
require_method('GET');

// ---- Get & validate ID ----
$raw_id = trim($_GET['id'] ?? '');
if ($raw_id === '') {
    error('Certificate ID is required. Pass ?id=CERT-XXXX-XXX in the URL.', 400);
}

$cert_id = strtoupper($raw_id);

if (!validate_cert_id($cert_id)) {
    error('Invalid Certificate ID format.', 400);
}

// ---- Query database ----
try {
    $pdo  = DB::conn();
    $stmt = $pdo->prepare(
        'SELECT cert_id, student_name, domain, start_date, end_date, created_at
         FROM   certificates
         WHERE  cert_id = ?
         LIMIT  1'
    );
    $stmt->execute([$cert_id]);
    $cert = $stmt->fetch();

} catch (PDOException $e) {
    error('Database error occurred.', 500);
}

// ---- Not found ----
if (!$cert) {
    error("No certificate found with ID: {$cert_id}", 404);
}

// ---- Log the verification ----
log_verification($cert_id);

// ---- Build response ----
$duration = get_duration($cert['start_date'], $cert['end_date']);

success([
    'cert_id'      => $cert['cert_id'],
    'student_name' => $cert['student_name'],
    'domain'       => $cert['domain'],
    'start_date'   => $cert['start_date'],
    'end_date'     => $cert['end_date'],
    'duration'     => $duration,
    'issued_on'    => date('Y-m-d', strtotime($cert['created_at'])),
    'verified_at'  => date('Y-m-d H:i:s'),
], 'Certificate verified successfully');
?>
