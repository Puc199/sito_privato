<?php
$servername = "localhost";
$db_username = "root";
$db_password = "root";
$dbname = "EasyTicket";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $db_username, $db_password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Connessione fallita: " . $e->getMessage());
}
?>