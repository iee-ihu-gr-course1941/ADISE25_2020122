
[ΞΕΡΗ 2020122.docx](https://github.com/user-attachments/files/26165831/2020122.docx)


ΒΑΣΗ ΔΕΔΟΜΕΝΩΝ:
-- 1. Δημιουργία της Βάσης Δεδομένων
CREATE DATABASE IF NOT EXISTS xeri_game;
USE xeri_game;

-- 2. Πίνακας για το Ταμπλό (Board)
-- Περιλαμβάνει την κατάσταση παιχνιδιού, τη σειρά και τον νικητή σε περίπτωση εγκατάλειψης
CREATE TABLE board (
    game_id INT PRIMARY KEY,
    status ENUM('waiting', 'playing', 'ended') DEFAULT 'waiting',
    player_turn VARCHAR(50) DEFAULT NULL,
    last_captured_by ENUM('P1', 'P2') DEFAULT NULL
);

-- 3. Πίνακας για τους Παίκτες
-- Περιλαμβάνει το σκορ, το token ταυτοποίησης και τον μετρητή μαζεμένων φύλλων (Πρόταση 3)
CREATE TABLE players (
    username VARCHAR(50) PRIMARY KEY,
    game_id INT,
    player_side ENUM('P1', 'P2'),
    token VARCHAR(100),
    score INT DEFAULT 0,
    cards_collected INT DEFAULT 0,
    FOREIGN KEY (game_id) REFERENCES board(game_id) ON DELETE CASCADE
);

-- 4. Πίνακας για τις Κάρτες
-- Διαχειρίζεται την τοποθεσία κάθε κάρτας (τράπουλα, χέρι, τραπέζι, μαζεμένα)
CREATE TABLE cards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT,
    suit ENUM('C', 'D', 'H', 'S'), -- Clubs, Diamonds, Hearts, Spades
    rank ENUM('A', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K'),
    location ENUM('deck', 'P1_hand', 'P2_hand', 'table', 'P1_captured', 'P2_captured'),
    table_order INT DEFAULT 0,
    FOREIGN KEY (game_id) REFERENCES board(game_id) ON DELETE CASCADE
);

-- 5. Πίνακας για το Ιστορικό Κινήσεων (Game Log)
-- Υλοποιεί την απαίτηση για Live ενημέρωση των κινήσεων (Πρόταση 3)
CREATE TABLE game_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT,
    username VARCHAR(50),
    action_text TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (game_id) REFERENCES board(game_id) ON DELETE CASCADE
);
