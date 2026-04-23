<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "EasyTicket";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);

if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

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
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - EasyTicket</title>
    <link rel="stylesheet" href="css/style2.css?v=21">
    <link rel="icon" type="image/png" href="img/icn_sito_sf.png">
</head>
<body>
    <div class="login-shell">
        <div class="login-card">
            <div class="login-brand">
                <img src="img/logo_sito.png" alt="Logo EasyTicket">
                <h1>Accedi</h1>
            </div>

            <?php if (isset($login_error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($login_error); ?></div>
            <?php endif; ?>

            <form action="login.php" method="post" class="login-form">
                <label for="first">Username</label>
                <input type="text" id="first" name="username" placeholder="Inserisci nome utente" required>

                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Inserisci password" required>

                <div class="wrap">
                    <button type="submit">Accedi</button>
                </div>
            </form>

            <div class="login-links">
                <a href="registra.php">Crea account</a>
                <a href="recuperaPassword.php">Hai dimenticato la password?</a>
            </div>
        </div>
    </div>
</body>
</html>