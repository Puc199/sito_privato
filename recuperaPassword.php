<?php
require_once 'init.php';

$reset_error = '';
$reset_success = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($username === '' || $new_password === '' || $confirm_password === '') {
        $reset_error = "Compila tutti i campi.";
    } elseif ($new_password !== $confirm_password) {
        $reset_error = "Le password non coincidono.";
    } elseif (strlen($new_password) < 6) {
        $reset_error = "La nuova password deve contenere almeno 6 caratteri.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM utente WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user) {
            $reset_error = "Username non trovato.";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            $update = $pdo->prepare("UPDATE utente SET password = ? WHERE id = ?");
            $ok = $update->execute([$hashed_password, (int)$user['id']]);

            if ($ok) {
                $reset_success = "Password aggiornata con successo. Tra poco verrai reindirizzato al login.";
                header("Refresh: 2; URL=login.php");
            } else {
                $reset_error = "Errore durante l'aggiornamento della password.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recupera password - EasyTicket</title>
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/login.css">
    <link rel="icon" type="image/png" href="img/icn_sito_sf.png">
</head>
<body class="auth-page">

    <main class="login-shell">
        <div class="login-card">
            <div class="login-brand">
                <img src="img/logo_sito.png" alt="Logo EasyTicket">
                <h1>Recupera password</h1>
            </div>

            <?php if (!empty($reset_error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($reset_error); ?></div>
            <?php endif; ?>

            <?php if (!empty($reset_success)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($reset_success); ?></div>
            <?php endif; ?>

            <form action="recuperaPassword.php" method="post" class="login-form">
                <label for="username">Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="Inserisci username"
                    required
                    value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                >

                <label for="new_password">Nuova password</label>
                <input
                    type="password"
                    id="new_password"
                    name="new_password"
                    placeholder="Inserisci nuova password"
                    required
                >

                <label for="confirm_password">Conferma password</label>
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    placeholder="Conferma nuova password"
                    required
                >

                <div class="wrap">
                    <button type="submit" class="auth-submit">Aggiorna password</button>
                </div>
            </form>

            <div class="login-links">
                <a href="login.php">Torna al login</a>
            </div>
        </div>
    </main>

</body>
</html>