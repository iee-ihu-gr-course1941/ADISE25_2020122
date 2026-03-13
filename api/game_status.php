<?php
require_once '../config/db_connect.php';
header('Content-Type: application/json');

$game_id = $_GET['game_id'] ?? 0;
$token = $_GET['token'] ?? '';

try {
    // Λήψη κατάστασης και στοιχείων παίκτη
    $stmt = $pdo->prepare("SELECT b.*, p.player_side, p.username, p.score, p.cards_collected 
                           FROM board b 
                           JOIN players p ON b.game_id = p.game_id 
                           WHERE p.token = ? AND b.game_id = ?");
    $stmt->execute([$token, $game_id]);
    $state = $stmt->fetch();

    if (!$state) { 
        die(json_encode(['status' => 'error', 'message' => 'Session expired'])); 
    }

    // Έλεγχος Deadlock και αυτόματο μοίρασμα
    $stmt_check = $pdo->prepare("SELECT COUNT(*) as in_hand FROM cards WHERE game_id = ? AND location IN ('P1_hand', 'P2_hand')");
    $stmt_check->execute([$game_id]);
    $cards_in_hand = $stmt_check->fetch()['in_hand'];
    
    $stmt_deck_count = $pdo->prepare("SELECT COUNT(*) as rem FROM cards WHERE game_id = ? AND location = 'deck'");
    $stmt_deck_count->execute([$game_id]);
    $remaining_deck = $stmt_deck_count->fetch()['rem'];

    if ($cards_in_hand == 0 && $state['status'] == 'playing') {
        if ($remaining_deck > 0) {
            $stmt_deck = $pdo->prepare("SELECT id FROM cards WHERE game_id = ? AND location = 'deck' LIMIT 12");
            $stmt_deck->execute([$game_id]);
            $next_cards = $stmt_deck->fetchAll();
            foreach ($next_cards as $index => $c) {
                $loc = ($index < 6) ? 'P1_hand' : 'P2_hand';
                $pdo->prepare("UPDATE cards SET location = ? WHERE id = ?")->execute([$loc, $c['id']]);
            }
        } else {
            // Υπολογισμός Bonus +3 πόντων (Πρόταση 3)
            $stmt_all = $pdo->prepare("SELECT username, cards_collected FROM players WHERE game_id = ?");
            $stmt_all->execute([$game_id]);
            $players = $stmt_all->fetchAll();
            if (count($players) == 2) {
                $p1_cards = $players[0]['cards_collected'];
                $p2_cards = $players[1]['cards_collected'];
                if ($p1_cards > $p2_cards) {
                    $pdo->prepare("UPDATE players SET score = score + 3 WHERE username = ?")->execute([$players[0]['username']]);
                } elseif ($p2_cards > $p1_cards) {
                    $pdo->prepare("UPDATE players SET score = score + 3 WHERE username = ?")->execute([$players[1]['username']]);
                }
            }
            $pdo->prepare("UPDATE board SET status = 'ended' WHERE game_id = ?")->execute([$game_id]);
            $state['status'] = 'ended';
        }
    }

    $table = $pdo->prepare("SELECT id, suit, rank FROM cards WHERE game_id = ? AND location = 'table' ORDER BY table_order ASC");
    $table->execute([$game_id]);

    $hand_loc = ($state['player_side'] == 'P1') ? 'P1_hand' : 'P2_hand';
    $hand = $pdo->prepare("SELECT id, suit, rank FROM cards WHERE game_id = ? AND location = ?");
    $hand->execute([$game_id, $hand_loc]);

    // Λήψη Log (Πρόταση 3)
    $log = $pdo->prepare("SELECT action_text FROM game_log WHERE game_id = ? ORDER BY id DESC LIMIT 5");
    $log->execute([$game_id]);

    echo json_encode([
    'status' => $state['status'],
    'player_turn' => $state['player_turn'],
    'score' => $state['score'],
    'cards_collected' => $state['cards_collected'],
    'remaining_deck' => $remaining_deck,
    'table' => $table->fetchAll(),
    'hand' => $hand->fetchAll(),
    'log' => $log->fetchAll(PDO::FETCH_COLUMN)
]);
} catch (Exception $e) { 
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); 
}