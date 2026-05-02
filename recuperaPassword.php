<!DOCTYPE html>
<html>

<head>
    <title>EasyTicket LOGIN</title>
    <link rel="stylesheet" href="css/style2.css">
</head>

<body>
    <div class="main">
        <h1>Recupera Password</h1>
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

            $user = $_POST['username'];
            $new_password = $_POST['password'];
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

            // Prepara ed esegui la query per verificare l'username
            $stmt = $conn->prepare("SELECT id FROM utente WHERE username = ?");
            $stmt->bind_param("s", $user);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                // Username trovato, aggiorna la password
                $stmt->bind_result($id);
                $stmt->fetch();

                // Aggiorna la password nel database
                $update_stmt = $conn->prepare("UPDATE utente SET password = ? WHERE id = ?");
                $update_stmt->bind_param("si", $hashed_password, $id);
                $update_stmt->execute();

                if ($update_stmt->affected_rows > 0) {
                    header("Location: login.php");
                    exit();
                    echo "<p>La tua password è stata aggiornata con successo.</p>";
                } else {
                    echo "<p>Errore nell'aggiornamento della password.</p>";
                }

            } else {
                echo "<p>Username non trovato.</p>";
            }

            $stmt->close();
            $conn->close();
        ?>
        <form action="" method="post">
            <label for="first">Username:</label>
            <input type="text" id="first" name="username" placeholder="Inserisci Nome Utente" required>
            <label for="password">Nuova Password:</label>
            <input type="password" id="password" name="password" placeholder="Inserisci nuova Password" required>
            <div class="wrap">
                <button type="submit">Cambia Password</button>
            </div>
        </form>
    </div>
</body>

</html>
