<?php
require_once 'init.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['ruolo']) || (int)$_SESSION['ruolo'] !== 1) {
    header("Location: home.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $seat_id = $_POST['seat_id'];
    $user_id = 1; // Assume the user is logged in and user_id is 1
    $partita_id = 1; // Assume the match id is 1

    // Insert the ticket purchase into the biglietto table
    $sql = "INSERT INTO biglietto (Sigillo_Fiscale, N_posti, Disponibilità, ID_Utente, ID_Partita, ID_Posto) 
            VALUES ('ABC123', 1, 0, $user_id, $partita_id, $seat_id)";
    
    if ($conn->query($sql) === TRUE) {
        $ticket_id = $conn->insert_id;
        header("Location: confirmation.php?ticket_id=" . $ticket_id);
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }

    $conn->close();
} else {
    $seat_id = $_GET['seat_id'];
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Purchase</title>
        <link rel="icon" type="image/png" href="img/icn_sito_sf.png">
    </head>
    <body>
        <h1>Purchase Ticket</h1>
        <form method="post" action="purchase.php">
            <input type="hidden" name="seat_id" value="<?php echo $seat_id; ?>">
            <button type="submit">Confirm Purchase</button>
        </form>
    </body>
    </html>
    <?php
}
?>
