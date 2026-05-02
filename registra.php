<?php
require_once 'init.php';

$register_error = '';
$register_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $cognome = trim($_POST['cognome'] ?? '');
    $data_nascita = $_POST['data_nascita'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $password_plain = $_POST['password'] ?? '';

    if ($nome === '' || $cognome === '' || $data_nascita === '' || $username === '' || $password_plain === '') {
        $register_error = 'Compila tutti i campi.';
    } else {
        $password = password_hash($password_plain, PASSWORD_DEFAULT);
        $id_ruolo = 2;
        $saldo = 0.00;

        $stmt = $conn->prepare("SELECT id FROM utente WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $register_error = "Il nome utente è già presente. Scegli un altro nome utente.";
        } else {
            $sql_utente = "INSERT INTO utente (nome, cognome, data_nascita, username, password, id_ruolo, saldo)
                           VALUES (?, ?, ?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($sql_utente);
            $insert_stmt->bind_param("sssssid", $nome, $cognome, $data_nascita, $username, $password, $id_ruolo, $saldo);

            if ($insert_stmt->execute()) {
                header("Location: login.php?registered=1");
                exit();
            } else {
                $register_error = "Errore nell'inserimento dell'utente: " . $insert_stmt->error;
            }

            $insert_stmt->close();
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrazione - EasyTicket</title>
    <link rel="stylesheet" href="css/style2.css?v=22">
    <link rel="icon" type="image/png" href="img/icn_sito_sf.png">
</head>
<body>
<div class="login-shell">
    <div class="login-card">
        <div class="login-brand">
            <img src="img/logo_sito.png" alt="Logo EasyTicket">
            <h1>Crea account</h1>
            <p>Registrati per prenotare eventi in modo semplice e veloce.</p>
        </div>

        <?php if ($register_error !== ''): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($register_error); ?></div>
        <?php endif; ?>

        <form action="registra.php" method="post" class="login-form">
            <label for="nome">Nome</label>
            <input type="text" id="nome" name="nome" placeholder="Inserisci nome" required>

            <label for="cognome">Cognome</label>
            <input type="text" id="cognome" name="cognome" placeholder="Inserisci cognome" required>

            <label for="data_nascita">Data di nascita</label>
            <input type="date" id="data_nascita" name="data_nascita" required>

            <label for="username">Username</label>
            <input type="text" id="username" name="username" placeholder="Inserisci nome utente" required>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Inserisci password" required>

            <div class="wrap">
                <button type="submit">Registrati</button>
            </div>
        </form>

        <div class="login-links">
            <a href="login.php">Hai già un account? Accedi</a>
        </div>
    </div>
</div>
</body>
</html>