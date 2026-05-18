<?php
require_once 'init.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'] ?? '';
$ruolo = (int)($_SESSION['ruolo'] ?? 0);

if ($username === '') {
    die("Utente non valido.");
}

$stmt = $pdo->prepare("SELECT id, username, saldo FROM utente WHERE username = ? LIMIT 1");
$stmt->execute([$username]);
$utente = $stmt->fetch();

if (!$utente) {
    die("Utente non trovato.");
}

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
    <link rel="stylesheet" href="css/style1.css?v=40">
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
        <section class="section-block">
            <div class="section-heading">
                <h2>Ciao <?php echo htmlspecialchars($utente['username']); ?>, bentornato</h2>
                <p>Controlla i tuoi biglietti e ricarica il wallet.</p>
            </div>

            <div class="admin-grid">
                <div class="admin-card">
                    <h3>Saldo disponibile</h3>
                    <p id="wallet-balance" style="font-size: 2rem; font-weight: 700; margin-top: 10px;">
                        € <?php echo number_format((float)$utente['saldo'], 2, ',', '.'); ?>
                    </p>
                </div>

                <div class="admin-card">
                    <h3>Biglietti acquistati</h3>
                    <p style="font-size: 2rem; font-weight: 700; margin-top: 10px;">
                        <?php echo $numeroBiglietti; ?>
                    </p>
                </div>

                <div class="admin-card">
                    <h3>Totale speso</h3>
                    <p style="font-size: 2rem; font-weight: 700; margin-top: 10px;">
                        € <?php echo number_format($totaleSpeso, 2, ',', '.'); ?>
                    </p>
                </div>
            </div>
        </section>

        <?php if ($ruolo === 2): ?>
        <section class="section-block">
            <div class="section-heading">
                <h2>Ricarica Wallet</h2>
            </div>

            <div class="admin-card" style="max-width: 520px;">
                <p style="margin-bottom: 16px;">Aggiungi credito al tuo account in modo rapido.</p>

                <div id="wallet-feedback" style="display:none; margin:12px 0 18px 0; padding:12px; border-radius:10px;"></div>

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
        </section>
        <?php endif; ?>

        <section class="section-block">
            <div class="section-heading">
                <h2>I tuoi biglietti</h2>
            </div>

            <?php if (empty($biglietti)): ?>
                <div class="admin-card">
                    <h3>Nessun biglietto trovato</h3>
                    <p style="margin-top: 10px;">Non risultano ancora biglietti associati al tuo account.</p>
                    <div style="margin-top: 20px;">
                        <a href="home.php" class="admin-submit" style="display: inline-block; text-decoration: none;">
                            Vai agli eventi
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="styled-table">
                        <thead>
                            <tr>
                                <th>Evento</th>
                                <th>Data</th>
                                <th>Settore</th>
                                <th>Posto</th>
                                <th>Prezzo</th>
                                <th>Sigillo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($biglietti as $biglietto): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($biglietto['evento_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($biglietto['data_evento']); ?></td>
                                    <td><?php echo htmlspecialchars($biglietto['settore_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($biglietto['posto']); ?></td>
                                    <td>€ <?php echo number_format((float)$biglietto['prezzo'], 2, ',', '.'); ?></td>
                                    <td><?php echo htmlspecialchars($biglietto['sigillo_fiscale']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <footer class="site-footer">
        <p>&copy; 2026 EasyTicket</p>
    </footer>

    <?php if ($ruolo === 2): ?>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('wallet-recharge-form');
        const inputImporto = document.getElementById('wallet-importo');
        const submitBtn = document.getElementById('wallet-submit-btn');
        const feedbackBox = document.getElementById('wallet-feedback');
        const balanceElement = document.getElementById('wallet-balance');

        if (!form || !inputImporto || !submitBtn || !feedbackBox || !balanceElement) {
            return;
        }

        function showFeedback(message, isSuccess) {
            feedbackBox.style.display = 'block';
            feedbackBox.textContent = message;
            feedbackBox.style.backgroundColor = isSuccess ? '#e8f7ee' : '#fdecea';
            feedbackBox.style.color = isSuccess ? '#1e6b3a' : '#b42318';
            feedbackBox.style.border = isSuccess
                ? '1px solid #b7dfc6'
                : '1px solid #f5c2c0';
        }

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const importo = parseFloat(inputImporto.value);

            if (isNaN(importo) || importo <= 0) {
                showFeedback('Inserisci un importo valido maggiore di 0.', false);
                return;
            }

            submitBtn.disabled = true;
            submitBtn.textContent = 'Ricarica in corso...';

            try {
                const formData = new FormData();
                formData.append('importo', importo);

                const response = await fetch('ricarica_wallet_ajax.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    balanceElement.textContent = '€ ' + data.nuovo_saldo;
                    inputImporto.value = '';
                    showFeedback(data.message, true);
                } else {
                    showFeedback(data.message || 'Operazione non riuscita.', false);
                }
            } catch (error) {
                showFeedback('Errore di comunicazione con il server.', false);
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Ricarica saldo';
            }
        });
    });
    </script>
    <?php endif; ?>
</body>
</html>