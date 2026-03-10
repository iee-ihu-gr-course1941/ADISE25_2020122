<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Ξερή - Login</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="login-container">
    <h1 style="color: #fff; text-shadow: 2px 2px 4px #000;">Ξερή Online</h1>
    <div class="card-login" style="background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 20px rgba(0,0,0,0.3);">
        <input type="text" id="username" placeholder="Το όνομά σου..." style="width: 250px; padding: 12px; border: 1px solid #ccc; border-radius: 5px;">
        <button id="loginBtn" style="padding: 12px 25px; background: #27ae60; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">Σύνδεση</button>
    </div>
    <div id="error-msg" style="color: #ff4d4d; margin-top: 10px; font-weight: bold;"></div>
</div>

    <script>
    $('#loginBtn').click(function() {
        const user = $('#username').val();
        if(!user) { alert("Βάλε όνομα!"); return; }

        $.post('api/auth.php', { action: 'login', username: user }, function(res) {
            if(res.status === 'success') {
                sessionStorage.setItem('username', user);
                sessionStorage.setItem('token', res.token);
                window.location.href = 'lobby.php';
            } else {
                $('#error-msg').text(res.message);
            }
        }, 'json');
    });
    </script>
</body>
</html>