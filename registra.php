<?php
require_once 'init.php';

$reg_error   = '';
$reg_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome        = trim($_POST['nome'] ?? '');
    $cognome     = trim($_POST['cognome'] ?? '');
    $data_nascita = trim($_POST['data_nascita'] ?? '');
    $username    = trim($_POST['username'] ?? '');
    $password    = $_POST['password'] ?? '';

    if ($nome === '' || $cognome === '' || $data_nascita === '' || $username === '' || $password === '') {
        $reg_error = "Compila tutti i campi.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM utente WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();

        if ($exists) {
            $reg_error = "Username già in uso. Scegline un altro.";
        } else {
            $hash    = password_hash($password, PASSWORD_DEFAULT);
            $id_ruolo = 2;
            $saldo   = 0.00;

            $stmt = $conn->prepare("INSERT INTO utente (nome, cognome, data_nascita, username, password, id_ruolo, saldo) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssid", $nome, $cognome, $data_nascita, $username, $hash, $id_ruolo, $saldo);

            if ($stmt->execute()) {
                $reg_success = "Registrazione completata! Reindirizzamento...";
                header("Refresh: 2; URL=login.php");
            } else {
                $reg_error = "Errore durante la registrazione: " . $stmt->error;
            }
            $stmt->close();
        }
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Registrati - EasyTicket</title>
    <link rel="stylesheet" href="css/style1.css">
    <link rel="icon" type="image/png" href="img/icn_sito_sf.png">
</head>
<body>
<header class="site-header">
    <div class="header-inner">
        <a href="home.php" class="brand"><img src="img/logo_sito.png" alt="EasyTicket"></a>
    </div>
</header>
<main class="page-shell">
    <section class="section-block" style="max-width:480px;margin:auto;">
        <div class="section-heading"><h2>Crea account</h2></div>
        <?php if ($reg_error): ?>
            <div class="admin-card msg-ko"><?php echo esc($reg_error); ?></div>
        <?php endif; ?>
        <?php if ($reg_success): ?>
            <div class="admin-card msg-ok"><?php echo esc($reg_success); ?></div>
        <?php endif; ?>
        <form method="post" class="admin-card">
            <div class="admin-form-group"><label>Nome</label><input type="text" name="nome" required></div>
            <div class="admin-form-group"><label>Cognome</label><input type="text" name="cognome" required></div>
            <div class="admin-form-group"><label>Data di nascita</label><input type="date" name="data_nascita" required></div>
            <div class="admin-form-group"><label>Username</label><input type="text" name="username" required></div>
            <div class="admin-form-group"><label>Password</label><input type="password" name="password" required></div>
            <button type="submit" class="admin-submit">Registrati</button>
        </form>
        <p style="margin-top:12px;text-align:center;">Hai già un account? <a href="login.php">Accedi</a></p>
    </section>
</main>
<footer class="site-footer"><p>&copy; 2026 EasyTicket</p></footer>
</body>
</html>