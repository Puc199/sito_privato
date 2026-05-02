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
$errore = "";

function tableExists(mysqli $conn, string $tableName): bool {
    $safeName = $conn->real_escape_string($tableName);
    $sql = "SHOW TABLES LIKE '{$safeName}'";
    $result = $conn->query($sql);
    return $result && $result->num_rows > 0;
}

function getCategorie(mysqli $conn): array {
    $categorie = [];
    $sql = "SELECT id, nome FROM categoria ORDER BY nome ASC";
    $result = $conn->query($sql);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $categorie[] = $row;
        }
    }

    return $categorie;
}

function getLuoghi(mysqli $conn): array {
    $luoghi = [];
    $sql = "SELECT id, nome, citta, tipo FROM luogo ORDER BY nome ASC";
    $result = $conn->query($sql);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $luoghi[] = $row;
        }
    }

    return $luoghi;
}

function getSettori(mysqli $conn): array {
    if (tableExists($conn, 'settore')) {
        $settori = [];
        $sql = "SELECT id, nome, descrizione FROM settore ORDER BY id ASC";
        $result = $conn->query($sql);

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $settori[] = $row;
            }
        }

        if (!empty($settori)) {
            return $settori;
        }
    }

    return [
        ['id' => 1, 'nome' => 'VIP', 'descrizione' => 'Posti premium'],
        ['id' => 2, 'nome' => 'Tribuna', 'descrizione' => 'Posti centrali numerati'],
        ['id' => 3, 'nome' => 'Curva', 'descrizione' => 'Settore popolare'],
        ['id' => 4, 'nome' => 'Platea', 'descrizione' => 'Posti in platea'],
        ['id' => 5, 'nome' => 'Galleria', 'descrizione' => 'Posti in galleria']
    ];
}

function generaDateIntervallo(string $dataInizio, string $dataFine): array {
    $date = [];

    $inizio = new DateTime($dataInizio);
    $fine = new DateTime($dataFine);
    $inizio->setTime(0, 0, 0);
    $fine->setTime(0, 0, 0);

    if ($inizio > $fine) {
        return [];
    }

    $current = clone $inizio;
    while ($current <= $fine) {
        $date[] = $current->format('Y-m-d');
        $current->modify('+1 day');
    }

    return $date;
}

function salvaImmagineEvento(?array $file): ?string {
    if (!isset($file) || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Errore durante il caricamento dell'immagine.");
    }

    $estensione = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $estensioniConsentite = ['jpg', 'jpeg', 'png', 'webp'];

    if (!in_array($estensione, $estensioniConsentite, true)) {
        throw new Exception("Formato immagine non consentito. Usa JPG, PNG o WEBP.");
    }

    $cartellaRelativa = "img/eventi/";
    $cartellaAssoluta = __DIR__ . DIRECTORY_SEPARATOR . "img" . DIRECTORY_SEPARATOR . "eventi" . DIRECTORY_SEPARATOR;

    if (!is_dir($cartellaAssoluta)) {
        if (!mkdir($cartellaAssoluta, 0775, true)) {
            throw new Exception("Impossibile creare la cartella immagini.");
        }
    }

    if (!is_writable($cartellaAssoluta)) {
        throw new Exception("La cartella img/eventi non è scrivibile.");
    }

    $nomePulito = preg_replace('/[^a-zA-Z0-9_-]/', '-', pathinfo($file['name'], PATHINFO_FILENAME));
    $nomePulito = trim((string)$nomePulito, '-');

    if ($nomePulito === '') {
        $nomePulito = 'evento';
    }

    $nomeFile = time() . "_" . $nomePulito . "." . $estensione;
    $percorsoFinaleAssoluto = $cartellaAssoluta . $nomeFile;
    $percorsoFinaleRelativo = $cartellaRelativa . $nomeFile;

    if (!move_uploaded_file($file['tmp_name'], $percorsoFinaleAssoluto)) {
        throw new Exception("Errore nel caricamento dell'immagine.");
    }

    return $percorsoFinaleRelativo;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['azione']) && $_POST['azione'] === 'aggiungi_evento') {
        $titolo = trim($_POST['titolo'] ?? '');
        $descrizione = trim($_POST['descrizione'] ?? '');
        $id_categoria = (int)($_POST['id_categoria'] ?? 0);
        $id_luogo = (int)($_POST['id_luogo'] ?? 0);
        $data_inizio = trim($_POST['data_inizio'] ?? '');
        $data_fine = trim($_POST['data_fine'] ?? '');
        $orario_spettacolo = trim($_POST['orario_spettacolo'] ?? '');
        $settori_input = $_POST['settori'] ?? [];

        try {
            if ($titolo === '' || $id_categoria <= 0 || $id_luogo <= 0 || $data_inizio === '' || $data_fine === '' || $orario_spettacolo === '') {
                throw new Exception("Compila tutti i campi obbligatori dell'evento.");
            }

            $dateGenerate = generaDateIntervallo($data_inizio, $data_fine);
            if (empty($dateGenerate)) {
                throw new Exception("Intervallo date non valido.");
            }

            if (!is_array($settori_input) || empty($settori_input)) {
                throw new Exception("Inserisci almeno un settore.");
            }

            $settoriValidi = [];
            foreach ($settori_input as $settoreRow) {
                $id_settore = (int)($settoreRow['id_settore'] ?? 0);
                $prezzo = str_replace(',', '.', trim((string)($settoreRow['prezzo'] ?? '')));
                $posti_totali = (int)($settoreRow['posti_totali'] ?? 0);

                if ($id_settore > 0 && $prezzo !== '' && is_numeric($prezzo) && $posti_totali > 0) {
                    $settoriValidi[] = [
                        'id_settore' => $id_settore,
                        'prezzo' => (float)$prezzo,
                        'posti_totali' => $posti_totali,
                        'posti_disponibili' => $posti_totali
                    ];
                }
            }

            if (empty($settoriValidi)) {
                throw new Exception("Devi inserire almeno un settore valido con prezzo e posti.");
            }

            $immagine = salvaImmagineEvento($_FILES['immagine'] ?? null);
            $primaDataEvento = $dateGenerate[0] . ' ' . $orario_spettacolo . ':00';

            $conn->begin_transaction();

            $statoEvento = 'programmato';
            $stmtEvento = $conn->prepare("
                INSERT INTO evento (titolo, descrizione, data_evento, id_categoria, id_luogo, immagine, stato)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            if (!$stmtEvento) {
                throw new Exception("Errore preparazione inserimento evento: " . $conn->error);
            }

            $stmtEvento->bind_param(
                "sssiiss",
                $titolo,
                $descrizione,
                $primaDataEvento,
                $id_categoria,
                $id_luogo,
                $immagine,
                $statoEvento
            );
            $stmtEvento->execute();
            $id_evento = $conn->insert_id;
            $stmtEvento->close();

            $stmtReplica = $conn->prepare("
                INSERT INTO replica_evento (id_evento, data_ora_inizio, data_ora_fine, stato)
                VALUES (?, ?, ?, ?)
            ");

            if (!$stmtReplica) {
                throw new Exception("Errore preparazione inserimento repliche: " . $conn->error);
            }

            $stmtSettore = $conn->prepare("
                INSERT INTO evento_settore (id_replica_evento, id_evento, id_settore, prezzo, posti_totali, posti_disponibili)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            if (!$stmtSettore) {
                throw new Exception("Errore preparazione inserimento settori: " . $conn->error);
            }

            $statoReplica = 'programmata';
            $numeroRepliche = 0;

            foreach ($dateGenerate as $dataSingola) {
                $dataOraInizio = $dataSingola . ' ' . $orario_spettacolo . ':00';
                $dataOraFine = null;

                $stmtReplica->bind_param(
                    "isss",
                    $id_evento,
                    $dataOraInizio,
                    $dataOraFine,
                    $statoReplica
                );
                $stmtReplica->execute();

                $id_replica = $conn->insert_id;
                $numeroRepliche++;

                foreach ($settoriValidi as $settore) {
                    $id_settore = $settore['id_settore'];
                    $prezzo = $settore['prezzo'];
                    $posti_totali = $settore['posti_totali'];
                    $posti_disponibili = $settore['posti_disponibili'];

                    $stmtSettore->bind_param(
                        "iiidii",
                        $id_replica,
                        $id_evento,
                        $id_settore,
                        $prezzo,
                        $posti_totali,
                        $posti_disponibili
                    );
                    $stmtSettore->execute();
                }
            }

            $stmtReplica->close();
            $stmtSettore->close();

            $conn->commit();
            $messaggio = "Evento creato con successo. Repliche generate: " . $numeroRepliche . ".";
        } catch (Throwable $e) {
            if ($conn instanceof mysqli) {
                try {
                    $conn->rollback();
                } catch (Throwable $rollbackError) {
                }
            }
            $errore = $e->getMessage();
        }
    }

    if (isset($_POST['azione']) && $_POST['azione'] === 'elimina_evento') {
        $id_evento_elimina = (int)($_POST['id_evento'] ?? 0);

        try {
            if ($id_evento_elimina <= 0) {
                throw new Exception("Evento non valido.");
            }

            $stmt = $conn->prepare("SELECT immagine FROM evento WHERE id = ? LIMIT 1");
            if (!$stmt) {
                throw new Exception("Errore nella preparazione lettura evento: " . $conn->error);
            }

            $stmt->bind_param("i", $id_evento_elimina);
            $stmt->execute();
            $result = $stmt->get_result();
            $eventoDaEliminare = $result ? $result->fetch_assoc() : null;
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM evento WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Errore nella preparazione eliminazione evento: " . $conn->error);
            }

            $stmt->bind_param("i", $id_evento_elimina);
            $stmt->execute();
            $stmt->close();

            if ($eventoDaEliminare && !empty($eventoDaEliminare['immagine'])) {
                $percorsoImmagine = __DIR__ . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $eventoDaEliminare['immagine']);
                if (is_file($percorsoImmagine)) {
                    @unlink($percorsoImmagine);
                }
            }

            $messaggio = "Evento eliminato con successo.";
        } catch (Throwable $e) {
            $errore = $e->getMessage();
        }
    }
}

$categorie = getCategorie($conn);
$luoghi = getLuoghi($conn);
$settoriDisponibili = getSettori($conn);

$eventi = [];
$sqlEventi = "
    SELECT 
        e.id,
        e.titolo,
        e.descrizione,
        COALESCE(MIN(r.data_ora_inizio), e.data_evento) AS data_evento,
        e.immagine,
        e.stato,
        c.nome AS categoria,
        l.nome AS luogo,
        l.citta,
        COUNT(DISTINCT r.id) AS numero_repliche
    FROM evento e
    JOIN categoria c ON e.id_categoria = c.id
    JOIN luogo l ON e.id_luogo = l.id
    LEFT JOIN replica_evento r ON r.id_evento = e.id
    GROUP BY 
        e.id,
        e.titolo,
        e.descrizione,
        e.data_evento,
        e.immagine,
        e.stato,
        c.nome,
        l.nome,
        l.citta
    ORDER BY data_evento DESC, e.id DESC
";
$resultEventi = $conn->query($sqlEventi);

if ($resultEventi) {
    while ($row = $resultEventi->fetch_assoc()) {
        $eventi[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - EasyTicket</title>
    <link rel="stylesheet" href="css/style1.css?v=50">
    <link rel="icon" type="image/png" href="img/icn_sito_sf.png">
    <style>
        .range-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
        }
        .settori-builder {
            display: grid;
            gap: 16px;
            margin-top: 20px;
        }
        .settore-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 12px;
            align-items: end;
            padding: 14px;
            border: 1px solid #d9e0e8;
            border-radius: 14px;
            background: #f8fbff;
        }
        .mini-label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #17324d;
            font-size: 14px;
        }
        .row-remove-btn {
            background: #d84b38;
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 12px 14px;
            cursor: pointer;
            font-weight: 700;
        }
        .row-remove-btn:hover {
            opacity: 0.92;
        }
        .secondary-btn {
            background: #eef4fa;
            color: #17324d;
            border: 1px solid #d9e0e8;
            border-radius: 10px;
            padding: 12px 16px;
            cursor: pointer;
            font-weight: 700;
        }
        .dashboard-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .table-wrap {
            overflow-x: auto;
        }
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
        }
        .admin-table th,
        .admin-table td {
            padding: 14px 12px;
            border-bottom: 1px solid #e7edf3;
            text-align: left;
            vertical-align: middle;
        }
        .admin-table th {
            background: #f4f8fc;
            color: #17324d;
            font-size: 14px;
        }
        .admin-table td {
            color: #28435c;
            font-size: 15px;
        }
        .thumb-evento {
            width: 84px;
            height: 56px;
            object-fit: cover;
            border-radius: 10px;
            background: #eef2f6;
        }
        .msg-ok {
            border-color: #cfe8d3 !important;
            color: #277243 !important;
            background: #f3fbf4;
        }
        .msg-ko {
            border-color: #f1d1ca !important;
            color: #c13d2a !important;
            background: #fff7f5;
        }
        @media (max-width: 768px) {
            .settore-row {
                grid-template-columns: 1fr;
            }
        }
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
            <h2>Area Amministrazione</h2>
            <p>Gestione eventi, repliche e settori prenotabili.</p>
        </div>

        <?php if ($messaggio !== ""): ?>
            <div class="admin-card msg-ok" style="margin-top: 20px;">
                <?php echo esc($messaggio); ?>
            </div>
        <?php endif; ?>

        <?php if ($errore !== ""): ?>
            <div class="admin-card msg-ko" style="margin-top: 20px;">
                <?php echo esc($errore); ?>
            </div>
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
                    <label for="titolo">Titolo Evento</label>
                    <input type="text" id="titolo" name="titolo" required>
                </div>

                <div class="admin-form-group">
                    <label for="id_categoria">Categoria</label>
                    <select id="id_categoria" name="id_categoria" required>
                        <option value="">Seleziona...</option>
                        <?php foreach ($categorie as $categoria): ?>
                            <option value="<?php echo (int)$categoria['id']; ?>">
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
                            <option value="<?php echo (int)$luogo['id']; ?>">
                                <?php echo esc($luogo['nome'] . ' - ' . $luogo['citta']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="admin-form-group">
                    <label for="immagine">Immagine Copertina</label>
                    <input type="file" id="immagine" name="immagine" accept=".jpg,.jpeg,.png,.webp">
                </div>
            </div>

            <div class="admin-form-group">
                <label for="descrizione">Descrizione</label>
                <textarea id="descrizione" name="descrizione" rows="5" placeholder="Descrizione dell'evento..."></textarea>
            </div>

            <div class="range-grid">
                <div class="admin-form-group">
                    <label for="data_inizio">Data inizio</label>
                    <input type="date" id="data_inizio" name="data_inizio" required>
                </div>

                <div class="admin-form-group">
                    <label for="data_fine">Data fine</label>
                    <input type="date" id="data_fine" name="data_fine" required>
                </div>

                <div class="admin-form-group">
                    <label for="orario_spettacolo">Orario spettacolo</label>
                    <input type="time" id="orario_spettacolo" name="orario_spettacolo" required>
                </div>
            </div>

            <div class="section-heading" style="margin-top: 28px;">
                <h2>Settori</h2>
                <p>Questi settori verranno associati a tutte le repliche create.</p>
            </div>

            <div id="settori-builder" class="settori-builder">
                <div class="settore-row">
                    <div>
                        <label class="mini-label">Settore</label>
                        <select name="settori[0][id_settore]" required>
                            <option value="">Seleziona settore</option>
                            <?php foreach ($settoriDisponibili as $settore): ?>
                                <option value="<?php echo (int)$settore['id']; ?>">
                                    <?php echo esc($settore['nome']); ?>
                                </option>
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
                        <button type="button" class="row-remove-btn" onclick="removeSettoreRow(this)">Rimuovi</button>
                    </div>
                </div>
            </div>

            <div style="margin-top: 14px;" class="dashboard-actions">
                <button type="button" class="secondary-btn" onclick="addSettoreRow()">Aggiungi settore</button>
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
                        <th>Immagine</th>
                        <th>Titolo</th>
                        <th>Categoria</th>
                        <th>Luogo</th>
                        <th>Prima data</th>
                        <th>Repliche</th>
                        <th>Stato</th>
                        <th>Azioni</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($eventi as $evento): ?>
                        <tr>
                            <td>
                                <?php if (!empty($evento['immagine'])): ?>
                                    <img class="thumb-evento" src="<?php echo esc($evento['immagine']); ?>" alt="<?php echo esc($evento['titolo']); ?>">
                                <?php else: ?>
                                    <img class="thumb-evento" src="img/evento-default.png" alt="Evento">
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo esc($evento['titolo']); ?></strong><br>
                                <small><?php echo esc(mb_strimwidth((string)($evento['descrizione'] ?? ''), 0, 80, '...')); ?></small>
                            </td>
                            <td><?php echo esc($evento['categoria']); ?></td>
                            <td><?php echo esc($evento['luogo'] . ' - ' . $evento['citta']); ?></td>
                            <td>
                                <?php echo !empty($evento['data_evento']) ? esc(date('d/m/Y H:i', strtotime($evento['data_evento']))) : 'N/D'; ?>
                            </td>
                            <td><?php echo (int)$evento['numero_repliche']; ?></td>
                            <td><?php echo esc($evento['stato']); ?></td>
                            <td>
                                <div class="dashboard-actions">
                                    <a href="evento.php?id=<?php echo (int)$evento['id']; ?>" class="hero-cta">Apri</a>
                                    <a href="modifica_evento.php?id=<?php echo (int)$evento['id']; ?>" class="secondary-btn" style="text-decoration:none; display:inline-flex; align-items:center;">Modifica</a>

                                    <form method="post" onsubmit="return confirm('Vuoi eliminare davvero questo evento?');" style="display:inline;">
                                        <input type="hidden" name="azione" value="elimina_evento">
                                        <input type="hidden" name="id_evento" value="<?php echo (int)$evento['id']; ?>">
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

<footer class="site-footer">
    <p>&copy; 2026 EasyTicket</p>
</footer>

<script>
let settoreIndex = 1;

function addSettoreRow() {
    const builder = document.getElementById('settori-builder');
    const row = document.createElement('div');
    row.className = 'settore-row';

    row.innerHTML = `
        <div>
            <label class="mini-label">Settore</label>
            <select name="settori[${settoreIndex}][id_settore]" required>
                <option value="">Seleziona settore</option>
                <?php foreach ($settoriDisponibili as $settore): ?>
                    <option value="<?php echo (int)$settore['id']; ?>"><?php echo esc($settore['nome']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="mini-label">Prezzo (€)</label>
            <input type="number" step="0.01" min="0" name="settori[${settoreIndex}][prezzo]" required>
        </div>

        <div>
            <label class="mini-label">Posti totali</label>
            <input type="number" min="1" name="settori[${settoreIndex}][posti_totali]" required>
        </div>

        <div>
            <button type="button" class="row-remove-btn" onclick="removeSettoreRow(this)">Rimuovi</button>
        </div>
    `;

    builder.appendChild(row);
    settoreIndex++;
}

function removeSettoreRow(button) {
    const rows = document.querySelectorAll('.settore-row');
    if (rows.length <= 1) {
        alert('Deve esserci almeno un settore.');
        return;
    }
    button.closest('.settore-row').remove();
}
</script>
</body>
</html>