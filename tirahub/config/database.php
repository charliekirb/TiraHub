<?php
// ============================================================
//  TiraHub – Database Configuration
// ============================================================

define('DB_HOST',    'localhost');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_NAME',    'tirahub_db');
define('DB_CHARSET', 'utf8mb4');

class Database {
    private static ?PDO $instance = null;

    public static function connect(): PDO {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                DB_HOST, DB_NAME, DB_CHARSET
            );
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_FOUND_ROWS   => true,
            ];
            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                error_log('DB Connection Error: ' . $e->getMessage());
                die(json_encode(['success' => false, 'message' => 'Database connection failed.']));
            }
        }
        return self::$instance;
    }

    // Call a stored procedure that returns OUT params
    public static function callProc(PDO $pdo, string $proc, array $inParams, array $outParams): array {
        $allParams   = array_merge($inParams, $outParams);
        $placeholders = implode(', ', $allParams);
        $pdo->exec("CALL {$proc}({$placeholders})");
        $row = $pdo->query("SELECT " . implode(', ', $outParams))->fetch();
        return $row ?: [];
    }
}
