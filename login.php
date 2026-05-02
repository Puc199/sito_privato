<?php
require_once 'init.php';

$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // PDO usa prepare/execute con array, non bind_param
    $stmt = $pdo->prepare("SELECT id, nome, cognome, username, password, id_ruolo FROM utente WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $user['username'];
        $_SESSION['ruolo']    = (int)$user['id_ruolo'];
        $_SESSION['user_id']  = (int)$user['id'];
        header("Location: home.php");
        exit();
    } else {
        $login_error = "Username o password non validi.";
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<title>Login - EasyTicket</title>
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
<div class="section-heading"><h2>Accedi</h2></div>
<?php if ($login_error): ?>
<div class="admin-card msg-ko"><?php echo esc($login_error); ?></div>
<?php endif; ?>
<form method="post" class="admin-card">
<div class="admin-form-group">
<label>Username</label>
<input type="text" name="username" required>
</div>
<div class="admin-form-group">
<label>Password</label>
<input type="password" name="password" required>
</div>
<button type="submit" class="admin-submit">Accedi</button>
</form>
<p style="margin-top:12px;text-align:center;">Non hai un account? <a href="registra.php">Registrati</a></p>
</section>
</main>
<footer class="site-footer"><p>&copy; 2026 EasyTicket</p></footer>
</body>
</html>