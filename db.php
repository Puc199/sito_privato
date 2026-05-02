<?php
$host = 'localhost';
$dbname = 'Easy'; // CORREZIONE: dal tuo file SQL
$user = 'root';
$pass = 'root'; // Password MAMP
$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Connessione fallita: " . $e->getMessage());
}
?>