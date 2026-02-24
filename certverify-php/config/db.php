<?php
// ============================================================
//  CertVerify — Database Configuration
//  File: config/db.php
//  Edit the constants below to match your MySQL setup.
// ============================================================

define('DB_HOST',     'localhost');   // MySQL host (usually localhost)
define('DB_PORT',     3306);          // MySQL port (default 3306)
define('DB_NAME',     'certverify_db');
define('DB_USER',     'root');        // ← Change to your MySQL username
define('DB_PASS',     '');            // ← Change to your MySQL password
define('DB_CHARSET',  'utf8mb4');

// Admin session key (keep secret, used to sign session data)
define('SESSION_SECRET', 'certverify_secret_2024_change_me');

// App settings
define('APP_NAME',    'CertVerify');
define('APP_VERSION', '1.0.0');

// ============================================================
//  Database connection (PDO — Singleton pattern)
// ============================================================
class DB {
    private static ?PDO $pdo = null;

    public static function conn(): PDO {
        if (self::$pdo === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
            );
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            try {
                self::$pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                http_response_code(503);
                die(json_encode([
                    'success' => false,
                    'error'   => 'Database connection failed. Check config/db.php settings.',
                    'detail'  => $e->getMessage()
                ]));
            }
        }
        return self::$pdo;
    }
}
?>
