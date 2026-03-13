<?php
// Στοιχεία σύνδεσης βάσης δεδομένων
$host = '127.0.0.1';
$port = '3308';
$dbname = 'xeri_game';
$username = 'iee2020122';
$password = '2837issiml';

try {
    // Δημιουργία DSN (Data Source Name)
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    
    // Επιλογές PDO για καλύτερο error handling
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Πετάει Exception σε λάθη
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Επιστρέφει associative array
        PDO::ATTR_EMULATE_PREPARES   => false,                  // Απενεργοποίηση emulation για ασφάλεια
    ];

    // Δημιουργία του instance της σύνδεσης
    $pdo = new PDO($dsn, $username, $password, $options);

} catch (PDOException $e) {
    // Σε περίπτωση σφάλματος, επιστρέφει JSON (για να είναι συμβατό με το API)
    header('Content-Type: application/json');
    echo json_encode([
        "status" => "error",
        "message" => "Αποτυχία σύνδεσης με τη βάση: " . $e->getMessage()
    ]);
    exit;
}
?>