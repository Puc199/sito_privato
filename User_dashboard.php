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
    $stmt = $conn->prepare("SELECT saldo FROM utente WHERE username = ?");
    if (!$stmt) {
        die("Preparazione della query fallita: " . $conn->error);
    }
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['saldo'];
    }
    return 0;
}

// Funzione per ottenere lo storico degli ordini
function getOrderHistory($conn, $username) {
    $stmt = $conn->prepare("
        SELECT b.Sigillo_Fiscale, p.Squadra_C AS Squadra_C, s.nome AS Squadra_T, b.posto AS Posto, b.prezzo, b.settore AS Settore
        FROM biglietto b
        JOIN utente u ON b.ID_Utente = u.id
        JOIN partita p ON b.ID_Partita = p.id
        JOIN squadre s ON p.id_squadraOspite = s.id
        WHERE u.username = ?
        ORDER BY b.id DESC
    ");
    if (!$stmt) {
        die("Preparazione della query fallita: " . $conn->error);
    }
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    return $orders;
}

// Aggiorna il saldo se il modulo è stato inviato
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $importo = isset($_POST['importo']) ? floatval($_POST['importo']) : 0;
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
            $stmt = $conn->prepare("UPDATE utente SET saldo = saldo + ? WHERE id = ?");
            if (!$stmt) {
                die("Preparazione della query di aggiornamento fallita: " . $conn->error);
            }
            $stmt->bind_param("di", $importo, $id);

            if ($stmt->execute()) {
                $message = "Ricarica effettuata con successo.";
                header("Location: User_dashboard.php");
                exit();
            } else {
                $message = "Errore durante la ricarica del saldo: " . $stmt->error;
            }
        } else {
            $message = "Utente non trovato.";
        }
    } else {
        $message = "Importo non valido.";
    }
}

// Ottieni il saldo attuale
$walletBalance = getWalletBalance($conn, $username);

// Ottieni lo storico degli ordini
$orderHistory = getOrderHistory($conn, $username);

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
        <div class="main-container">
            <div class="container">
                <h2>Ricarica</h2>
                <p>Saldo attuale: <strong><?php echo htmlspecialchars($walletBalance); ?>€</strong></p>
                <p>Ricarica qui il tuo saldo</p>
                <form id="ricaricaForm" method="POST" action="">
                    <label for="importo">Inserisci l'importo da ricaricare:</label>
                    <input type="number" id="importo" name="importo" required>
                    <button type="submit">Ricarica Saldo</button>
                </form>
                <?php if ($message): ?>
                    <p><?php echo htmlspecialchars($message); ?></p>
                <?php endif; ?>
            </div>
            <div class="container">
                <h2>Storico Ordini</h2>
                <?php if (count($orderHistory) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Sigillo Fiscale</th>
                                <th>Squadra C</th>
                                <th>Squadra T</th>
                                <th>Settore</th>
                                <th>Posto</th>
                                <th>Prezzo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orderHistory as $order): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($order['Sigillo_Fiscale']); ?></td>
                                    <td><?php echo htmlspecialchars($order['Squadra_C']); ?></td>
                                    <td><?php echo htmlspecialchars($order['Squadra_T']); ?></td>
                                    <td><?php echo htmlspecialchars($order['Settore']); ?></td>
                                    <td><?php echo htmlspecialchars($order['Posto']); ?></td>
                                    <td><?php echo htmlspecialchars($order['prezzo']); ?>€</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Non hai effettuato alcun ordine.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <footer>
        <p>&copy; 2026 EasyTicket</p>
    </footer>
</body>
</html>
