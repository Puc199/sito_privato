<?php
require_once 'init.php';

// Controllo accesso
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'] ?? '';
$ruolo = (int)($_SESSION['ruolo'] ?? 0);

if ($username === '') {
    die("Utente non valido.");
}

// Dati utente
$stmt = $pdo->prepare("SELECT id, username, saldo FROM utente WHERE username = ? LIMIT 1");
$stmt->execute([$username]);
$utente = $stmt->fetch();

if (!$utente) {
    die("Utente non trovato.");
}

// Dati biglietti
$stmt = $pdo->prepare("
    SELECT
        b.id,
        e.titolo AS evento_nome,
        r.data_ora_inizio AS data_evento,
        s.nome AS settore_nome,
        b.posto,
        b.prezzo,
        b.sigillo_fiscale
    FROM biglietto b
    JOIN evento_settore es ON b.id_evento_settore = es.id
    JOIN replica_evento r ON es.id_replica_evento = r.id
    JOIN evento e ON es.id_evento = e.id
    JOIN settore s ON es.id_settore = s.id
    WHERE b.id_utente = ?
    ORDER BY r.data_ora_inizio DESC, e.titolo ASC
");
$stmt->execute([$utente['id']]);
$biglietti = $stmt->fetchAll();

$numeroBiglietti = count($biglietti);
$totaleSpeso = 0;
foreach ($biglietti as $biglietto) {
    $totaleSpeso += (float)$biglietto['prezzo'];
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - EasyTicket</title>
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/user.css">
    <link rel="icon" type="image/png" href="img/icn_sito_sf.png">
</head>
<body>
    <header class="site-header">
        <div class="header-inner">
            <a href="home.php" class="brand">
                <img src="img/logo_sito.png" alt="Logo EasyTicket">
            </a>
            <nav class="user-nav">
                <a href="home.php" class="user-pill secondary-pill">Home</a>
                <a href="logout.php" class="user-pill secondary-pill">Logout</a>
            </nav>
        </div>
    </header>

    <main class="page-shell">
        
        <!-- Saluto e Statistiche -->
        <section class="section-block">
            <div class="section-heading">
                <h2>Ciao <?php echo htmlspecialchars($utente['username']); ?></h2>
                <p>Controlla il tuo saldo, i tuoi biglietti e ricarica il wallet.</p>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Saldo disponibile</h3>
                    <p class="stat-value" id="wallet-balance">
                        € <?php echo number_format((float)$utente['saldo'], 2, ',', '.'); ?>
                    </p>
                </div>
                <div class="stat-card">
                    <h3>Biglietti acquistati</h3>
                    <p class="stat-value"><?php echo $numeroBiglietti; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Totale speso</h3>
                    <p class="stat-value">€ <?php echo number_format($totaleSpeso, 2, ',', '.'); ?></p>
                </div>
            </div>
        </section>

        <!-- Ricarica Wallet (Solo per utenti standard, ruolo 2) -->
        <?php if ($ruolo === 2): ?>
        <section class="section-block wallet-section">
            <div class="section-heading">
                <h2>Ricarica Wallet</h2>
            </div>
            <div class="wallet-card">
                <div class="wallet-balance">
                    <span>Il tuo credito attuale</span>
                    <strong id="wallet-display-balance">€ <?php echo number_format((float)$utente['saldo'], 2, ',', '.'); ?></strong>
                </div>
                <div class="wallet-form">
                    <div id="wallet-feedback"></div>
                    <form id="wallet-recharge-form">
                        <div class="admin-form-group">
                            <label for="wallet-importo">Importo da ricaricare</label>
                            <input
                                type="number"
                                id="wallet-importo"
                                name="importo"
                                min="1"
                                max="1000"
                                step="0.01"
                                placeholder="Es. 20.00"
                                required
                            >
                        </div>
                        <button type="submit" id="wallet-submit-btn" class="admin-submit">
                            Ricarica saldo
                        </button>
                    </form>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Sezione Biglietti -->
        <section class="section-block tickets-section">
            <h2>I tuoi biglietti</h2>
            
            <?php if (empty($biglietti)): ?>
                <div class="empty-card">
                    <h3>Nessun biglietto trovato</h3>
                    <p>Non risultano ancora biglietti associati al tuo account.</p>
                    <a href="home.php" class="admin-submit" style="display: inline-block; text-decoration: none;">
                        Vai agli eventi
                    </a>
                </div>
            <?php else: ?>
                <div class="tickets-grid">
                    <?php foreach ($biglietti as $biglietto): ?>
                        <div class="ticket-card" id="ticket-card-<?php echo $biglietto['id']; ?>">
                            
                            <!-- Header cliccabile (Vista rapida) -->
                            <div class="ticket-card-header" onclick="toggleTicketDetails(<?php echo $biglietto['id']; ?>)">
                                <div class="ticket-card-top">
                                    <span class="ticket-badge"><?php echo htmlspecialchars($biglietto['settore_nome']); ?></span>
                                    <span class="ticket-price">€ <?php echo number_format($biglietto['prezzo'], 2, ',', '.'); ?></span>
                                </div>
                                <h3><?php echo htmlspecialchars($biglietto['evento_nome']); ?></h3>
                                <div class="ticket-meta-preview">
                                    <span>📅 <?php echo date('d/m/Y', strtotime($biglietto['data_evento'])); ?></span>
                                    <span>🎫 Posto <?php echo htmlspecialchars($biglietto['posto']); ?></span>
                                </div>
                                <div class="ticket-expand-hint">Clicca per vedere i dettagli</div>
                            </div>
                            
                            <!-- Dettagli nascosti (Si aprono al click) -->
                            <div class="ticket-details" id="ticket-details-<?php echo $biglietto['id']; ?>">
                                <div class="detail-row">
                                    <span class="detail-label">Evento:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($biglietto['evento_nome']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Data e ora:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($biglietto['data_evento']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Settore:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($biglietto['settore_nome']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Posto:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($biglietto['posto']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Prezzo pagato:</span>
                                    <span class="detail-value">€ <?php echo number_format($biglietto['prezzo'], 2, ',', '.'); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Sigillo fiscale:</span>
                                    <span class="detail-value sigillo"><?php echo htmlspecialchars($biglietto['sigillo_fiscale']); ?></span>
                                </div>
                                <div class="ticket-actions" onclick="event.stopPropagation();">
                                    <button
                                        type="button"
                                        class="btn-delete"
                                        onclick="event.stopPropagation(); deleteTicket(<?php echo (int)$biglietto['id']; ?>, <?php echo (float)$biglietto['prezzo']; ?>, this);">
                                        Elimina biglietto e richiedi rimborso
                                    </button>
                                </div>
                           </div>
                            </div>
                            
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

    </main>

    <footer class="site-footer">
        <p>&copy; 2026 EasyTicket</p>
    </footer>

    <!-- Script JS -->
    <script src="js/user_dashboard.js"></script>

</body>
</html>