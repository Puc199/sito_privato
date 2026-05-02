<?php
require_once 'init.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php"); exit();
}
if (isset($_SESSION['ruolo']) && (int)$_SESSION['ruolo'] === 1) {
    header("Location: admin_dashboard.php"); exit();
}

$username          = $_SESSION['username'];
$walletMessage     = '';
$walletMessageType = '';

$stmt = $pdo->prepare("SELECT id, nome, cognome, username, saldo FROM utente WHERE username = ? LIMIT 1");
$stmt->execute([$username]);
$user = $stmt->fetch();
if (!$user) { die("Utente non trovato."); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['wallet_action'] ?? '') === 'ricarica') {
    $importo = 0;
    if (isset($_POST['wallet_custom_submit'])) {
        $importo = floatval($_POST['importo_custom'] ?? 0);
    } elseif (isset($_POST['importo'])) {
        $importo = floatval($_POST['importo']);
    }

    if ($importo <= 0) {
        $walletMessage = "Inserisci un importo valido maggiore di zero.";
        $walletMessageType = "error";
    } elseif ($importo > 1000) {
        $walletMessage = "Puoi ricaricare al massimo 1000€.";
        $walletMessageType = "error";
    } else {
        $stmt = $pdo->prepare("UPDATE utente SET saldo = saldo + ? WHERE id = ?");
        if ($stmt->execute([$importo, $user['id']])) {
            $walletMessage = "Ricarica effettuata con successo.";
            $walletMessageType = "success";
            $stmt = $pdo->prepare("SELECT id, nome, cognome, username, saldo FROM utente WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
        } else {
            $walletMessage = "Errore durante la ricarica.";
            $walletMessageType = "error";
        }
    }
}

$saldo       = (float)($user['saldo'] ?? 0);
$username = isset($_SESSION['username']) ? esc($_SESSION['username']) : null;
$stmt = $pdo->prepare("
    SELECT
        b.id, b.sigillo_fiscale, b.disponibilita, b.posto, b.prezzo, b.data_acquisto,
        es.id_settore,
        e.id AS evento_id, e.titolo, e.descrizione, e.immagine, e.stato,
        r.data_ora_inizio AS data_evento
    FROM biglietto b
    INNER JOIN evento_settore es ON b.id_evento_settore = es.id
    INNER JOIN evento e          ON es.id_evento = e.id
    INNER JOIN replica_evento r  ON es.id_replica_evento = r.id
    WHERE b.id_utente = ? AND b.disponibilita = 1
    ORDER BY r.data_ora_inizio DESC, b.data_acquisto DESC
");
$stmt->execute([$user['id']]);
$tickets = $stmt->fetchAll();

$nomiSettori = [1=>'VIP', 2=>'Tribuna', 3=>'Curva', 4=>'Platea', 5=>'Galleria'];
$totalSpent  = array_sum(array_column($tickets, 'prezzo'));
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - EasyTicket</title>
    <link rel="stylesheet" href="css/style1.css">
    <link rel="icon" type="image/png" href="img/icn_sito_sf.png">
</head>
<body>
<header class="site-header">
    <div class="header-inner">
        <a href="home.php" class="brand"><img src="img/logo_sito.png" alt="EasyTicket"></a>
        <nav class="user-nav">
            <a href="User_dashboard.php" class="user-pill primary-pill"><?php echo esc($username); ?></a>
            <a href="logout.php" class="user-pill secondary-pill">Logout</a>
        </nav>
    </div>
</header>
<main class="page-shell">
    <section class="section-block">
        <div class="section-heading">
            <h2>Ciao <?php echo esc($username); ?>, bentornato</h2>
            <p>Controlla i tuoi biglietti e ricarica il wallet.</p>
        </div>
        <div class="admin-grid">
            <div class="admin-card" style="text-align:center;">
                <p style="font-size:13px;color:#7a7974;">Saldo disponibile</p>
                <p style="font-size:2rem;font-weight:700;color:#01696f;">€<?php echo number_format($saldo,2,',','.'); ?></p>
            </div>
            <div class="admin-card" style="text-align:center;">
                <p style="font-size:13px;color:#7a7974;">Biglietti acquistati</p>
                <p style="font-size:2rem;font-weight:700;"><?php echo count($tickets); ?></p>
            </div>
            <div class="admin-card" style="text-align:center;">
                <p style="font-size:13px;color:#7a7974;">Totale speso</p>
                <p style="font-size:2rem;font-weight:700;">€<?php echo number_format($totalSpent,2,',','.'); ?></p>
            </div>
        </div>
    </section>

    <section class="section-block">
        <div class="section-heading"><h2>Ricarica Wallet</h2></div>
        <?php if ($walletMessage): ?>
            <div class="admin-card <?php echo $walletMessageType === 'success' ? 'msg-ok' : 'msg-ko'; ?>">
                <?php echo esc($walletMessage); ?>
            </div>
        <?php endif; ?>
        <form method="post" class="admin-card">
            <input type="hidden" name="wallet_action" value="ricarica">
            <div class="admin-form-group">
                <label>Importo (€)</label>
                <input type="number" name="importo" step="0.01" min="1" max="1000" placeholder="Es. 50.00" required>
            </div>
            <button type="submit" class="admin-submit">Ricarica</button>
        </form>
    </section>

    <section class="section-block">
        <div class="section-heading">
            <h2>I tuoi biglietti</h2>
        </div>
        <?php if (empty($tickets)): ?>
            <div class="empty-state">
                <h3>Nessun biglietto trovato</h3>
                <p>Non risultano ancora biglietti associati al tuo account.</p>
                <a href="home.php" class="hero-cta">Vai agli eventi</a>
            </div>
        <?php else: ?>
            <div class="admin-card table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr><th>Evento</th><th>Data</th><th>Settore</th><th>Posto</th><th>Prezzo</th><th>Sigillo</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($tickets as $t): ?>
                        <tr>
                            <td><strong><?php echo esc($t['titolo']); ?></strong></td>
                            <td><?php echo !empty($t['data_evento']) ? esc(date('d/m/Y H:i', strtotime($t['data_evento']))) : 'N/D'; ?></td>
                            <td><?php echo esc($nomiSettori[$t['id_settore']] ?? 'N/D'); ?></td>
                            <td><?php echo (int)$t['posto']; ?></td>
                            <td>€<?php echo number_format((float)$t['prezzo'],2,',','.'); ?></td>
                            <td><small><?php echo esc($t['sigillo_fiscale']); ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>
<footer class="site-footer"><p>&copy; 2026 EasyTicket</p></footer>
</body>
</html>