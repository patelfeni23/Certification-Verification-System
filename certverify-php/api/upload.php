<?php
// ============================================================
//  CertVerify API — Bulk Upload (CSV / Excel via CSV export)
//  POST /api/upload.php   multipart/form-data  field: file
//  Admin only.
//
//  Expected CSV columns (row 1 = headers):
//  cert_id | student_name | domain | start_date | end_date
// ============================================================

require_once __DIR__ . '/../config/helpers.php';
set_headers();
require_method('POST');
require_admin();

if (empty($_FILES['file'])) {
    error('No file uploaded. Use field name: file', 400);
}

$file     = $_FILES['file'];
$ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed  = ['csv', 'txt'];

if (!in_array($ext, $allowed, true)) {
    error('Only CSV files are accepted. Export your Excel as .csv first.', 400);
}
if ($file['error'] !== UPLOAD_ERR_OK) {
    error('File upload error code: ' . $file['error'], 400);
}
if ($file['size'] > 5 * 1024 * 1024) {
    error('File too large. Max 5 MB.', 400);
}

// ---- Parse CSV ----
$handle = fopen($file['tmp_name'], 'r');
if (!$handle) error('Could not read uploaded file.', 500);

// Read headers
$headers = fgetcsv($handle);
if (!$headers) { fclose($handle); error('CSV file is empty.', 400); }

// Normalize header names
$headers = array_map(fn($h) => strtolower(trim(str_replace([' ', '-'], '_', $h))), $headers);

$required = ['cert_id', 'student_name', 'domain', 'start_date', 'end_date'];
foreach ($required as $req) {
    if (!in_array($req, $headers, true)) {
        fclose($handle);
        error("Missing required column: {$req}. Required columns: " . implode(', ', $required), 400);
    }
}

$idxOf = array_flip($headers);

// ---- Process rows ----
$pdo     = DB::conn();
$session_start_safe = true;
session_start_safe();
$admin_id = $_SESSION['admin_id'];

$inserted = 0;
$skipped  = 0;
$errors   = [];
$row_num  = 1; // header was row 0

$insertStmt = $pdo->prepare(
    'INSERT IGNORE INTO certificates (cert_id, student_name, domain, start_date, end_date, issued_by)
     VALUES (?, ?, ?, ?, ?, ?)'
);

while (($row = fgetcsv($handle)) !== false) {
    $row_num++;
    if (array_filter($row) === []) continue; // skip blank lines

    $cert_id = strtoupper(sanitize($row[$idxOf['cert_id']] ?? ''));
    $name    = sanitize($row[$idxOf['student_name']] ?? '');
    $domain  = sanitize($row[$idxOf['domain']] ?? '');
    $start   = sanitize($row[$idxOf['start_date']] ?? '');
    $end     = sanitize($row[$idxOf['end_date']] ?? '');

    // Row validation
    if (!$cert_id || !$name || !$domain || !$start || !$end) {
        $errors[] = "Row {$row_num}: Missing required fields — skipped.";
        $skipped++;
        continue;
    }
    if (!validate_cert_id($cert_id)) {
        $errors[] = "Row {$row_num}: Invalid cert_id format '{$cert_id}' — skipped.";
        $skipped++;
        continue;
    }
    if (!validate_date($start) || !validate_date($end)) {
        $errors[] = "Row {$row_num}: Invalid date format for {$cert_id} — skipped.";
        $skipped++;
        continue;
    }
    if ($end <= $start) {
        $errors[] = "Row {$row_num}: end_date must be after start_date for {$cert_id} — skipped.";
        $skipped++;
        continue;
    }

    try {
        $insertStmt->execute([$cert_id, $name, $domain, $start, $end, $admin_id]);
        if ($insertStmt->rowCount() > 0) {
            $inserted++;
        } else {
            $errors[] = "Row {$row_num}: Duplicate ID {$cert_id} — skipped.";
            $skipped++;
        }
    } catch (PDOException $e) {
        $errors[] = "Row {$row_num}: DB error for {$cert_id}: " . $e->getMessage();
        $skipped++;
    }
}

fclose($handle);

success([
    'inserted' => $inserted,
    'skipped'  => $skipped,
    'errors'   => $errors,
    'total_rows_processed' => $row_num - 1,
], "{$inserted} certificate(s) imported successfully. {$skipped} skipped.");
?>
