<?php
// ============================================================
//  TiraHub – Auth & Session Helpers
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => false,   // set true on HTTPS production
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

function isAdmin(): bool {
    return isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin';
}

function isStudent(): bool {
    return isLoggedIn() && ($_SESSION['role'] ?? '') === 'student';
}

function requireLogin(string $redirect = '../index.php'): void {
    if (!isLoggedIn()) {
        header("Location: {$redirect}");
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ../student/dashboard.php');
        exit;
    }
}

function requireStudent(): void {
    requireLogin();
    if (!isStudent()) {
        header('Location: ../admin/dashboard.php');
        exit;
    }
}

function setSession(array $user, int $studentId = 0): void {
    session_regenerate_id(true);
    $_SESSION['user_id']    = (int)$user['user_id'];
    $_SESSION['username']   = $user['username'];
    $_SESSION['email']      = $user['email'];
    $_SESSION['role']       = $user['role'];
    $_SESSION['student_id'] = $studentId;
}

function logout(): void {
    $_SESSION = [];
    session_destroy();
}

function flashMessage(string $key, string $message, string $type = 'success'): void {
    $_SESSION['flash'][$key] = ['message' => $message, 'type' => $type];
}

function getFlash(string $key): ?array {
    if (isset($_SESSION['flash'][$key])) {
        $flash = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $flash;
    }
    return null;
}

function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function jsonResponse(bool $success, string $message, array $data = []): void {
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

function getClientIP(): string {
    return $_SERVER['HTTP_X_FORWARDED_FOR']
        ?? $_SERVER['REMOTE_ADDR']
        ?? '0.0.0.0';
}

function unreadNotifCount(PDO $pdo, int $userId): int {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}
