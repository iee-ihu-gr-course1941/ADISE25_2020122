<?php
require_once '../config/db_connect.php';
session_start();
$token = $_POST['token'] ?? '';

// Διαγραφή παίκτη (προαιρετικά μπορείς να ακυρώσεις και το παιχνίδι αν βγει ο ένας)
$stmt = $pdo->prepare("DELETE FROM players WHERE token = ?");
$stmt->execute([$token]);

echo json_encode(['status' => 'success']);
?>