<?php
require_once '../config/db_connect.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$username = $_POST['username'] ?? '';

if($action == 'login') {
    $token = bin2hex(random_bytes(16));
    // INSERT ή UPDATE αν υπάρχει ήδη ο χρήστης
    $stmt = $pdo->prepare("INSERT INTO players (username, token) VALUES (?, ?) ON DUPLICATE KEY UPDATE token=?");
    $stmt->execute([$username, $token, $token]);
    
    echo json_encode(['status' => 'success', 'token' => $token]);
}
?>