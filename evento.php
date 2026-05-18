<?php
require_once 'init.php';

$id_evento = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_evento <= 0) {
    die("Evento non valido.");
}

$stmt = $pdo->prepare("
    SELECT 
        e.*,
        c.nome AS categoria,
        l.nome AS luogo,
        l.citta,
        l.indirizzo
    FROM evento e
    JOIN categoria c ON e.id_categoria = c.id
    JOIN luogo l ON e.id_luogo = l.id
    WHERE e.id = ?
    LIMIT 1
");
$stmt->execute([$id_evento]);
$evento = $stmt->fetch();

if (!$evento) {
    die("Evento non trovato.");
}

$stmt = $pdo->prepare("
    SELECT id, data_ora_inizio
    FROM replica_evento
    WHERE id_evento = ? AND stato = 'programmata'
    ORDER BY data_ora_inizio ASC
");
$stmt->execute([$id_evento]);
$repliche = $stmt->fetchAll();

$errore_acquisto = '';
$messaggio_acquisto = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        $errore_acquisto = "Devi effettuare il login per acquistare.";
    } else {
        $id_evento_settore = isset($_POST['id_evento_settore']) ? (int)$_POST['id_evento_settore'] : 0;
        $quantita = isset($_POST['quantita']) ? (int)$_POST['quantita'] : 1;
        $user_id = (int)$_SESSION['user_id'];

        if ($id_evento_settore <= 0 || $quantita <= 0) {
            $errore_acquisto = "Seleziona replica, settore e quantità valide.";
        } else {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("
                    SELECT es.*, s.nome AS nome_settore
                    FROM evento_settore es
                    JOIN settore s ON es.id_settore = s.id
                    WHERE es.id = ?
                    LIMIT 1
                ");
                $stmt->execute([$id_evento_settore]);
                $settore = $stmt->fetch();

                if (!$settore) {
                    throw new Exception("Settore non valido.");
                }

                if ((int)$settore['posti_disponibili'] < $quantita) {
                    throw new Exception("Posti insufficienti nel settore selezionato.");
                }

                $totale = $quantita * (float)$settore['prezzo'];

                $stmt = $pdo->prepare("SELECT saldo FROM utente WHERE id = ? LIMIT 1");
                $stmt->execute([$user_id]);
                $utente = $stmt->fetch();

                if (!$utente) {
                    throw new Exception("Utente non trovato.");
                }

                if ((float)$utente['saldo'] < $totale) {
                    throw new Exception("Saldo insufficiente.");
                }

                $stmt = $pdo->prepare("UPDATE utente SET saldo = saldo - ? WHERE id = ?");
                $stmt->execute([$totale, $user_id]);

                $stmt = $pdo->prepare("
                    UPDATE evento_settore
                    SET posti_disponibili = posti_disponibili - ?
                    WHERE id = ?
                ");
                $stmt->execute([$quantita, $id_evento_settore]);

                $stmtUltimoPosto = $pdo->prepare("
                    SELECT COALESCE(MAX(posto), 0) AS ultimo_posto
                    FROM biglietto
                    WHERE id_evento_settore = ?
                ");
                $stmtUltimoPosto->execute([$id_evento_settore]);
                $ultimo = $stmtUltimoPosto->fetch();
                $ultimoPosto = (int)($ultimo['ultimo_posto'] ?? 0);

                $stmtBiglietto = $pdo->prepare("
                    INSERT INTO biglietto
                    (sigillo_fiscale, disponibilita, id_utente, id_evento_settore, posto, prezzo)
                    VALUES (?, 1, ?, ?, ?, ?)
                ");

                for ($i = 1; $i <= $quantita; $i++) {
                    $sigillo = substr(bin2hex(random_bytes(10)), 0, 15);
                    $posto = $ultimoPosto + $i;

                    $stmtBiglietto->execute([
                        $sigillo,
                        $user_id,
                        $id_evento_settore,
                        $posto,
                        $settore['prezzo']
                    ]);
                }

                $pdo->commit();
                $messaggio_acquisto = "Acquisto completato con successo.";
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errore_acquisto = $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title><?php echo esc($evento['titolo']); ?> - EasyTicket</title>
    <link rel="stylesheet" href="css/style1.css">
    <link rel="icon" type="image/png" href="img/icn_sito_sf.png">
</head>
<body>
<header class="site-header">
    <div class="header-inner">
        <a href="home.php" class="brand">
            <img src="img/logo_sito.png" alt="EasyTicket">
        </a>
    </div>
</header>

<main class="page-shell">
    <section class="section-block">
        <div class="section-heading">
            <h2><?php echo esc($evento['titolo']); ?></h2>
            <p>
                <?php echo esc($evento['categoria']); ?> · 
                <?php echo esc($evento['luogo']); ?>, 
                <?php echo esc($evento['citta']); ?>
            </p>
        </div>

        <?php if (!empty($evento['immagine'])): ?>
            <div class="admin-card" style="padding:0; overflow:hidden;">
                <img
                    src="<?php echo esc($evento['immagine']); ?>"
                    alt="<?php echo esc($evento['titolo']); ?>"
                    style="width:100%; display:block;"
                >
            </div>
        <?php endif; ?>

        <?php if (!empty($evento['descrizione'])): ?>
            <div class="admin-card" style="margin-top:20px;">
                <p><?php echo nl2br(esc($evento['descrizione'])); ?></p>
            </div>
        <?php endif; ?>

        <div class="admin-card" style="margin-top:20px;">
            <h3>Dettagli utente</h3>

            <?php if ($errore_acquisto): ?>
                <div class="msg-ko"><?php echo esc($errore_acquisto); ?></div>
            <?php endif; ?>

            <?php if ($messaggio_acquisto): ?>
                <div class="msg-ok"><?php echo esc($messaggio_acquisto); ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="admin-form-group">
                    <label for="replica-select">Scegli la replica</label>
                    <?php if (!empty($repliche)): ?>
                        <select id="replica-select" name="id_replica" required>
                            <option value="">Seleziona giorno e orario dello spettacolo che preferisci</option>
                            <?php foreach ($repliche as $replica): ?>
                                <option value="<?php echo (int)$replica['id']; ?>">
                                    <?php echo esc(date('d/m/Y H:i', strtotime($replica['data_ora_inizio']))); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <p>Nessuna replica disponibile. Questo evento non ha ancora date prenotabili.</p>
                    <?php endif; ?>
                </div>

                <div class="admin-form-group">
                    <label for="settore-select">Scegli il settore</label>
                    <select id="settore-select" name="id_evento_settore" required disabled>
                        <option value="">Seleziona prima una replica</option>
                    </select>
                </div>

                <p><strong>Posti disponibili:</strong> <span id="posti-disponibili">-</span></p>
                <p><strong>Prezzo per biglietto:</strong> € <span id="prezzo-settore">-</span></p>

                <div class="admin-form-group">
                    <label for="quantita">Quantità</label>
                    <input type="number" id="quantita" name="quantita" min="1" value="1" required>
                </div>

                <button type="submit" class="admin-submit">Completa acquisto</button>
            </form>
        </div>
    </section>
</main>

<script src="js/evento.js"></script>
</body>
</html>