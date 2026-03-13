<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Ξερή - Παιχνίδι</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/game_cards.css"> 
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .info-bar { background: #2c3e50; color: white; padding: 10px; display: flex; justify-content: center; gap: 15px; border-radius: 5px; }
        .log-box { background: white; border: 1px solid #ccc; height: 100px; overflow-y: auto; margin-top: 10px; padding: 5px; color: black; }
    </style>
</head>
<body>
    <div id="game-container">
        <div class="info-bar">
            <span>ID: <strong id="gid-val"></strong></span> |
            <span>Σκορ: <strong id="score-val">0</strong></span> |
            <span>Φύλλα: <strong id="captured-val">0</strong></span> |
            <span>Τράπουλα: <strong id="deck-val">0</strong></span>
            <button onclick="forfeitGame()" style="background:red; color:white; border:none; cursor:pointer;">Εγκατάλειψη</button>
        </div>

        <div id="turn-msg" style="text-align:center; padding:10px; font-weight:bold;">Φόρτωση...</div>

        <div class="board-area" style="position:relative; min-height:200px;">
            <div id="table-cards"></div>
        </div>

        <div class="my-hand">
            <h4>Τα Φύλλα σου</h4>
            <div id="my-cards" class="cards-flex"></div>
        </div>

        <div class="log-box" id="game-log"></div>
    </div>

    <script>
    const user = sessionStorage.getItem('username');
    const token = sessionStorage.getItem('token');
    const gid = sessionStorage.getItem('game_id');
    $('#gid-val').text(gid);

    function updateGame() {
        $.get('api/game_status.php', { game_id: gid, token: token }, function(res) {
            if(res.status === 'ended') {
                alert("Το παιχνίδι έληξε!"); window.location.href = 'lobby.php'; return;
            }
            $('#score-val').text(res.score);
            $('#captured-val').text(res.cards_collected);
            $('#deck-val').text(res.remaining_deck);
            $('#turn-msg').text(res.player_turn === user ? "ΔΙΚΗ ΣΟΥ ΣΕΙΡΑ!" : "Περιμένεις τον " + res.player_turn);

            let logHtml = ""; res.log.forEach(m => logHtml += `<div>${m}</div>`); $('#game-log').html(logHtml);

            let tableHtml = "";
            res.table.forEach((c, i) => {
                tableHtml += `<div class="table-card" style="left:${50+(i*20)}px; z-index:${i}">${renderCard(c)}</div>`;
            });
            $('#table-cards').html(tableHtml);

            let handHtml = "";
            res.hand.forEach(c => {
                handHtml += `<button class="card-btn" onclick="playCard(${c.id})">${renderCard(c)}</button>`;
            });
            $('#my-cards').html(handHtml);
        });
    }

    function renderCard(c) {
        const isRed = (c.suit === 'H' || c.suit === 'D') ? 'red' : '';
        const icons = { 'C': '♣', 'D': '♦', 'H': '♥', 'S': '♠' };
        return `<div class="card ${isRed}"><span>${c.rank}</span><span>${icons[c.suit]}</span></div>`;
    }

    function playCard(id) { $.post('api/play_card.php', { card_id: id, token: token, game_id: gid }, updateGame); }
    function forfeitGame() { $.post('api/game_admin.php', { action: 'forfeit', username: user, game_id: gid }, () => window.location.href='lobby.php'); }

    setInterval(updateGame, 2000);
    updateGame();


  // Διαγράψτε την παλιά forfeitGame και κρατήστε ΜΟΝΟ αυτή:
function forfeitGame() {
    if (confirm("Είστε σίγουροι ότι θέλετε να εγκαταλείψετε; Ο αντίπαλος θα κερδίσει!")) {
        $.post('api/game_admin.php', { 
            action: 'forfeit', 
            username: user, 
            game_id: gid,
            token: token 
        }, function(res) {
            if (res.status === 'success') {
                alert("Εγκαταλείψατε το παιχνίδι.");
                window.location.href = 'lobby.php';
            }
        });
    }
}
    </script>
</body>
</html>