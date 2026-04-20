<?php
session_start();

// Abilita la visualizzazione degli errori
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Dettagli del database
$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "EasyTicket";

// Crea connessione
$conn = new mysqli($servername, $db_username, $db_password, $dbname);

// Controlla connessione
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

// Prendi i dati dal form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM utente WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        if (password_verify($password, $user["password"])) {
            $_SESSION["username"] = $user["username"];
            $_SESSION["ruolo"] = (int)$user["id_ruolo"];
            $_SESSION["loggedin"] = true;

            header("Location: home.php");
            exit();
        } else {
            $login_error = "Password non valida";
        }
    } else {
        $login_error = "Username non valido";
    }

    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>EasyTicket</title>
    <link rel="stylesheet" href="css/style2.css?v=20">
    <link rel="icon" type="image/png" href="img/icn_sito_sf.png">
</head>
<body>
    <div class="main">
        <img src="img/logo_sito.png" alt="Logo EasyTicket">
        <?php if (isset($login_error)): ?>
            <div class='alert alert-danger'><?php echo $login_error; ?></div>
        <?php endif; ?>
        <form action="login.php" method="post">
            <label for="first">Username:</label>
            <input type="text" id="first" name="username" placeholder="Inserisci Nome Utente" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" placeholder="Inserisci Password" required>

            <div class="wrap">
                <button type="submit">Accedi</button>
            </div>
        </form>
        <p>
            <a href="registra.php" style="text-decoration: none;">Crea account</a>
            <br>
            <a href="recuperaPassword.php" style="text-decoration: none;">Hai dimenticato la password?</a>
        </p>
    </div>
</body>
</html>