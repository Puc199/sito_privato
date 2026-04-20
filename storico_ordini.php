<?php
session_start();

// Verify the user is logged in and has the correct role
if (!isset($_SESSION['loggedin']) || $_SESSION['ruolo'] != 1) {
    header("Location: login.php");
    exit();
}

// Database details
$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "sito";

// Create connection
$conn = new mysqli($servername, $db_username, $db_password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Retrieve match details
if (isset($_GET['id'])) {
    $match_id = intval($_GET['id']);
} else {
    $match_id = 0;
}
$match_details = [];
$tickets = [];

if ($match_id > 0) {
    $sql = "
        SELECT p.Squadra_C, s2.nome AS Squadra_T, p.Data_partita
        FROM partita p
        JOIN squadre s2 ON p.id_squadraOspite = s2.id
        WHERE p.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $match_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $match_details = $result->fetch_assoc();
    }

    $stmt->close();
}

// Retrieve tickets sold for the specific match
if (!empty($match_details)) {
    $sql = "
        SELECT b.id, b.ID_Partita, b.posto, b.prezzo, u.username, b.settore
        FROM biglietto b
        JOIN utente u ON b.ID_Utente = u.id
        WHERE b.ID_Partita = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $match_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $tickets[] = $row;
        }
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Storico Ordini</title>
    <link rel="stylesheet" href="css/style4.css">
</head>
<body>
    <header>
        <a href="home.php">
            <img src="img/logo_inter.png" alt="logo Inter">
        </a>
        <nav>
            <ul id="user-area">
                <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
                    <li>
                        <a href="<?php echo $_SESSION['ruolo'] == 1 ? 'admin_dashboard.php' : 'User_dashboard.php'; ?>" id="user-link" class="login-style">
                        </a>
                    </li>
                <?php else: ?>
                    <li><a href="login.php" id="login-link" class="login-style">Login</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
    <main>
        <?php if (!empty($match_details)): ?>
            <h1>Storico Ordini per la Partita: 
                <?php echo htmlspecialchars($match_details['Squadra_C'] . ' vs ' . $match_details['Squadra_T']); ?>
            </h1>
            <?php if (!empty($tickets)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Utente</th>
                            <th>Settore</th>
                            <th>Posto</th>
                            <th>Prezzo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets as $ticket): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($ticket['username']); ?></td>
                                <td><?php echo htmlspecialchars($ticket['settore']); ?></td>
                                <td><?php echo htmlspecialchars($ticket['posto']); ?></td>
                                <td><?php echo htmlspecialchars($ticket['prezzo']); ?>€</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Nessun biglietto venduto per questa partita.</p>
            <?php endif; ?>
        <?php else: ?>
            <p>Partita non trovata o ID non valido.</p>
        <?php endif; ?>
    </main>
    <footer>
        <p>&copy; 2026 EasyTicket</p>
    </footer>
</body>
</html>
