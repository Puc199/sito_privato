<?php
require_once 'init.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit();
}

if ((int)($_SESSION['ruolo'] ?? 0) !== 1) {
    header('Location: home.php');
    exit();
}

$id_evento = (int)($_GET['id'] ?? 0);
if ($id_evento <= 0) {
    die('Evento non valido.');
}

$messaggio = '';
$errore = '';

function salvaImmagineEvento(?array $file, ?string $immagineAttuale = null): ?string {
    if (!$file || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return $immagineAttuale;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Errore durante il caricamento dell\'immagine.');
    }

    $estensione = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $consentite = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($estensione, $consentite, true)) {
        throw new Exception('Formato immagine non consentito. Usa JPG, PNG o WEBP.');
    }

    $dirFs = __DIR__ . '/img/eventi/';
    $dirDb = 'img/eventi/';

    if (!is_dir($dirFs) && !mkdir($dirFs, 0775, true)) {
        throw new Exception('Impossibile creare la cartella img/eventi.');
    }

    $nomePulito = preg_replace('/[^a-zA-Z0-9_-]/', '-', pathinfo($file['name'], PATHINFO_FILENAME));
    $nomePulito = trim($nomePulito, '-');
    if ($nomePulito === '') {
        $nomePulito = 'evento';
    }

    $nomeFile = time() . '_' . $nomePulito . '.' . $estensione;
    $targetFs = $dirFs . $nomeFile;
    $targetDb = $dirDb . $nomeFile;

    if (!move_uploaded_file($file['tmp_name'], $targetFs)) {
        throw new Exception('Errore nel salvataggio della nuova immagine.');
    }

    if (!empty($immagineAttuale)) {
        $vecchia = __DIR__ . '/' . ltrim($immagineAttuale, '/');
        if (is_file($vecchia)) {
            @unlink($vecchia);
        }
    }

    return $targetDb;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $azione = $_POST['azione'] ?? '';

    if ($azione === 'modifica_evento') {
        $titolo = trim($_POST['titolo'] ?? '');
        $descrizione = trim($_POST['descrizione'] ?? '');
        $id_categoria = (int)($_POST['id_categoria'] ?? 0);
        $id_luogo = (int)($_POST['id_luogo'] ?? 0);
        $stato = trim($_POST['stato'] ?? 'programmato');

        try {
            if ($titolo === '' || $id_categoria <= 0 || $id_luogo <= 0) {
                throw new Exception('Compila tutti i campi obbligatori dell\'evento.');
            }

            $statiValidi = ['programmato', 'annullato', 'completato'];
            if (!in_array($stato, $statiValidi, true)) {
                $stato = 'programmato';
            }

            $stmtOld = $pdo->prepare('SELECT immagine FROM evento WHERE id = ? LIMIT 1');
            $stmtOld->execute([$id_evento]);
            $oldEvento = $stmtOld->fetch();
            if (!$oldEvento) {
                throw new Exception('Evento non trovato.');
            }

            $immagine = salvaImmagineEvento($_FILES['immagine'] ?? null, $oldEvento['immagine'] ?? null);

            $stmt = $pdo->prepare('UPDATE evento SET titolo = ?, descrizione = ?, id_categoria = ?, id_luogo = ?, immagine = ?, stato = ? WHERE id = ?');
            $stmt->execute([$titolo, $descrizione, $id_categoria, $id_luogo, $immagine, $stato, $id_evento]);

            $messaggio = 'Evento aggiornato con successo.';
        } catch (Throwable $e) {
            $errore = $e->getMessage();
        }
    }

    if ($azione === 'aggiungi_replica') {
        $data_ora_inizio = trim($_POST['data_ora_inizio'] ?? '');
        $data_ora_fine = trim($_POST['data_ora_fine'] ?? '');
        $stato_replica = trim($_POST['stato_replica'] ?? 'programmata');

        try {
            if ($data_ora_inizio === '') {
                throw new Exception('Inserisci almeno data e ora di inizio per la replica.');
            }

            $statiReplicaValidi = ['programmata', 'annullata', 'completata'];
            if (!in_array($stato_replica, $statiReplicaValidi, true)) {
                $stato_replica = 'programmata';
            }

            $dataInizioSql = str_replace('T', ' ', $data_ora_inizio) . ':00';
            $dataFineSql = $data_ora_fine !== '' ? str_replace('T', ' ', $data_ora_fine) . ':00' : null;

            $stmt = $pdo->prepare('INSERT INTO replica_evento (id_evento, data_ora_inizio, data_ora_fine, stato) VALUES (?, ?, ?, ?)');
            $stmt->execute([$id_evento, $dataInizioSql, $dataFineSql, $stato_replica]);

            $id_replica = (int)$pdo->lastInsertId();

            $stmtSettoriOrig = $pdo->prepare('SELECT id_settore, prezzo, posti_totali, posti_disponibili FROM evento_settore WHERE id_evento = ? ORDER BY id ASC LIMIT 100');
            $stmtSettoriOrig->execute([$id_evento]);
            $settoriOrig = $stmtSettoriOrig->fetchAll();

            if (!empty($settoriOrig)) {
                $giaInseriti = [];
                $stmtInsSett = $pdo->prepare('INSERT INTO evento_settore (id_replica_evento, id_evento, id_settore, prezzo, posti_totali, posti_disponibili) VALUES (?, ?, ?, ?, ?, ?)');
                foreach ($settoriOrig as $s) {
                    $chiave = (int)$s['id_settore'];
                    if (isset($giaInseriti[$chiave])) {
                        continue;
                    }
                    $giaInseriti[$chiave] = true;
                    $stmtInsSett->execute([$id_replica, $id_evento, $s['id_settore'], $s['prezzo'], $s['posti_totali'], $s['posti_disponibili']]);
                }
            }

            syncDataEvento($pdo, $id_evento);
            $messaggio = 'Replica aggiunta con successo.';
        } catch (Throwable $e) {
            $errore = $e->getMessage();
        }
    }

    if ($azione === 'elimina_replica') {
        $id_replica = (int)($_POST['id_replica'] ?? 0);

        try {
            if ($id_replica <= 0) {
                throw new Exception('Replica non valida.');
            }

            $stmt = $pdo->prepare('SELECT COUNT(*) AS n FROM biglietto b INNER JOIN evento_settore es ON b.id_evento_settore = es.id WHERE es.id_replica_evento = ?');
            $stmt->execute([$id_replica]);
            $row = $stmt->fetch();

            if ((int)($row['n'] ?? 0) > 0) {
                throw new Exception('Non puoi eliminare questa replica: ci sono già biglietti acquistati.');
            }

            $stmt = $pdo->prepare('DELETE FROM replica_evento WHERE id = ? AND id_evento = ?');
            $stmt->execute([$id_replica, $id_evento]);

            syncDataEvento($pdo, $id_evento);
            $messaggio = 'Replica eliminata con successo.';
        } catch (Throwable $e) {
            $errore = $e->getMessage();
        }
    }
}

$categorie = $pdo->query('SELECT id, nome FROM categoria ORDER BY nome ASC')->fetchAll();
$luoghi = $pdo->query('SELECT id, nome, citta FROM luogo ORDER BY citta, nome ASC')->fetchAll();

$stmt = $pdo->prepare('SELECT * FROM evento WHERE id = ? LIMIT 1');
$stmt->execute([$id_evento]);
$evento = $stmt->fetch();

if (!$evento) {
    die('Evento non trovato.');
}

$stmt = $pdo->prepare('SELECT id, data_ora_inizio, data_ora_fine, stato FROM replica_evento WHERE id_evento = ? ORDER BY data_ora_inizio ASC');
$stmt->execute([$id_evento]);
$repliche = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifica Evento - EasyTicket</title>
    <link rel="icon" type="image/x-icon" href="img/icn_sito_sf.png">
    <link rel="stylesheet" href="css/style1.css?v=70">
    <style>
        .mini-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:16px; }
        .msg-ok { border-color:#cfe8d3 !important; color:#277243 !important; background:#f3fbf4; }
        .msg-ko { border-color:#f1d1ca !important; color:#c13d2a !important; background:#fff7f5; }
        .replica-list { display:grid; gap:14px; margin-top:18px; }
        .replica-item { border:1px solid #d9e0e8; border-radius:14px; padding:16px; background:#fff; display:flex; justify-content:space-between; gap:16px; align-items:center; flex-wrap:wrap; }
        .replica-meta strong { color:#17324d; display:block; margin-bottom:6px; }
        .danger-btn { background:#d84b38; color:#fff; border:none; border-radius:10px; padding:10px 14px; font-weight:700; cursor:pointer; }
        .secondary-btn { background:#eef4fa; color:#17324d; border:1px solid #d9e0e8; border-radius:10px; padding:10px 16px; cursor:pointer; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; }
        .thumb-preview { width:160px; height:100px; object-fit:cover; border-radius:12px; background:#eef2f6; }
    </style>
</head>
<body>
<header class="site-header">
    <div class="header-inner">
        <a href="home.php" class="brand">
            <img src="img/logo_sito.png" alt="Logo EasyTicket">
        </a>
        <nav class="user-nav">
            <a href="admin_dashboard.php" class="user-pill primary-pill">admin</a>
            <a href="logout.php" class="user-pill secondary-pill">Logout</a>
        </nav>
    </div>
</header>

<main class="page-shell">
    <section class="section-block">
        <div class="section-heading">
            <h2>Modifica Evento</h2>
            <p>Gestisci dati principali e repliche dell'evento selezionato.</p>
        </div>

        <?php if ($messaggio !== ''): ?>
            <div class="admin-card msg-ok" style="margin-top: 20px;">
                <?php echo esc($messaggio); ?>
            </div>
        <?php endif; ?>

        <?php if ($errore !== ''): ?>
            <div class="admin-card msg-ko" style="margin-top: 20px;">
                <?php echo esc($errore); ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="section-block">
        <div class="admin-grid">
            <div class="admin-card">
                <h3>Dati evento</h3>

                <form method="post" enctype="multipart/form-data" style="margin-top:16px;">
                    <input type="hidden" name="azione" value="modifica_evento">

                    <div class="admin-form-group">
                        <label for="titolo">Titolo</label>
                        <input type="text" id="titolo" name="titolo" value="<?php echo esc($evento['titolo']); ?>" required>
                    </div>

                    <div class="admin-form-group">
                        <label for="descrizione">Descrizione</label>
                        <textarea id="descrizione" name="descrizione" rows="5"><?php echo esc($evento['descrizione'] ?? ''); ?></textarea>
                    </div>

                    <div class="mini-grid">
                        <div class="admin-form-group">
                            <label for="id_categoria">Categoria</label>
                            <select id="id_categoria" name="id_categoria" required>
                                <option value="">Seleziona...</option>
                                <?php foreach ($categorie as $categoria): ?>
                                    <option value="<?php echo (int)$categoria['id']; ?>" <?php echo ((int)$evento['id_categoria'] === (int)$categoria['id']) ? 'selected' : ''; ?>>
                                        <?php echo esc($categoria['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="admin-form-group">
                            <label for="id_luogo">Luogo</label>
                            <select id="id_luogo" name="id_luogo" required>
                                <option value="">Seleziona...</option>
                                <?php foreach ($luoghi as $luogo): ?>
                                    <option value="<?php echo (int)$luogo['id']; ?>" <?php echo ((int)$evento['id_luogo'] === (int)$luogo['id']) ? 'selected' : ''; ?>>
                                        <?php echo esc($luogo['citta'] . ' - ' . $luogo['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="admin-form-group">
                            <label for="stato">Stato evento</label>
                            <select id="stato" name="stato">
                                <option value="programmato" <?php echo (($evento['stato'] ?? '') === 'programmato') ? 'selected' : ''; ?>>Programmato</option>
                                <option value="annullato" <?php echo (($evento['stato'] ?? '') === 'annullato') ? 'selected' : ''; ?>>Annullato</option>
                                <option value="completato" <?php echo (($evento['stato'] ?? '') === 'completato') ? 'selected' : ''; ?>>Completato</option>
                            </select>
                        </div>

                        <div class="admin-form-group">
                            <label for="immagine">Nuova immagine</label>
                            <input type="file" id="immagine" name="immagine" accept=".jpg,.jpeg,.png,.webp">
                        </div>
                    </div>

                    <div class="mini-grid" style="align-items:end; margin-top:8px;">
                        
                    </div>

                    <div style="margin-top:18px; display:flex; gap:10px; flex-wrap:wrap;">
                        <button type="submit" class="admin-submit">Salva modifiche evento</button>
                        <a href="admin_dashboard.php" class="secondary-btn">Torna alla dashboard</a>
                    </div>
                </form>
            </div>

            <div class="admin-card">
                <h3>Aggiungi replica</h3>
                <p style="margin-top:6px; color:#5c7389;">Qui puoi inserire più date o più orari per lo stesso evento.</p>

                <form method="post" style="margin-top:18px;">
                    <input type="hidden" name="azione" value="aggiungi_replica">

                    <div class="admin-form-group">
                        <label for="data_ora_inizio">Data e ora inizio</label>
                        <input type="datetime-local" id="data_ora_inizio" name="data_ora_inizio" required>
                    </div>

                    <div class="admin-form-group">
                        <label for="data_ora_fine">Data e ora fine</label>
                        <input type="datetime-local" id="data_ora_fine" name="data_ora_fine">
                    </div>

                    <button type="submit" class="admin-submit">Aggiungi replica</button>
                </form>
            </div>
        </div>
    </section>

    <section class="section-block">
        <div class="section-heading">
            <h2>Repliche esistenti</h2>
            <p>Puoi vedere e cancellare le repliche già associate a questo evento.</p>
        </div>

        <?php if (empty($repliche)): ?>
            <div class="empty-state">
                <h3>Nessuna replica presente</h3>
                <p>Aggiungi almeno una replica per permettere la scelta di data e orario.</p>
            </div>
        <?php else: ?>
            <div class="replica-list">
                <?php foreach ($repliche as $replica): ?>
                    <div class="replica-item">
                        <div class="replica-meta">
                            <strong><?php echo esc(date('d/m/Y H:i', strtotime($replica['data_ora_inizio']))); ?></strong>
                            <div>Fine: <?php echo !empty($replica['data_ora_fine']) ? esc(date('d/m/Y H:i', strtotime($replica['data_ora_fine']))) : 'non impostata'; ?></div>
                            <div>Stato: <?php echo esc($replica['stato']); ?></div>
                        </div>

                        <form method="post" onsubmit="return confirm('Vuoi eliminare questa replica?');">
                            <input type="hidden" name="azione" value="elimina_replica">
                            <input type="hidden" name="id_replica" value="<?php echo (int)$replica['id']; ?>">
                            <button type="submit" class="danger-btn">Elimina replica</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<footer class="site-footer">
    <p>&copy; 2026 EasyTicket</p>
</footer>
</body>
</html>
