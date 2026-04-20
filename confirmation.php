<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
} else {
    $username = $_SESSION['username'];
}

if (!isset($_SESSION['ticket'])) {
    echo "Errore: dettagli del biglietto non trovati.";
    exit();
}

$userDetails = getUserDetailsFromDatabase($username);
$ticketDetails = $_SESSION['ticket'];

function getUserDetailsFromDatabase($username) {
    $conn = new mysqli("localhost", "root", "", "sito");
    if ($conn->connect_error) {
        die("Connessione fallita: " . $conn->connect_error);
    }
    $stmt = $conn->prepare("SELECT * FROM utente WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $userDetails = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $userDetails;
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conferma Acquisto</title>
    <link rel="stylesheet" href="css/style3.css">
    <link rel="icon" type="image/png" href="img/icn_sito_sf.png">
</head>
<body>
    <header>
        <h1>Grazie per il tuo acquisto, <?php echo htmlspecialchars($username); ?>!</h1>
    </header>
    <main>
        <div>
            <h2>Dati Utente</h2>
            <p>Nome: <?php echo htmlspecialchars($userDetails['nome']); ?></p>
            <p>Cognome: <?php echo htmlspecialchars($userDetails['cognome']); ?></p>
            <p>Data di nascita: <?php echo htmlspecialchars($userDetails['data_nascita']); ?></p>
            <p>Username: <?php echo htmlspecialchars($userDetails['username']); ?></p>
        </div>
        <div>
            <h2>Dettagli Biglietto</h2>
            <p>Sigillo Fiscale: <?php echo htmlspecialchars($ticketDetails['Sigillo_Fiscale']); ?></p>
            <p>Settore: <?php echo htmlspecialchars($ticketDetails['sector']); ?></p>
            <p>Posto: <?php echo htmlspecialchars($ticketDetails['posto']); ?></p>
            <p>Prezzo: <?php echo htmlspecialchars($ticketDetails['prezzo']); ?>€</p>
        </div>
        <a href="home.php">Torna alla Home</a>
    </main>
</body>
</html>
