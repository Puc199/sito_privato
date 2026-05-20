<?php
require_once 'init.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Controllo se l'utente è loggato
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

// Recuperiamo l'username direttamente dalla sessione
$username = $_SESSION['username'] ?? '';

// ALLINEAMENTO: Leggiamo dal nuovo cassetto della sessione generato da evento.php
$ticketDetails = $_SESSION['ticket_info'] ?? [];

// Usiamo il $pdo globale già pronto dentro init.php
$userDetails = getUserDetailsFromDatabase($pdo, $username);

function getUserDetailsFromDatabase($pdo, $username) {
    $stmt = $pdo->prepare("SELECT * FROM utente WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
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
            <p>Nome: <?php echo htmlspecialchars($userDetails['nome'] ?? ''); ?></p>
            <p>Cognome: <?php echo htmlspecialchars($userDetails['cognome'] ?? ''); ?></p>
            <p>Data di nascita: <?php echo htmlspecialchars($userDetails['data_nascita'] ?? ''); ?></p>
            <p>Username: <?php echo htmlspecialchars($userDetails['username'] ?? ''); ?></p>
        </div>
        <div>
            <h2>Dettagli Biglietto</h2>
            <p>Settore: <?php echo htmlspecialchars($ticketDetails['settore'] ?? 'N/D'); ?></p>
            
            <?php 
            // Estraiamo dinamicamente tutti i posti e i sigilli fiscali generati nella transazione
            if (!empty($ticketDetails['biglietti']) && is_array($ticketDetails['biglietti'])) {
                $posti = [];
                $sigilli = [];
                foreach ($ticketDetails['biglietti'] as $b) {
                    $posti[] = "P" . $b['posto'];
                    $sigilli[] = $b['sigillo_fiscale'];
                }
                // Se compri più posti li mostra separati da una virgola (es: P1, P2)
                $postoStr = implode(', ', $posti);
                $sigilloStr = implode(', ', $sigilli);
            } else {
                $postoStr = 'N/D';
                $sigilloStr = 'N/D';
            }
            ?>
            
            <p>Posto: <?php echo htmlspecialchars($postoStr); ?></p>
            <p>Sigillo Fiscale: <?php echo htmlspecialchars($sigilloStr); ?></p>
            <p>Prezzo Totale: <?php echo htmlspecialchars($ticketDetails['totale'] ?? '0'); ?>€</p>
        </div>
        <a href="home.php">Torna alla Home</a>
    </main>
</body>
<<<<<<< HEAD
</html>


=======
</html>
>>>>>>> 4c087f73274427b82da25fdb6e34d68ade4a4cd3
