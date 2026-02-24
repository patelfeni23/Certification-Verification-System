<?php
// ============================================================
//  CertVerify API — Stats & Dashboard
//  GET /api/stats.php  (Admin only)
// ============================================================

require_once __DIR__ . '/../config/helpers.php';
set_headers();
require_method('GET');
require_admin();

try {
    $pdo = DB::conn();

    // Overall counts
    $totals = $pdo->query(
        'SELECT
           (SELECT COUNT(*) FROM certificates)                     AS total_certs,
           (SELECT COUNT(DISTINCT domain) FROM certificates)       AS unique_domains,
           (SELECT COUNT(*) FROM verification_logs)                AS total_verifications,
           (SELECT COUNT(*) FROM verification_logs WHERE DATE(verified_at) = CURDATE()) AS today_verifications'
    )->fetch();

    // Certs per domain
    $byDomain = $pdo->query(
        'SELECT domain, COUNT(*) AS count
         FROM   certificates
         GROUP  BY domain
         ORDER  BY count DESC'
    )->fetchAll();

    // Recent verifications
    $recentVerifications = $pdo->query(
        'SELECT vl.cert_id, c.student_name, c.domain, vl.verified_at
         FROM   verification_logs vl
         LEFT   JOIN certificates c ON vl.cert_id = c.cert_id
         ORDER  BY vl.verified_at DESC
         LIMIT  10'
    )->fetchAll();

    // Recent certificates added
    $recentCerts = $pdo->query(
        'SELECT cert_id, student_name, domain, start_date, end_date, created_at
         FROM   certificates
         ORDER  BY created_at DESC
         LIMIT  10'
    )->fetchAll();
    foreach ($recentCerts as &$c) {
        $c['duration'] = get_duration($c['start_date'], $c['end_date']);
    }

    // Domains list
    $domains = $pdo->query('SELECT name FROM domains ORDER BY name')->fetchAll(PDO::FETCH_COLUMN);

    success([
        'totals'               => $totals,
        'by_domain'            => $byDomain,
        'recent_verifications' => $recentVerifications,
        'recent_certs'         => $recentCerts,
        'domains'              => $domains,
    ], 'Stats loaded');

} catch (PDOException $e) {
    error('Failed to load stats: ' . $e->getMessage(), 500);
}
?>
