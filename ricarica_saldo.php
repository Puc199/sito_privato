<?php
session_start();

// Abilita la visualizzazione degli errori
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Controlla se l'utente è loggato
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "sito";
$username = $_SESSION['username'];

// Variabile per memorizzare i messaggi
$message = "";

// Connessione al database
$conn = new mysqli($servername, $db_username, $db_password, $dbname);

// Controlla connessione
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

// Funzione per ottenere il saldo attuale
function getWalletBalance($conn, $username) {
    $stmt = $conn->prepare("SELECT u.Saldo FROM utente u WHERE u.username = ?");
    if (!$stmt) {
        die("Preparazione della query fallita: " . $conn->error);
    }
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['Saldo'];
    }
    return 0;
}

// Aggiorna il saldo se il modulo è stato inviato
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $importo = isset($_POST['importo']) ? floatval($_POST['importo']) : 0;
    echo "Importo ricevuto: " . $importo . "<br>";

    if ($importo > 0) {
        // Prepara e esegui query per ottenere l'ID utente
        $stmt = $conn->prepare("SELECT id FROM utente WHERE username = ?");
        if (!$stmt) {
            die("Preparazione della query fallita: " . $conn->error);
        }
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $id = $row['id'];

            // Aggiorna il saldo del wallet
            $stmt = $conn->prepare("UPDATE utente SET Saldo = Saldo + ? WHERE id = ?");
            if (!$stmt) {
                die("Preparazione della query di aggiornamento fallita: " . $conn->error);
            }
            $stmt->bind_param("di", $importo, $id);

            if ($stmt->execute()) {
                echo "Query eseguita con successo.<br>";
                $message = "Ricarica effettuata con successo.";
                header("Location: home.php");
                exit();
            } else {
                echo "Errore durante l'esecuzione della query di aggiornamento: " . $stmt->error . "<br>";
                $message = "Errore durante la ricarica del saldo: " . $stmt->error;
            }
        } else {
            echo "Utente non trovato.<br>";
            $message = "Utente non trovato.";
        }
    } else {
        echo "Importo non valido.<br>";
        $message = "Importo non valido.";
    }
}

// Ottieni il saldo attuale
$walletBalance = getWalletBalance($conn, $username);

// Chiudi la connessione
$conn->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ricarica Saldo</title>
    <link rel="stylesheet" href="css/style3.css">
</head>
<body>
    <header>
        <a href="home.php">
            <img src="img/logo_inter.png" alt="logo Inter">
        </a>
    </header>
    <main>
        <h1>Ricarica Saldo</h1>
        <p>Saldo attuale: <strong><?php echo $walletBalance; ?>€</strong></p>
        <p>Il tuo saldo è insufficiente per completare l'acquisto del biglietto. Si prega di ricaricare il saldo.</p>
        <form id="ricaricaForm" method="POST" action="">
            <label for="importo">Inserisci l'importo da ricaricare:</label>
            <input type="number" id="importo" name="importo" required>
            <button type="submit">Ricarica Saldo</button>
        </form>
        <?php if ($message): ?>
            <p><?php echo $message; ?></p>
        <?php endif; ?>
    </main>
    <footer>
        <p>&copy; 2026 EasyTicket</p>
    </footer>
</body>
</html>