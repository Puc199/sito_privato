<?php
require_once 'init.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$messaggio = "";

// Eseguiamo la logica SOLO se l'utente ha effettivamente premuto il tasto nel form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username'] ?? '');
    $new_password = $_POST['password'] ?? '';
    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

    // Usiamo PDO (coerente con le slide del prof)
    $stmt = $pdo->prepare("SELECT id FROM utente WHERE username = ? LIMIT 1");
    $stmt->execute([$user]);
    $idutente = $stmt->fetchColumn();

    if ($idutente) {
        // Username trovato, aggiorna la password
        $update_stmt = $pdo->prepare("UPDATE utente SET password = ? WHERE id = ?");
        $update_stmt->execute([$hashed_password, $idutente]);

        // Reindirizza al login con un parametro di successo
        header("Location: login.php");
        exit();
    } else {
        $messaggio = "<p style='color: red;'>Username non trovato.</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>EasyTicket - Recupera Password</title>
    <link rel="stylesheet" href="css/style2.css">
</head>
<body>
    <div class="main">
        <h1>Recupera Password</h1>
        
        <?php echo $messaggio; ?>
        
        <form action="recuperaPassword.php" method="post">
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