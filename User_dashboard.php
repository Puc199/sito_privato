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

$username = $_SESSION['username'];
$walletMessage = "";
$walletMessageType = "";

/* =========================
   RECUPERO DATI UTENTE
========================= */
$stmt = $conn->prepare("SELECT id, nome, cognome, username, saldo FROM utente WHERE username = ? LIMIT 1");
if (!$stmt) {
    die("Errore query utente: " . $conn->error);
}
$stmt->bind_param("s", $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    die("Utente non trovato.");
}

/* =========================
   RICARICA SALDO
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['wallet_action']) && $_POST['wallet_action'] === 'ricarica') {
    $importo = 0;

    if (isset($_POST['wallet_custom_submit'])) {
        $importo = isset($_POST['importo_custom']) ? floatval($_POST['importo_custom']) : 0;
    } elseif (isset($_POST['importo'])) {
        $importo = floatval($_POST['importo']);
    }

    if ($importo <= 0) {
        $walletMessage = "Inserisci un importo valido maggiore di zero.";
        $walletMessageType = "error";
    } elseif ($importo > 1000) {
        $walletMessage = "Puoi ricaricare al massimo 1000€ per singola operazione.";
        $walletMessageType = "error";
    } else {
        $stmt = $conn->prepare("UPDATE utente SET saldo = saldo + ? WHERE id = ?");
        if (!$stmt) {
            die("Errore query ricarica: " . $conn->error);
        }
        $stmt->bind_param("di", $importo, $user['id']);

        if ($stmt->execute()) {
            $walletMessage = "Ricarica effettuata con successo.";
            $walletMessageType = "success";
        } else {
            $walletMessage = "Errore durante la ricarica del saldo.";
            $walletMessageType = "error";
        }
        $stmt->close();

        $stmt = $conn->prepare("SELECT id, nome, cognome, username, saldo FROM utente WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

$saldo = (float)($user['saldo'] ?? 0);
$nomeCompleto = trim(($user['nome'] ?? '') . ' ' . ($user['cognome'] ?? ''));
$displayName = $nomeCompleto !== '' ? $nomeCompleto : $user['username'];

/* =========================
   RECUPERO BIGLIETTI
========================= */
$stmt = $conn->prepare("
    SELECT 
        b.id,
        b.sigillo_fiscale,
        b.disponibilita,
        b.posto,
        b.prezzo,
        b.data_acquisto,
        s.nome AS settore_nome,
        e.id AS evento_id,
        e.titolo,
        e.descrizione,
        e.data_evento,
        e.immagine,
        e.stato
    FROM biglietto b
    INNER JOIN evento_settore es ON b.id_evento_settore = es.id
    INNER JOIN evento e ON es.id_evento = e.id
    INNER JOIN settore s ON es.id_settore = s.id
    WHERE b.id_utente = ? AND b.disponibilita = 1
    ORDER BY e.data_evento DESC, b.data_acquisto DESC
");

if (!$stmt) {
    die("Errore query biglietti: " . $conn->error);
}

$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();

$tickets = [];
while ($row = $result->fetch_assoc()) {
    $tickets[] = $row;
}
$stmt->close();

$totalTickets = count($tickets);
$totalSpent = 0;
foreach ($tickets as $ticket) {
    $totalSpent += (float)$ticket['prezzo'];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - EasyTicket</title>
    <link rel="stylesheet" href="css/style1.css?v=dashboard2">
    <link rel="icon" type="image/png" href="img/icn_sito_sf.png">
</head>
<body>

<header class="site-header">
    <div class="header-inner">
        <a href="home.php" class="brand">
            <img src="img/logo_sito.png" alt="EasyTicket">
        </a>

        <nav class="user-nav">
            <a href="home.php" class="user-pill secondary-pill">Eventi</a>
            <a href="logout.php" class="user-pill primary-pill">Logout</a>
        </nav>
    </div>
</header>

<main class="dashboard-shell">

    <section class="dashboard-top">
        <article class="hero-user-card">
            <span class="hero-label">Area personale</span>
            <h1>Ciao <?php echo htmlspecialchars($displayName); ?>, bentornato</h1>
            <p>
                In questa dashboard puoi controllare i tuoi biglietti, vedere il saldo disponibile
                e ricaricare il wallet senza aprire altre pagine.
            </p>

            <div class="hero-actions">
                <a href="home.php" class="dash-btn dash-btn-primary">Scopri eventi</a>
                <a href="#wallet-card" class="dash-btn dash-btn-secondary">Ricarica saldo</a>
            </div>
        </article>

        <aside class="summary-card">
            <h2>Riepilogo</h2>
            <div class="stats-grid">
                <div class="stat-box">
                    <span>Saldo disponibile</span>
                    <strong>€ <?php echo number_format($saldo, 2, ',', '.'); ?></strong>
                </div>

                <div class="stat-box">
                    <span>Biglietti acquistati</span>
                    <strong><?php echo $totalTickets; ?></strong>
                </div>

                <div class="stat-box">
                    <span>Spesa totale</span>
                    <strong>€ <?php echo number_format($totalSpent, 2, ',', '.'); ?></strong>
                </div>
            </div>
        </aside>
    </section>

    <section class="dashboard-top" style="margin-top: 0;">
        <article class="summary-card wallet-summary-card" id="wallet-card">
            <div class="wallet-summary-top">
                <div>
                    <h2>Wallet</h2>
                    <p>Ricarica il saldo direttamente dalla dashboard e continua subito con i tuoi acquisti.</p>
                </div>
                <span class="wallet-mini-badge">EasyTicket Wallet</span>
            </div>

            <div class="wallet-balance-display">
                <span>Credito disponibile</span>
                <strong>€ <?php echo number_format($saldo, 2, ',', '.'); ?></strong>
            </div>

            <?php if (!empty($walletMessage)): ?>
                <div class="wallet-message <?php echo htmlspecialchars($walletMessageType); ?>">
                    <?php echo htmlspecialchars($walletMessage); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="wallet-inline-form">
                <input type="hidden" name="wallet_action" value="ricarica">

                <div class="wallet-quick-buttons">
                    <button type="submit" name="importo" value="10">+10€</button>
                    <button type="submit" name="importo" value="25">+25€</button>
                    <button type="submit" name="importo" value="50">+50€</button>
                    <button type="submit" name="importo" value="100">+100€</button>
                </div>

                <div class="wallet-custom-row">
                    <input type="number" name="importo_custom" min="1" step="0.01" placeholder="Importo personalizzato">
                    <button type="submit" name="wallet_custom_submit" value="1" class="dash-btn dash-btn-primary">
                        Ricarica saldo
                    </button>
                </div>
            </form>
        </article>

        <aside class="summary-card">
            <h2>Informazioni account</h2>
            <div class="stats-grid">
                <div class="stat-box">
                    <span>Utente</span>
                    <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                </div>

                <div class="stat-box">
                    <span>Nome completo</span>
                    <strong style="font-size:18px;"><?php echo htmlspecialchars($displayName); ?></strong>
                </div>

                <div class="stat-box">
                    <span>Suggerimento</span>
                    <strong style="font-size:16px; line-height:1.5; font-weight:600; color: var(--color-text);">
                        Ricarica il saldo prima di acquistare, così completi più velocemente il checkout.
                    </strong>
                </div>
            </div>
        </aside>
    </section>

    <section class="tickets-section">
        <div class="tickets-header">
            <div>
                <h2>I tuoi biglietti</h2>
                <p>Qui trovi tutti i biglietti acquistati associati al tuo account.</p>
            </div>
        </div>

        <?php if ($totalTickets > 0): ?>
            <div class="tickets-grid">
                <?php foreach ($tickets as $ticket): ?>
                    <article class="ticket-card">
                        <div class="ticket-image">
                            <?php if (!empty($ticket['immagine'])): ?>
                                <img src="<?php echo htmlspecialchars($ticket['immagine']); ?>" alt="<?php echo htmlspecialchars($ticket['titolo']); ?>">
                            <?php else: ?>
                                <?php echo htmlspecialchars($ticket['titolo']); ?>
                            <?php endif; ?>
                        </div>

                        <div class="ticket-body">
                            <div class="ticket-top">
                                <span class="ticket-badge"><?php echo htmlspecialchars($ticket['settore_nome']); ?></span>
                                <span class="ticket-price">€ <?php echo number_format((float)$ticket['prezzo'], 2, ',', '.'); ?></span>
                            </div>

                            <h3 class="ticket-title"><?php echo htmlspecialchars($ticket['titolo']); ?></h3>

                            <div class="ticket-meta">
                                <div class="ticket-meta-box">
                                    <span>Data evento</span>
                                    <strong><?php echo date('d/m/Y H:i', strtotime($ticket['data_evento'])); ?></strong>
                                </div>

                                <div class="ticket-meta-box">
                                    <span>Settore</span>
                                    <strong><?php echo htmlspecialchars($ticket['settore_nome']); ?></strong>
                                </div>

                                <div class="ticket-meta-box">
                                    <span>Posto</span>
                                    <strong><?php echo htmlspecialchars($ticket['posto']); ?></strong>
                                </div>

                                <div class="ticket-meta-box">
                                    <span>Acquistato il</span>
                                    <strong><?php echo date('d/m/Y H:i', strtotime($ticket['data_acquisto'])); ?></strong>
                                </div>
                            </div>

                            <div class="ticket-code">
                                <span>Sigillo fiscale</span>
                                <strong><?php echo htmlspecialchars($ticket['sigillo_fiscale']); ?></strong>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-card">
                <h3>Nessun biglietto acquistato</h3>
                <p>
                    Non risultano ancora biglietti associati al tuo account.
                    Vai alla pagina eventi, scegli quello che preferisci e completa l'acquisto.
                </p>
                <a href="home.php" class="dash-btn dash-btn-primary" style="margin-top:18px;">Vai agli eventi</a>
            </div>
        <?php endif; ?>
    </section>

</main>

<footer class="site-footer">
    © 2026 EasyTicket
</footer>

</body>
</html>