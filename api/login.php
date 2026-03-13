<?php
require_once '../config/db_connect.php';
require_once 'start_game.php'; 

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$username = $_POST['username'] ?? '';

try {
    if ($action == 'create') {
        $game_id = rand(1, 10000);
        $token = bin2hex(random_bytes(16));

        // Δημιουργία παιχνιδιού
        $pdo->prepare("INSERT INTO board (game_id, status) VALUES (?, 'waiting')")->execute([$game_id]);
        
        // Εισαγωγή ή ενημέρωση παίκτη P1
        $stmt = $pdo->prepare("INSERT INTO players (username, game_id, player_side, token, score, cards_collected) 
                               VALUES (?, ?, 'P1', ?, 0, 0) 
                               ON DUPLICATE KEY UPDATE game_id = VALUES(game_id), player_side = 'P1', token = VALUES(token), score = 0, cards_collected = 0");
        $stmt->execute([$username, $game_id, $token]);

        echo json_encode(['status' => 'success', 'game_id' => $game_id, 'token' => $token]);

    } elseif ($action == 'join') {
        $game_id = $_POST['game_id'] ?? 0;
        $token = bin2hex(random_bytes(16));

        $stmt = $pdo->prepare("SELECT status FROM board WHERE game_id = ?");
        $stmt->execute([$game_id]);
        $game = $stmt->fetch();

        if ($game && $game['status'] == 'waiting') {
            // Εισαγωγή ή ενημέρωση παίκτη P2
            $stmt = $pdo->prepare("INSERT INTO players (username, game_id, player_side, token, score, cards_collected) 
                                   VALUES (?, ?, 'P2', ?, 0, 0) 
                                   ON DUPLICATE KEY UPDATE game_id = VALUES(game_id), player_side = 'P2', token = VALUES(token), score = 0, cards_collected = 0");
            $stmt->execute([$username, $game_id, $token]);
            
            initializeGame($pdo, $game_id);

            $stmt = $pdo->prepare("SELECT username FROM players WHERE game_id = ? AND player_side = 'P1'");
            $stmt->execute([$game_id]);
            $p1_name = $stmt->fetch()['username'];

            $pdo->prepare("UPDATE board SET status = 'playing', player_turn = ? WHERE game_id = ?")->execute([$p1_name, $game_id]);

            echo json_encode(['status' => 'success', 'game_id' => $game_id, 'token' => $token]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Αδύνατη η σύνδεση. Το παιχνίδι δεν είναι διαθέσιμο.']);
        }
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>