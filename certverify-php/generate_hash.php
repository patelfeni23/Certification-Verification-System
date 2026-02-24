<?php
// ============================================================
//  CertVerify — Password Hash Generator
//  Run once in browser: http://localhost/certverify/generate_hash.php
//  Then DELETE this file from your server!
// ============================================================

$password  = 'Admin@123';      // ← Change to your desired password
$hash      = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

echo "<pre style='font-family:monospace;font-size:14px;padding:30px'>";
echo "Password  : {$password}\n";
echo "Hash      : {$hash}\n\n";
echo "-- Run this SQL to update the admin password:\n";
echo "UPDATE admins SET password = '{$hash}' WHERE username = 'admin';\n";
echo "</pre>";
echo "<p style='padding:0 30px;color:red;font-weight:bold'>⚠ DELETE this file after use!</p>";
?>
