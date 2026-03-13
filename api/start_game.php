<?php
function initializeGame($pdo, $game_id) {
    $pdo->prepare("DELETE FROM cards WHERE game_id = ?")->execute([$game_id]);
    $suits = ['C', 'D', 'H', 'S'];
    $ranks = ['A', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K'];
    $deck = [];
    foreach ($suits as $s) foreach ($ranks as $r) $deck[] = ['s' => $s, 'r' => $r];
    shuffle($deck);

    $stmt = $pdo->prepare("INSERT INTO cards (game_id, suit, rank, location, table_order) VALUES (?, ?, ?, ?, ?)");
    foreach ($deck as $i => $card) {
        $loc = 'deck'; $order = 0;
        if ($i < 6) $loc = 'P1_hand';
        elseif ($i < 12) $loc = 'P2_hand';
        elseif ($i < 16) { 
            $loc = 'table'; 
            $order = ($i - 11); // Τάξη 1, 2, 3, 4 για τα πρώτα φύλλα
        }
        $stmt->execute([$game_id, $card['s'], $card['r'], $loc, $order]);
    }
}
?>