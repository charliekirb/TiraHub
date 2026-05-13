<?php
// ============================================================
//  TiraHub – Logout
// ============================================================
require_once 'config/auth.php';

if (isLoggedIn()) {
    require_once 'config/database.php';
    $pdo = Database::connect();
    $pdo->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id, ip_address) VALUES (?,?,?,?,?)")
        ->execute([$_SESSION['user_id'], 'LOGOUT', 'users', $_SESSION['user_id'], getClientIP()]);
}

logout();
header('Location: index.php');
exit;
