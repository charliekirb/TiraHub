<?php
require_once '../config/auth.php';
require_once '../config/database.php';
header('Content-Type: application/json');
if (!isLoggedIn()) { echo json_encode(['count'=>0,'items'=>[]]); exit; }
$pdo    = Database::connect();
$userId = (int)$_SESSION['user_id'];
$stmt   = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0");
$stmt->execute([$userId]);
$count  = (int)$stmt->fetchColumn();
if (isset($_GET['fetch'])) {
    $stmt2 = $pdo->prepare("SELECT notification_id,title,message,type,is_read,DATE_FORMAT(created_at,'%b %d %H:%i') AS created_at FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 6");
    $stmt2->execute([$userId]);
    $items = $stmt2->fetchAll();
    // Mark as read
    $pdo->prepare("UPDATE notifications SET is_read=1 WHERE user_id=? AND is_read=0")->execute([$userId]);
    echo json_encode(['count'=>0,'items'=>$items]);
} else {
    echo json_encode(['count'=>$count]);
}
