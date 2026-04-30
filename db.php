<?php
// Configurazione database per MAMP (mysqli)
$servername = "localhost";
$db_username = "root";
$db_password = "root";  // Password default di MAMP
$dbname = "EasyTicket";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);

if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>