<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Ξερή - Lobby</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="lobby-container">
        <h2>Καλώς ήρθες,<br><span id="display-user"></span></h2>
        
        <button id="createBtn">Δημιουργία Νέου Παιχνιδιού</button>
        
        <div class="divider">ή</div>
        
        <input type="number" id="game_id_input" placeholder="Εισάγετε ID Παιχνιδιού">
        <button id="joinBtn">Σύνδεση σε Παιχνίδι</button>
        
        <button onclick="logout()" class="logout-btn">Αποσύνδεση (Logout)</button>
    </div>

    <script>
        // ... (το script παραμένει το ίδιο με την προηγούμενη διορθωμένη έκδοση)
        const user = sessionStorage.getItem('username');
        if(!user) window.location.href = 'index.php';
        $('#display-user').text(user);

        $('#createBtn').click(function() { manageGame('create'); });
        $('#joinBtn').click(function() {
            const gid = $('#game_id_input').val();
            if(!gid) { alert("Παρακαλώ εισάγετε ένα ID!"); return; }
            manageGame('join', gid);
        });

        function manageGame(action, gid = null) {
            $.post('api/game_admin.php', { 
                action: action, 
                game_id: gid,
                username: user,
                token: sessionStorage.getItem('token')
            }, function(res) {
                if(res.status === 'success') {
                    sessionStorage.setItem('game_id', res.game_id);
                    window.location.href = 'game.php';
                } else { alert(res.message); }
            }, 'json');
        }

        function logout() {
            sessionStorage.clear();
            window.location.href = 'index.php';
        }
    </script>
</body>
</html>