<?php
require_once '../config/db_connect.php';
require_once 'start_game.php'; 
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$username = $_POST['username'] ?? '';
$game_id = $_POST['game_id'] ?? null;

try {
    if ($action == 'create') {
        $new_id = rand(1, 10000);
        $pdo->prepare("INSERT INTO board (game_id, status) VALUES (?, 'waiting')")->execute([$new_id]);
        $pdo->prepare("UPDATE players SET game_id = ?, player_side = 'P1', score = 0, cards_collected = 0 WHERE username = ?")
            ->execute([$new_id, $username]);
        echo json_encode(['status' => 'success', 'game_id' => $new_id]);

    } elseif ($action == 'join') {
        $stmt = $pdo->prepare("SELECT status FROM board WHERE game_id = ?");
        $stmt->execute([$game_id]);
        $game = $stmt->fetch();

        if (!$game || $game['status'] !== 'waiting') {
            echo json_encode(['status' => 'error', 'message' => 'Αδύνατη η σύνδεση.']);
        } else {
            $pdo->prepare("UPDATE players SET game_id = ?, player_side = 'P2', score = 0, cards_collected = 0 WHERE username = ?")
                ->execute([$game_id, $username]);
            
            initializeGame($pdo, $game_id);

            $stmt = $pdo->prepare("SELECT username FROM players WHERE game_id = ? AND player_side = 'P1'");
            $stmt->execute([$game_id]);
            $p1 = $stmt->fetch()['username'];

            $pdo->prepare("UPDATE board SET status = 'playing', player_turn = ? WHERE game_id = ?")->execute([$p1, $game_id]);
            echo json_encode(['status' => 'success', 'game_id' => $game_id]);
        }

    } elseif ($action == 'forfeit') {
        $action_msg = "Ο παίκτης $username εγκατέλειψε το παιχνίδι.";
        $pdo->prepare("INSERT INTO game_log (game_id, username, action_text) VALUES (?, ?, ?)")
            ->execute([$game_id, $username, $action_msg]);
            
        $pdo->prepare("UPDATE board SET status = 'ended' WHERE game_id = ?")->execute([$game_id]);
        echo json_encode(['status' => 'success', 'message' => 'Εγκαταλείψατε το παιχνίδι.']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}