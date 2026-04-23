<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Solo admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || (int)($_SESSION['ruolo'] ?? 0) !== 1) {
    header("Location: login.php");
    exit();
}

$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "EasyTicket";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

$id_evento = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_evento <= 0) {
    die("Evento non valido.");
}

$messaggio = "";
$errore = "";

/* =========================
   UPDATE EVENTO
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['azione']) && $_POST['azione'] === 'modifica_evento') {
    $titolo = trim($_POST['titolo'] ?? '');
    $descrizione = trim($_POST['descrizione'] ?? '');
    $id_categoria = (int)($_POST['id_categoria'] ?? 0);
    $id_luogo = (int)($_POST['id_luogo'] ?? 0);
    $data_evento = trim($_POST['data_evento'] ?? '');

    if ($titolo === '' || $id_categoria <= 0 || $id_luogo <= 0 || $data_evento === '') {
        $errore = "Compila tutti i campi obbligatori dell'evento.";
    } else {
        $data_evento = str_replace('T', ' ', $data_evento) . ':00';

        $stmtOld = $conn->prepare("SELECT immagine FROM evento WHERE id = ? LIMIT 1");
        $stmtOld->bind_param("i", $id_evento);
        $stmtOld->execute();
        $oldResult = $stmtOld->get_result();
        $oldEvento = $oldResult->fetch_assoc();
        $stmtOld->close();

        $immagine = $oldEvento['immagine'] ?? null;

        if (isset($_FILES['immagine']) && !empty($_FILES['immagine']['name'])) {
            $uploadDirFs = __DIR__ . '/img/eventi/';
            $uploadDirDb = 'img/eventi/';

            if (!is_dir($uploadDirFs)) {
                if (!mkdir($uploadDirFs, 0775, true)) {
                    $errore = "Impossibile creare la cartella img/eventi.";
                }
            }

            if ($errore === "") {
                $nomeOriginale = basename($_FILES['immagine']['name']);
                $nomePulito = preg_replace('/[^A-Za-z0-9.\-_]/', '_', $nomeOriginale);
                $nomeFile = time() . "_" . $nomePulito;

                $targetPathFs = $uploadDirFs . $nomeFile;
                $targetPathDb = $uploadDirDb . $nomeFile;

                if (@move_uploaded_file($_FILES['immagine']['tmp_name'], $targetPathFs)) {
                    $immagine = $targetPathDb;
                } else {
                    $errore = "Errore durante il caricamento della nuova immagine.";
                }
            }
        }

        if ($errore === "") {
            $sql = "UPDATE evento
                    SET titolo = ?, descrizione = ?, data_evento = ?, id_categoria = ?, id_luogo = ?, immagine = ?
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);

            if (!$stmt) {
                $errore = "Errore query update: " . $conn->error;
            } else {
                $stmt->bind_param(
                    "sssiisi",
                    $titolo,
                    $descrizione,
                    $data_evento,
                    $id_categoria,
                    $id_luogo,
                    $immagine,
                    $id_evento
                );

                if ($stmt->execute()) {
                    $messaggio = "Evento aggiornato con successo.";
                } else {
                    $errore = "Errore durante l'aggiornamento dell'evento: " . $stmt->error;
                }

                $stmt->close();
            }
        }
    }
}

/* =========================
   AGGIUNTA REPLICA
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['azione']) && $_POST['azione'] === 'aggiungi_replica') {
    $data_ora_inizio = trim($_POST['data_ora_inizio'] ?? '');
    $data_ora_fine = trim($_POST['data_ora_fine'] ?? '');

    if ($data_ora_inizio === '') {
        $errore = "Inserisci almeno data e ora di inizio per la replica.";
    } else {
        $data_ora_inizio_sql = str_replace('T', ' ', $data_ora_inizio) . ':00';
        $data_ora_fine_sql = null;

        if ($data_ora_fine !== '') {
            $data_ora_fine_sql = str_replace('T', ' ', $data_ora_fine) . ':00';
        }

        $sql = "INSERT INTO replica_evento (id_evento, data_ora_inizio, data_ora_fine, stato)
                VALUES (?, ?, ?, 'programmata')";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            $errore = "Errore preparazione inserimento replica: " . $conn->error;
        } else {
            $stmt->bind_param("iss", $id_evento, $data_ora_inizio_sql, $data_ora_fine_sql);

            if ($stmt->execute()) {
                $messaggio = "Replica aggiunta con successo.";
            } else {
                $errore = "Errore durante l'aggiunta della replica: " . $stmt->error;
            }

            $stmt->close();
        }
    }
}

/* =========================
   ELIMINA REPLICA
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['azione']) && $_POST['azione'] === 'elimina_replica') {
    $id_replica = (int)($_POST['id_replica'] ?? 0);

    if ($id_replica > 0) {
        $stmt = $conn->prepare("DELETE FROM replica_evento WHERE id = ? AND id_evento = ?");
        $stmt->bind_param("ii", $id_replica, $id_evento);

        if ($stmt->execute()) {
            $messaggio = "Replica eliminata con successo.";
        } else {
            $errore = "Errore durante l'eliminazione della replica.";
        }

        $stmt->close();
    }
}

/* =========================
   DATI SELECT
========================= */
$categorie = [];
$resCat = $conn->query("SELECT id, nome FROM categoria ORDER BY nome ASC");
if ($resCat && $resCat->num_rows > 0) {
    while ($row = $resCat->fetch_assoc()) {
        $categorie[] = $row;
    }
}

$luoghi = [];
$resLuogo = $conn->query("SELECT id, nome, citta FROM luogo ORDER BY citta, nome ASC");
if ($resLuogo && $resLuogo->num_rows > 0) {
    while ($row = $resLuogo->fetch_assoc()) {
        $luoghi[] = $row;
    }
}

/* =========================
   EVENTO
========================= */
$stmt = $conn->prepare("SELECT * FROM evento WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id_evento);
$stmt->execute();
$result = $stmt->get_result();
$evento = $result->fetch_assoc();
$stmt->close();

if (!$evento) {
    $conn->close();
    die("Evento non trovato.");
}

/* =========================
   REPLICHE EVENTO
========================= */
$repliche = [];
$stmt = $conn->prepare("SELECT id, data_ora_inizio, data_ora_fine, stato
                        FROM replica_evento
                        WHERE id_evento = ?
                        ORDER BY data_ora_inizio ASC");
$stmt->bind_param("i", $id_evento);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $repliche[] = $row;
}
$stmt->close();

$username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'admin';
$conn->close();

$dataEventoInput = '';
if (!empty($evento['data_evento'])) {
    $dataEventoInput = date('Y-m-d\TH:i', strtotime($evento['data_evento']));
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifica Evento - EasyTicket</title>
    <link rel="icon" type="image/x-icon" href="img/icn_sito_sf.png">
    <link rel="stylesheet" href="css/style1.css?v=70">
</head>
<body>
    <header class="site-header">
        <div class="header-inner">
            <a href="home.php" class="brand">
                <img src="img/logo_sito.png" alt="Logo EasyTicket">
            </a>

            <nav class="user-nav">
                <a href="admin_dashboard.php" class="user-pill primary-pill"><?php echo $username; ?></a>
                <a href="logout.php" class="user-pill secondary-pill">Logout</a>
            </nav>
        </div>
    </header>

    <main class="page-shell">
        <section class="section-block">
            <div class="section-heading">
                <h2>Modifica evento</h2>
                <p>Gestisci dati principali e repliche dell'evento selezionato.</p>
            </div>

            <?php if ($messaggio !== ""): ?>
                <div class="admin-card" style="margin-bottom:20px;">
                    <?php echo htmlspecialchars($messaggio); ?>
                </div>
            <?php endif; ?>

            <?php if ($errore !== ""): ?>
                <div class="admin-card" style="margin-bottom:20px; border-color:#f1d1ca; color:#c13d2a;">
                    <?php echo htmlspecialchars($errore); ?>
                </div>
            <?php endif; ?>

            <div class="admin-grid">
                <div class="admin-card">
                    <h3>Dati evento</h3>

                    <form action="modifica_evento.php?id=<?php echo $id_evento; ?>" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="azione" value="modifica_evento">

                        <label for="titolo">Titolo evento</label>
                        <input type="text" id="titolo" name="titolo" value="<?php echo htmlspecialchars($evento['titolo']); ?>" required>

                        <label for="descrizione">Descrizione</label>
                        <textarea id="descrizione" name="descrizione"><?php echo htmlspecialchars($evento['descrizione'] ?? ''); ?></textarea>

                        <label for="id_categoria">Categoria</label>
                        <select id="id_categoria" name="id_categoria" required>
                            <?php foreach ($categorie as $cat): ?>
                                <option value="<?php echo (int)$cat['id']; ?>" <?php echo ((int)$evento['id_categoria'] === (int)$cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label for="id_luogo">Luogo</label>
                        <select id="id_luogo" name="id_luogo" required>
                            <?php foreach ($luoghi as $luogo): ?>
                                <option value="<?php echo (int)$luogo['id']; ?>" <?php echo ((int)$evento['id_luogo'] === (int)$luogo['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($luogo['citta'] . " - " . $luogo['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label for="data_evento">Data principale evento</label>
                        <input type="datetime-local" id="data_evento" name="data_evento" value="<?php echo $dataEventoInput; ?>" required>

                        <label for="immagine">Nuova immagine evento</label>
                        <input type="file" id="immagine" name="immagine" accept="image/*">

                        <button type="submit" class="primary-btn">Salva modifiche evento</button>
                    </form>
                </div>

                <div class="admin-card">
                    <h3>Anteprima</h3>

                    <?php if (!empty($evento['immagine'])): ?>
                        <img src="<?php echo htmlspecialchars($evento['immagine']); ?>" alt="<?php echo htmlspecialchars($evento['titolo']); ?>" width="220">
                    <?php else: ?>
                        <img src="img/evento-default.png" alt="Evento" width="220">
                    <?php endif; ?>

                    <p><strong><?php echo htmlspecialchars($evento['titolo']); ?></strong></p>
                    <p><?php echo htmlspecialchars($evento['descrizione'] ?? 'Nessuna descrizione'); ?></p>
                    <p>Data principale: <?php echo htmlspecialchars($evento['data_evento']); ?></p>
                </div>
            </div>
        </section>

        <section class="section-block">
            <div class="section-heading">
                <h2>Aggiungi replica</h2>
                <p>Qui puoi inserire più date o più orari per lo stesso evento.</p>
            </div>

            <div class="admin-card">
                <form action="modifica_evento.php?id=<?php echo $id_evento; ?>" method="post">
                    <input type="hidden" name="azione" value="aggiungi_replica">

                    <label for="data_ora_inizio">Data e ora inizio</label>
                    <input type="datetime-local" id="data_ora_inizio" name="data_ora_inizio" required>

                    <label for="data_ora_fine">Data e ora fine (facoltativa)</label>
                    <input type="datetime-local" id="data_ora_fine" name="data_ora_fine">

                    <button type="submit" class="primary-btn">Aggiungi replica</button>
                </form>
            </div>
        </section>

        <section class="section-block">
            <div class="section-heading">
                <h2>Repliche esistenti</h2>
                <p>Puoi vedere e cancellare le repliche già associate a questo evento.</p>
            </div>

            <?php if (!empty($repliche)): ?>
                <div class="admin-list">
                    <?php foreach ($repliche as $replica): ?>
                        <div class="admin-list-item">
                            <div>
                                <strong><?php echo htmlspecialchars($replica['data_ora_inizio']); ?></strong>
                                <?php if (!empty($replica['data_ora_fine'])): ?>
                                    <span> - <?php echo htmlspecialchars($replica['data_ora_fine']); ?></span>
                                <?php endif; ?>
                            </div>

                            <form action="modifica_evento.php?id=<?php echo $id_evento; ?>" method="post" onsubmit="return confirm('Vuoi davvero eliminare questa replica?');">
                                <input type="hidden" name="azione" value="elimina_replica">
                                <input type="hidden" name="id_replica" value="<?php echo (int)$replica['id']; ?>">
                                <button type="submit" class="secondary-pill">Elimina</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h3>Nessuna replica presente</h3>
                    <p>Aggiungi almeno una replica per permettere la scelta di data e orario.</p>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <footer class="site-footer">
        <p>&copy; 2026 EasyTicket</p>
    </footer>
</body>
</html>