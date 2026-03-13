<?php
require_once '../config/db_connect.php';
header('Content-Type: application/json');

// Λήψη δεδομένων από το POST
$cid = $_POST['card_id'] ?? null; 
$token = $_POST['token'] ?? ''; 
$gid = $_POST['game_id'] ?? 0;

if (!$cid || !$token || !$gid) {
    die(json_encode(['status' => 'error', 'message' => 'Ελλιπή στοιχεία αιτήματος.']));
}

try {
    // 1. Έλεγχος Παίκτη, Σειράς και Σκορ
    $stmt = $pdo->prepare("SELECT b.*, p.player_side, p.username, p.score FROM board b 
                           JOIN players p ON b.game_id = p.game_id 
                           WHERE p.token = ? AND b.game_id = ?");
    $stmt->execute([$token, $gid]); 
    $p = $stmt->fetch();

    if (!$p || $p['player_turn'] != $p['username']) {
        die(json_encode(['status' => 'error', 'message' => 'Δεν είναι η σειρά σου!']));
    }

    // 2. Λήψη στοιχείων κάρτας που παίζεται και της τελευταίας στο τραπέζι
    $stmt = $pdo->prepare("SELECT * FROM cards WHERE id = ?"); 
    $stmt->execute([$cid]); 
    $card = $stmt->fetch();

    $stmt = $pdo->prepare("SELECT * FROM cards WHERE game_id = ? AND location = 'table' 
                           ORDER BY table_order DESC LIMIT 1");
    $stmt->execute([$gid]); 
    $last = $stmt->fetch();

    $captured = ($last && ($card['rank'] == $last['rank'] || $card['rank'] == 'J'));
    $points_added = 0;
    $captured_count = 0;
    $card_name = $card['rank'] . $card['suit'];

    if ($captured) {
        // Υπολογισμός Πόντων Αξίας (Α, Κ, Q, J, 10, 10♦, 2♣)
        $stmt = $pdo->prepare("SELECT rank, suit FROM cards WHERE (game_id = ? AND location = 'table') OR id = ?");
        $stmt->execute([$gid, $cid]); 
        $captured_cards = $stmt->fetchAll();
        
        $captured_count = count($captured_cards); // Πλήθος φύλλων για το bonus +3

        foreach ($captured_cards as $c) {
            if (in_array($c['rank'], ['A', 'K', 'Q', 'J', '10'])) $points_added++;
            if ($c['rank'] == '10' && $c['suit'] == 'D') $points_added++; // 10 Καρό
            if ($c['rank'] == '2' && $c['suit'] == 'C') $points_added++;  // 2 Σπαθί
        }

        // Έλεγχος για "Ξερή" (Πρόταση 1: 10 πόντοι / 20 για Βαλέ)
        $stmt_count = $pdo->prepare("SELECT COUNT(*) as t FROM cards WHERE game_id = ? AND location = 'table'");
        $stmt_count->execute([$gid]);
        $table_total = $stmt_count->fetch()['t'];

        if ($table_total == 1 && $card['rank'] == $last['rank']) {
            $points_added += ($card['rank'] == 'J' ? 20 : 10);
            $action_msg = "Ο παίκτης " . $p['username'] . " έκανε ΞΕΡΗ με " . $card_name;
        } else {
            $action_msg = "Ο παίκτης " . $p['username'] . " μάζεψε " . $captured_count . " φύλλα.";
        }

        // Μεταφορά καρτών στα "μαζεμένα" του παίκτη
        $target = ($p['player_side'] == 'P1' ? 'P1_captured' : 'P2_captured');
        $pdo->prepare("UPDATE cards SET location = ? WHERE (game_id = ? AND location = 'table') OR id = ?")
            ->execute([$target, $gid, $cid]);
        
        // Ενημέρωση Σκορ και Καταμέτρησης Φύλλων (Πρόταση 3)
        $pdo->prepare("UPDATE players SET score = score + ?, cards_collected = cards_collected + ? WHERE username = ?")
            ->execute([$points_added, $captured_count, $p['username']]);

    } else {
        // Απλό παίξιμο φύλλου στο τραπέζι
        $stmt_order = $pdo->prepare("SELECT COUNT(*) as c FROM cards WHERE game_id = ? AND location = 'table'"); 
        $stmt_order->execute([$gid]);
        $new_order = $stmt_order->fetch()['c'] + 1;

        $pdo->prepare("UPDATE cards SET location = 'table', table_order = ? WHERE id = ?")
            ->execute([$new_order, $cid]);
        
        $action_msg = "Ο παίκτης " . $p['username'] . " έπαιξε το φύλλο " . $card_name;
    }

    // 3. Καταγραφή Κίνησης στο Live Log (Πρόταση 3)
    $pdo->prepare("INSERT INTO game_log (game_id, username, action_text) VALUES (?, ?, ?)")
        ->execute([$gid, $p['username'], $action_msg]);

    // 4. Έλεγχος για νικητή (100 πόντοι) ή Αλλαγή Σειράς
    $new_total_score = $p['score'] + $points_added;
    
    if ($new_total_score >= 100) {
        $pdo->prepare("UPDATE board SET status = 'ended' WHERE game_id = ?")->execute([$gid]);
    } else {
        $stmt_next = $pdo->prepare("SELECT username FROM players WHERE game_id = ? AND username != ?");
        $stmt_next->execute([$gid, $p['username']]);
        $next_player = $stmt_next->fetch()['username'];
        
        $pdo->prepare("UPDATE board SET player_turn = ? WHERE game_id = ?")
            ->execute([$next_player, $gid]);
    }

    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Σφάλμα API: ' . $e->getMessage()]);
}