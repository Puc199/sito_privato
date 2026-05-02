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

$messaggio = "";
$errore    = "";

function getCategorie(mysqli $conn): array {
    $r = $conn->query("SELECT id, nome FROM categoria ORDER BY nome ASC");
    $out = [];
    if ($r) while ($row = $r->fetch_assoc()) $out[] = $row;
    return $out;
}

function getLuoghi(mysqli $conn): array {
    $r = $conn->query("SELECT id, nome, citta, tipo FROM luogo ORDER BY nome ASC");
    $out = [];
    if ($r) while ($row = $r->fetch_assoc()) $out[] = $row;
    return $out;
}

function getSettori(): array {
    return [
        ['id' => 1, 'nome' => 'VIP'],
        ['id' => 2, 'nome' => 'Tribuna'],
        ['id' => 3, 'nome' => 'Curva'],
        ['id' => 4, 'nome' => 'Platea'],
        ['id' => 5, 'nome' => 'Galleria']
    ];
}

function generaDateIntervallo(string $a, string $b): array {
    $date = [];
    $s = new DateTime($a); $s->setTime(0,0,0);
    $e = new DateTime($b); $e->setTime(0,0,0);
    if ($s > $e) return [];
    $c = clone $s;
    while ($c <= $e) { $date[] = $c->format('Y-m-d'); $c->modify('+1 day'); }
    return $date;
}

function salvaImmagine(?array $file): ?string {
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK) throw new Exception("Errore caricamento immagine.");
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','webp'], true)) throw new Exception("Formato non consentito.");
    $dir = __DIR__ . '/img/eventi/';
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $nome = time() . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '-', pathinfo($file['name'], PATHINFO_FILENAME)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . $nome)) throw new Exception("Errore salvataggio file.");
    return 'img/eventi/' . $nome;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['azione'] ?? '') === 'aggiungi_evento') {
        $titolo            = trim($_POST['titolo'] ?? '');
        $descrizione       = trim($_POST['descrizione'] ?? '');
        $id_categoria      = (int)($_POST['id_categoria'] ?? 0);
        $id_luogo          = (int)($_POST['id_luogo'] ?? 0);
        $data_inizio       = trim($_POST['data_inizio'] ?? '');
        $data_fine         = trim($_POST['data_fine'] ?? '');
        $orario_spettacolo = trim($_POST['orario_spettacolo'] ?? '');
        $settori_input     = $_POST['settori'] ?? [];

        try {
            if ($titolo==='' || $id_categoria<=0 || $id_luogo<=0 || $data_inizio==='' || $data_fine==='' || $orario_spettacolo==='')
                throw new Exception("Compila tutti i campi obbligatori.");

            $dateGenerate = generaDateIntervallo($data_inizio, $data_fine);
            if (empty($dateGenerate)) throw new Exception("Intervallo date non valido.");

            $settoriValidi = [];
            foreach ($settori_input as $s) {
                $prezzo = str_replace(',', '.', trim((string)($s['prezzo'] ?? '')));
                $posti  = (int)($s['posti_totali'] ?? 0);
                $id_s   = (int)($s['id_settore'] ?? 0);
                if ($id_s > 0 && is_numeric($prezzo) && (float)$prezzo >= 0 && $posti > 0)
                    $settoriValidi[] = ['id' => $id_s, 'prezzo' => (float)$prezzo, 'posti' => $posti];
            }
            if (empty($settoriValidi)) throw new Exception("Inserisci almeno un settore valido.");

            $immagine = salvaImmagine($_FILES['immagine'] ?? null);

            $conn->begin_transaction();

            $stmt = $conn->prepare("INSERT INTO evento (titolo, descrizione, id_categoria, id_luogo, immagine, stato) VALUES (?, ?, ?, ?, ?, 'programmato')");
            if (!$stmt) throw new Exception("Errore prepare evento: " . $conn->error);
            $stmt->bind_param("ssiis", $titolo, $descrizione, $id_categoria, $id_luogo, $immagine);
            $stmt->execute();
            $id_evento = $conn->insert_id;
            $stmt->close();

            $nRep = 0;
            foreach ($dateGenerate as $d) {
                $dataOraInizio = $d . ' ' . $orario_spettacolo . ':00';
                $stmt = $conn->prepare("INSERT INTO replica_evento (id_evento, data_ora_inizio, stato) VALUES (?, ?, 'programmata')");
                if (!$stmt) throw new Exception("Errore prepare replica: " . $conn->error);
                $stmt->bind_param("is", $id_evento, $dataOraInizio);
                $stmt->execute();
                $id_replica = $conn->insert_id;
                $stmt->close();
                $nRep++;

                foreach ($settoriValidi as $sv) {
                    $stmt = $conn->prepare("INSERT INTO evento_settore (id_replica_evento, id_evento, id_settore, prezzo, posti_totali, posti_disponibili) VALUES (?, ?, ?, ?, ?, ?)");
                    if (!$stmt) throw new Exception("Errore prepare settore: " . $conn->error);
                    $stmt->bind_param("iiidii", $id_replica, $id_evento, $sv['id'], $sv['prezzo'], $sv['posti'], $sv['posti']);
                    $stmt->execute();
                    $stmt->close();
                }
            }

            $conn->commit();
            $messaggio = "Evento creato. Repliche generate: " . $nRep . ".";
        } catch (Throwable $e) {
            try { $conn->rollback(); } catch (Throwable $ignored) {}
            $errore = $e->getMessage();
        }
    }

    if (($_POST['azione'] ?? '') === 'elimina_evento') {
        $id_el = (int)($_POST['id_evento'] ?? 0);
        try {
            if ($id_el <= 0) throw new Exception("Evento non valido.");
            $stmt = $conn->prepare("SELECT immagine FROM evento WHERE id = ? LIMIT 1");
            $stmt->bind_param("i", $id_el);
            $stmt->execute();
            $ev = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $conn->prepare("DELETE FROM evento WHERE id = ?")->execute() ;
            $stmt2 = $conn->prepare("DELETE FROM evento WHERE id = ?");
            $stmt2->bind_param("i", $id_el);
            $stmt2->execute();
            $stmt2->close();
            if ($ev && !empty($ev['immagine'])) {
                $p = __DIR__ . '/' . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $ev['immagine']);
                if (is_file($p)) @unlink($p);
            }
            $messaggio = "Evento eliminato.";
        } catch (Throwable $e) { $errore = $e->getMessage(); }
    }
}

$categorie         = getCategorie($conn);
$luoghi            = getLuoghi($conn);
$settoriDisponibili = getSettori();

$eventi = [];
$res = $conn->query("
    SELECT
        e.id, e.titolo, e.descrizione, e.immagine, e.stato,
        c.nome AS categoria,
        l.nome AS luogo, l.citta,
        MIN(r.data_ora_inizio) AS data_evento,
        COUNT(DISTINCT r.id)  AS numero_repliche
    FROM evento e
    JOIN categoria c ON e.id_categoria = c.id
    JOIN luogo l     ON e.id_luogo = l.id
    LEFT JOIN replica_evento r ON r.id_evento = e.id
    GROUP BY e.id, e.titolo, e.descrizione, e.immagine, e.stato, c.nome, l.nome, l.citta
    ORDER BY data_evento DESC, e.id DESC
");
if ($res) while ($row = $res->fetch_assoc()) $eventi[] = $row;
$conn->close();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - EasyTicket</title>
    <link rel="stylesheet" href="css/style1.css?v=51">
    <link rel="icon" type="image/png" href="img/icn_sito_sf.png">
    <style>
        .range-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px}
        .settori-builder{display:grid;gap:16px;margin-top:20px}
        .settore-row{display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:12px;align-items:end;padding:14px;border:1px solid #d9e0e8;border-radius:14px;background:#f8fbff}
        .mini-label{display:block;margin-bottom:6px;font-weight:600;color:#17324d;font-size:14px}
        .row-remove-btn{background:#d84b38;color:#fff;border:none;border-radius:10px;padding:12px 14px;cursor:pointer;font-weight:700}
        .secondary-btn{background:#eef4fa;color:#17324d;border:1px solid #d9e0e8;border-radius:10px;padding:12px 16px;cursor:pointer;font-weight:700}
        .dashboard-actions{display:flex;gap:10px;flex-wrap:wrap}
        .table-wrap{overflow-x:auto}
        .admin-table{width:100%;border-collapse:collapse;background:#fff;border-radius:16px;overflow:hidden}
        .admin-table th,.admin-table td{padding:14px 12px;border-bottom:1px solid #e7edf3;text-align:left;vertical-align:middle}
        .admin-table th{background:#f4f8fc;color:#17324d;font-size:14px}
        .admin-table td{color:#28435c;font-size:15px}
        .thumb-evento{width:84px;height:56px;object-fit:cover;border-radius:10px;background:#eef2f6}
        .msg-ok{border-color:#cfe8d3!important;color:#277243!important;background:#f3fbf4}
        .msg-ko{border-color:#f1d1ca!important;color:#c13d2a!important;background:#fff7f5}
        @media(max-width:768px){.settore-row{grid-template-columns:1fr}}
    </style>
</head>
<body>
<header class="site-header">
    <div class="header-inner">
        <a href="home.php" class="brand"><img src="img/logo_sito.png" alt="EasyTicket"></a>
        <nav class="user-nav">
            <a href="admin_dashboard.php" class="user-pill primary-pill">admin</a>
            <a href="logout.php" class="user-pill secondary-pill">Logout</a>
        </nav>
    </div>
</header>

<main class="page-shell">
    <section class="section-block">
        <div class="section-heading">
            <h2>Area Amministrazione</h2>
            <p>Gestione eventi, repliche e settori prenotabili.</p>
        </div>
        <?php if ($messaggio !== ""): ?>
            <div class="admin-card msg-ok" style="margin-top:20px"><?php echo esc($messaggio); ?></div>
        <?php endif; ?>
        <?php if ($errore !== ""): ?>
            <div class="admin-card msg-ko" style="margin-top:20px"><?php echo esc($errore); ?></div>
        <?php endif; ?>
    </section>

    <section class="section-block">
        <div class="section-heading">
            <h2>Aggiungi Nuovo Evento</h2>
            <p>Crea l'evento principale, il range di giorni e le repliche automatiche.</p>
        </div>
        <form method="post" enctype="multipart/form-data" class="admin-card">
            <input type="hidden" name="azione" value="aggiungi_evento">
            <div class="admin-grid">
                <div class="admin-form-group">
                    <label>Titolo Evento</label>
                    <input type="text" name="titolo" required>
                </div>
                <div class="admin-form-group">
                    <label>Categoria</label>
                    <select name="id_categoria" required>
                        <option value="">Seleziona...</option>
                        <?php foreach ($categorie as $c): ?>
                            <option value="<?php echo (int)$c['id']; ?>"><?php echo esc($c['nome']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="admin-form-group">
                    <label>Luogo</label>
                    <select name="id_luogo" required>
                        <option value="">Seleziona...</option>
                        <?php foreach ($luoghi as $l): ?>
                            <option value="<?php echo (int)$l['id']; ?>"><?php echo esc($l['nome'] . ' - ' . $l['citta']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="admin-form-group">
                    <label>Immagine Copertina</label>
                    <input type="file" name="immagine" accept=".jpg,.jpeg,.png,.webp">
                </div>
            </div>
            <div class="admin-form-group">
                <label>Descrizione</label>
                <textarea name="descrizione" rows="4" placeholder="Descrizione..."></textarea>
            </div>
            <div class="range-grid">
                <div class="admin-form-group"><label>Data inizio</label><input type="date" name="data_inizio" required></div>
                <div class="admin-form-group"><label>Data fine</label><input type="date" name="data_fine" required></div>
                <div class="admin-form-group"><label>Orario spettacolo</label><input type="time" name="orario_spettacolo" required></div>
            </div>
            <div class="section-heading" style="margin-top:28px">
                <h2>Settori</h2>
                <p>Questi settori verranno associati a tutte le repliche create.</p>
            </div>
            <div id="settori-builder" class="settori-builder">
                <div class="settore-row">
                    <div>
                        <label class="mini-label">Settore</label>
                        <select name="settori[0][id_settore]" required>
                            <option value="">Seleziona</option>
                            <?php foreach ($settoriDisponibili as $s): ?>
                                <option value="<?php echo (int)$s['id']; ?>"><?php echo esc($s['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="mini-label">Prezzo (€)</label>
                        <input type="number" step="0.01" min="0" name="settori[0][prezzo]" required>
                    </div>
                    <div>
                        <label class="mini-label">Posti totali</label>
                        <input type="number" min="1" name="settori[0][posti_totali]" required>
                    </div>
                    <div>
                        <button type="button" class="row-remove-btn" onclick="removeRow(this)">Rimuovi</button>
                    </div>
                </div>
            </div>
            <div style="margin-top:14px" class="dashboard-actions">
                <button type="button" class="secondary-btn" onclick="addRow()">Aggiungi settore</button>
                <button type="submit" class="admin-submit">Crea evento e repliche</button>
            </div>
        </form>
    </section>

    <section class="section-block">
        <div class="section-heading">
            <h2>Eventi Esistenti</h2>
            <p>Elenco degli eventi presenti nel catalogo.</p>
        </div>
        <?php if (empty($eventi)): ?>
            <div class="empty-state">
                <h3>Nessun evento presente</h3>
                <p>Non ci sono ancora eventi nel database.</p>
            </div>
        <?php else: ?>
            <div class="admin-card table-wrap">
                <table class="admin-table">
                    <thead>
                    <tr>
                        <th>Immagine</th><th>Titolo</th><th>Categoria</th><th>Luogo</th>
                        <th>Prima data</th><th>Repliche</th><th>Stato</th><th>Azioni</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($eventi as $ev): ?>
                        <tr>
                            <td>
                                <img class="thumb-evento"
                                     src="<?php echo !empty($ev['immagine']) ? esc($ev['immagine']) : 'img/evento-default.png'; ?>"
                                     alt="<?php echo esc($ev['titolo']); ?>">
                            </td>
                            <td>
                                <strong><?php echo esc($ev['titolo']); ?></strong><br>
                                <small><?php echo esc(mb_strimwidth((string)($ev['descrizione'] ?? ''), 0, 80, '...')); ?></small>
                            </td>
                            <td><?php echo esc($ev['categoria']); ?></td>
                            <td><?php echo esc($ev['luogo'] . ' - ' . $ev['citta']); ?></td>
                            <td><?php echo !empty($ev['data_evento']) ? esc(date('d/m/Y H:i', strtotime($ev['data_evento']))) : 'N/D'; ?></td>
                            <td><?php echo (int)$ev['numero_repliche']; ?></td>
                            <td><?php echo esc($ev['stato']); ?></td>
                            <td>
                                <div class="dashboard-actions">
                                    <a href="evento.php?id=<?php echo (int)$ev['id']; ?>" class="hero-cta">Apri</a>
                                    <a href="modifica_evento.php?id=<?php echo (int)$ev['id']; ?>" class="secondary-btn" style="text-decoration:none;display:inline-flex;align-items:center">Modifica</a>
                                    <form method="post" onsubmit="return confirm('Eliminare questo evento?')" style="display:inline">
                                        <input type="hidden" name="azione" value="elimina_evento">
                                        <input type="hidden" name="id_evento" value="<?php echo (int)$ev['id']; ?>">
                                        <button type="submit" class="row-remove-btn">Elimina</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>

<footer class="site-footer"><p>&copy; 2026 EasyTicket</p></footer>

<script>
let idx = 1;
const opts = `<?php foreach ($settoriDisponibili as $s): ?><option value="<?php echo (int)$s['id']; ?>"><?php echo esc($s['nome']); ?></option><?php endforeach; ?>`;
function addRow() {
    const b = document.getElementById('settori-builder');
    const d = document.createElement('div');
    d.className = 'settore-row';
    d.innerHTML = `
        <div><label class="mini-label">Settore</label>
        <select name="settori[${idx}][id_settore]" required><option value="">Seleziona</option>${opts}</select></div>
        <div><label class="mini-label">Prezzo (€)</label><input type="number" step="0.01" min="0" name="settori[${idx}][prezzo]" required></div>
        <div><label class="mini-label">Posti totali</label><input type="number" min="1" name="settori[${idx}][posti_totali]" required></div>
        <div><button type="button" class="row-remove-btn" onclick="removeRow(this)">Rimuovi</button></div>`;
    b.appendChild(d);
    idx++;
}
function removeRow(btn) {
    if (document.querySelectorAll('.settore-row').length <= 1) { alert('Almeno un settore richiesto.'); return; }
    btn.closest('.settore-row').remove();
}
</script>
</body>
</html>