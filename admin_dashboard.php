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

$messaggio = "";
$errore = "";

$evento_modifica_id = isset($_GET['modifica_evento']) ? (int)$_GET['modifica_evento'] : 0;
$evento_repliche_id = isset($_GET['repliche_evento']) ? (int)$_GET['repliche_evento'] : 0;

/* =========================
   AGGIUNTA EVENTO
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST['azione'] ?? '') === 'aggiungi_evento') {
    $titolo = trim($_POST['titolo'] ?? '');
    $descrizione = trim($_POST['descrizione'] ?? '');
    $id_categoria = (int)($_POST['id_categoria'] ?? 0);
    $id_luogo = (int)($_POST['id_luogo'] ?? 0);
    $data_evento = trim($_POST['data_evento'] ?? '');

    if ($titolo === '' || $id_categoria <= 0 || $id_luogo <= 0 || $data_evento === '') {
        $errore = "Compila tutti i campi obbligatori per aggiungere l'evento.";
    } else {
        $data_evento = str_replace('T', ' ', $data_evento) . ':00';
        $immagine = null;

        if (!empty($_FILES['immagine']['name'])) {
            $uploadDirFs = __DIR__ . '/img/eventi/';
            $uploadDirDb = 'img/eventi/';

            if (!is_dir($uploadDirFs)) {
                mkdir($uploadDirFs, 0775, true);
            }

            $nomeOriginale = basename($_FILES['immagine']['name']);
            $nomePulito = preg_replace('/[^A-Za-z0-9.\-_]/', '_', $nomeOriginale);
            $nomeFile = time() . "_" . $nomePulito;

            $targetPathFs = $uploadDirFs . $nomeFile;
            $targetPathDb = $uploadDirDb . $nomeFile;

            if (move_uploaded_file($_FILES['immagine']['tmp_name'], $targetPathFs)) {
                $immagine = $targetPathDb;
            } else {
                $errore = "Errore durante il caricamento dell'immagine.";
            }
        }

        if ($errore === "") {
            $sql = "INSERT INTO evento (titolo, descrizione, data_evento, id_categoria, id_luogo, immagine, stato)
                    VALUES (?, ?, ?, ?, ?, ?, 'programmato')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssiis", $titolo, $descrizione, $data_evento, $id_categoria, $id_luogo, $immagine);

            if ($stmt->execute()) {
                $messaggio = "Evento aggiunto con successo.";
            } else {
                $errore = "Errore durante l'inserimento dell'evento: " . $stmt->error;
            }

            $stmt->close();
        }
    }
}

/* =========================
   MODIFICA EVENTO
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST['azione'] ?? '') === 'modifica_evento') {
    $id_evento = (int)($_POST['id_evento'] ?? 0);
    $titolo = trim($_POST['titolo'] ?? '');
    $descrizione = trim($_POST['descrizione'] ?? '');
    $id_categoria = (int)($_POST['id_categoria'] ?? 0);
    $id_luogo = (int)($_POST['id_luogo'] ?? 0);
    $data_evento = trim($_POST['data_evento'] ?? '');

    if ($id_evento <= 0 || $titolo === '' || $id_categoria <= 0 || $id_luogo <= 0 || $data_evento === '') {
        $errore = "Compila correttamente tutti i campi per modificare l'evento.";
    } else {
        $data_evento = str_replace('T', ' ', $data_evento) . ':00';

        $stmtOld = $conn->prepare("SELECT immagine FROM evento WHERE id = ? LIMIT 1");
        $stmtOld->bind_param("i", $id_evento);
        $stmtOld->execute();
        $oldResult = $stmtOld->get_result();
        $oldEvento = $oldResult->fetch_assoc();
        $stmtOld->close();

        $immagine = $oldEvento['immagine'] ?? null;

        if (!empty($_FILES['immagine']['name'])) {
            $uploadDirFs = __DIR__ . '/img/eventi/';
            $uploadDirDb = 'img/eventi/';

            if (!is_dir($uploadDirFs)) {
                mkdir($uploadDirFs, 0775, true);
            }

            $nomeOriginale = basename($_FILES['immagine']['name']);
            $nomePulito = preg_replace('/[^A-Za-z0-9.\-_]/', '_', $nomeOriginale);
            $nomeFile = time() . "_" . $nomePulito;

            $targetPathFs = $uploadDirFs . $nomeFile;
            $targetPathDb = $uploadDirDb . $nomeFile;

            if (move_uploaded_file($_FILES['immagine']['tmp_name'], $targetPathFs)) {
                $immagine = $targetPathDb;
            } else {
                $errore = "Errore durante il caricamento della nuova immagine.";
            }
        }

        if ($errore === "") {
            $sql = "UPDATE evento
                    SET titolo = ?, descrizione = ?, data_evento = ?, id_categoria = ?, id_luogo = ?, immagine = ?
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssiisi", $titolo, $descrizione, $data_evento, $id_categoria, $id_luogo, $immagine, $id_evento);

            if ($stmt->execute()) {
                $messaggio = "Evento modificato con successo.";
                $evento_modifica_id = $id_evento;
            } else {
                $errore = "Errore durante la modifica dell'evento: " . $stmt->error;
            }

            $stmt->close();
        }
    }
}

/* =========================
   ELIMINA EVENTO
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST['azione'] ?? '') === 'elimina_evento') {
    $id_evento = (int)($_POST['id_evento'] ?? 0);

    if ($id_evento > 0) {
        $stmt = $conn->prepare("DELETE FROM evento WHERE id = ?");
        $stmt->bind_param("i", $id_evento);

        if ($stmt->execute()) {
            $messaggio = "Evento eliminato con successo.";
            if ($evento_modifica_id === $id_evento) $evento_modifica_id = 0;
            if ($evento_repliche_id === $id_evento) $evento_repliche_id = 0;
        } else {
            $errore = "Errore durante l'eliminazione dell'evento.";
        }

        $stmt->close();
    }
}

/* =========================
   AGGIUNGI REPLICA
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST['azione'] ?? '') === 'aggiungi_replica') {
    $id_evento = (int)($_POST['id_evento_replica'] ?? 0);
    $data_ora_inizio = trim($_POST['data_ora_inizio'] ?? '');
    $data_ora_fine = trim($_POST['data_ora_fine'] ?? '');

    if ($id_evento <= 0 || $data_ora_inizio === '') {
        $errore = "Seleziona un evento e inserisci la data/ora di inizio della replica.";
    } else {
        $data_ora_inizio_sql = str_replace('T', ' ', $data_ora_inizio) . ':00';
        $data_ora_fine_sql = null;

        if ($data_ora_fine !== '') {
            $data_ora_fine_sql = str_replace('T', ' ', $data_ora_fine) . ':00';
        }

        $stmt = $conn->prepare("INSERT INTO replica_evento (id_evento, data_ora_inizio, data_ora_fine, stato)
                                VALUES (?, ?, ?, 'programmata')");
        $stmt->bind_param("iss", $id_evento, $data_ora_inizio_sql, $data_ora_fine_sql);

        if ($stmt->execute()) {
            $messaggio = "Replica aggiunta con successo.";
            $evento_repliche_id = $id_evento;
        } else {
            $errore = "Errore durante l'aggiunta della replica: " . $stmt->error;
        }

        $stmt->close();
    }
}

/* =========================
   ELIMINA REPLICA
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST['azione'] ?? '') === 'elimina_replica') {
    $id_replica = (int)($_POST['id_replica'] ?? 0);
    $id_evento = (int)($_POST['id_evento_replica'] ?? 0);

    if ($id_replica > 0) {
        $stmt = $conn->prepare("DELETE FROM replica_evento WHERE id = ?");
        $stmt->bind_param("i", $id_replica);

        if ($stmt->execute()) {
            $messaggio = "Replica eliminata con successo.";
            $evento_repliche_id = $id_evento;
        } else {
            $errore = "Errore durante l'eliminazione della replica.";
        }

        $stmt->close();
    }
}

/* =========================
   DATI BASE
========================= */
$categorie = [];
$resCat = $conn->query("SELECT id, nome FROM categoria ORDER BY nome ASC");
while ($resCat && $row = $resCat->fetch_assoc()) {
    $categorie[] = $row;
}

$luoghi = [];
$resLuogo = $conn->query("SELECT id, nome, citta FROM luogo ORDER BY citta, nome ASC");
while ($resLuogo && $row = $resLuogo->fetch_assoc()) {
    $luoghi[] = $row;
}

$eventi = [];
$sqlEventi = "SELECT e.id, e.titolo, e.descrizione, e.data_evento, e.immagine,
                     e.id_categoria, e.id_luogo,
                     c.nome AS categoria, l.nome AS luogo, l.citta
              FROM evento e
              JOIN categoria c ON e.id_categoria = c.id
              JOIN luogo l ON e.id_luogo = l.id
              ORDER BY e.data_evento ASC";
$resEventi = $conn->query($sqlEventi);
while ($resEventi && $row = $resEventi->fetch_assoc()) {
    $eventi[] = $row;
}

$eventoSelezionato = null;
foreach ($eventi as $ev) {
    if ((int)$ev['id'] === $evento_modifica_id) {
        $eventoSelezionato = $ev;
        break;
    }
}

$repliche = [];
if ($evento_repliche_id > 0) {
    $stmt = $conn->prepare("SELECT id, data_ora_inizio, data_ora_fine, stato
                            FROM replica_evento
                            WHERE id_evento = ?
                            ORDER BY data_ora_inizio ASC");
    $stmt->bind_param("i", $evento_repliche_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $repliche[] = $row;
    }
    $stmt->close();
}

$username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'admin';
$conn->close();

$dataEventoInput = '';
if ($eventoSelezionato && !empty($eventoSelezionato['data_evento'])) {
    $dataEventoInput = date('Y-m-d\TH:i', strtotime($eventoSelezionato['data_evento']));
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - EasyTicket</title>
    <link rel="icon" type="image/x-icon" href="img/icn_sito_sf.png">
    <link rel="stylesheet" href="css/style1.css?v=90">
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
            <h2>Amministrazione Eventi</h2>
            <p>Gestisci eventi e repliche in modo separato e mirato.</p>
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
    </section>

    <section class="section-block">
        <div class="section-heading">
            <h2>Aggiungi evento</h2>
            <p>Crea un nuovo evento principale.</p>
        </div>

        <div class="admin-card">
            <form action="admin_dashboard.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="azione" value="aggiungi_evento">

                <label for="titolo">Titolo evento</label>
                <input type="text" id="titolo" name="titolo" required>

                <label for="descrizione">Descrizione</label>
                <textarea id="descrizione" name="descrizione"></textarea>

                <label for="id_categoria">Categoria</label>
                <select id="id_categoria" name="id_categoria" required>
                    <option value="">Seleziona categoria</option>
                    <?php foreach ($categorie as $cat): ?>
                        <option value="<?php echo (int)$cat['id']; ?>">
                            <?php echo htmlspecialchars($cat['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="id_luogo">Luogo</label>
                <select id="id_luogo" name="id_luogo" required>
                    <option value="">Seleziona luogo</option>
                    <?php foreach ($luoghi as $luogo): ?>
                        <option value="<?php echo (int)$luogo['id']; ?>">
                            <?php echo htmlspecialchars($luogo['citta'] . ' - ' . $luogo['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="data_evento">Data principale evento</label>
                <input type="datetime-local" id="data_evento" name="data_evento" required>

                <label for="immagine">Immagine evento</label>
                <input type="file" id="immagine" name="immagine" accept="image/*">

                <button type="submit" class="admin-submit">Aggiungi evento</button>
            </form>
        </div>
    </section>

    <section class="section-block">
        <div class="section-heading">
            <h2>Modifica evento</h2>
            <p>Seleziona un evento e modifica solo i campi che ti servono.</p>
        </div>

        <div class="admin-card">
            <form method="get" action="admin_dashboard.php">
                <label for="modifica_evento">Scegli evento</label>
                <select id="modifica_evento" name="modifica_evento" required>
                    <option value="">Seleziona evento</option>
                    <?php foreach ($eventi as $ev): ?>
                        <option value="<?php echo (int)$ev['id']; ?>" <?php echo ($evento_modifica_id === (int)$ev['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($ev['titolo'] . ' - ' . $ev['data_evento']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="admin-submit">Carica evento</button>
            </form>
        </div>

        <?php if ($eventoSelezionato): ?>
            <div class="admin-card" style="margin-top:20px;">
                <form action="admin_dashboard.php?modifica_evento=<?php echo (int)$eventoSelezionato['id']; ?>" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="azione" value="modifica_evento">
                    <input type="hidden" name="id_evento" value="<?php echo (int)$eventoSelezionato['id']; ?>">

                    <label for="titolo_mod">Titolo evento</label>
                    <input type="text" id="titolo_mod" name="titolo" value="<?php echo htmlspecialchars($eventoSelezionato['titolo']); ?>" required>

                    <label for="descrizione_mod">Descrizione</label>
                    <textarea id="descrizione_mod" name="descrizione"><?php echo htmlspecialchars($eventoSelezionato['descrizione'] ?? ''); ?></textarea>

                    <label for="categoria_mod">Categoria</label>
                    <select id="categoria_mod" name="id_categoria" required>
                        <?php foreach ($categorie as $cat): ?>
                            <option value="<?php echo (int)$cat['id']; ?>" <?php echo ((int)$eventoSelezionato['id_categoria'] === (int)$cat['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="luogo_mod">Luogo</label>
                    <select id="luogo_mod" name="id_luogo" required>
                        <?php foreach ($luoghi as $luogo): ?>
                            <option value="<?php echo (int)$luogo['id']; ?>" <?php echo ((int)$eventoSelezionato['id_luogo'] === (int)$luogo['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($luogo['citta'] . ' - ' . $luogo['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="data_evento_mod">Data principale evento</label>
                    <input type="datetime-local" id="data_evento_mod" name="data_evento" value="<?php echo $dataEventoInput; ?>" required>

                    <label for="immagine_mod">Nuova immagine evento</label>
                    <input type="file" id="immagine_mod" name="immagine" accept="image/*">

                    <?php if (!empty($eventoSelezionato['immagine'])): ?>
                        <p>Immagine attuale:</p>
                        <img src="<?php echo htmlspecialchars($eventoSelezionato['immagine']); ?>" alt="<?php echo htmlspecialchars($eventoSelezionato['titolo']); ?>" width="220">
                    <?php endif; ?>

                    <button type="submit" class="admin-submit" style="margin-top:15px;">Salva modifiche</button>
                </form>

                <form action="admin_dashboard.php" method="post" onsubmit="return confirm('Vuoi davvero eliminare questo evento?');" style="margin-top:20px;">
                    <input type="hidden" name="azione" value="elimina_evento">
                    <input type="hidden" name="id_evento" value="<?php echo (int)$eventoSelezionato['id']; ?>">
                    <button type="submit" class="secondary-pill">Elimina evento</button>
                </form>
            </div>
        <?php endif; ?>
    </section>

    <section class="section-block">
        <div class="section-heading">
            <h2>Gestione repliche</h2>
            <p>Seleziona un evento e gestisci qui tutte le sue repliche.</p>
        </div>

        <div class="admin-card">
            <form method="get" action="admin_dashboard.php">
                <?php if ($evento_modifica_id > 0): ?>
                    <input type="hidden" name="modifica_evento" value="<?php echo $evento_modifica_id; ?>">
                <?php endif; ?>

                <label for="repliche_evento">Evento per repliche</label>
                <select id="repliche_evento" name="repliche_evento" required>
                    <option value="">Seleziona evento</option>
                    <?php foreach ($eventi as $ev): ?>
                        <option value="<?php echo (int)$ev['id']; ?>" <?php echo ($evento_repliche_id === (int)$ev['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($ev['titolo'] . ' - ' . $ev['data_evento']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="admin-submit">Carica repliche</button>
            </form>
        </div>

        <?php if ($evento_repliche_id > 0): ?>
            <div class="admin-card" style="margin-top:20px;">
                <h3>Aggiungi replica</h3>
                <form action="admin_dashboard.php?repliche_evento=<?php echo $evento_repliche_id; ?><?php echo $evento_modifica_id > 0 ? '&modifica_evento=' . $evento_modifica_id : ''; ?>" method="post">
                    <input type="hidden" name="azione" value="aggiungi_replica">
                    <input type="hidden" name="id_evento_replica" value="<?php echo $evento_repliche_id; ?>">

                    <label for="data_ora_inizio">Data e ora inizio</label>
                    <input type="datetime-local" id="data_ora_inizio" name="data_ora_inizio" required>

                    <label for="data_ora_fine">Data e ora fine (facoltativa)</label>
                    <input type="datetime-local" id="data_ora_fine" name="data_ora_fine">

                    <button type="submit" class="admin-submit">Aggiungi replica</button>
                </form>
            </div>

            <div class="admin-card" style="margin-top:20px;">
                <h3>Repliche esistenti</h3>

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

                                <form action="admin_dashboard.php?repliche_evento=<?php echo $evento_repliche_id; ?><?php echo $evento_modifica_id > 0 ? '&modifica_evento=' . $evento_modifica_id : ''; ?>" method="post" onsubmit="return confirm('Vuoi davvero eliminare questa replica?');">
                                    <input type="hidden" name="azione" value="elimina_replica">
                                    <input type="hidden" name="id_replica" value="<?php echo (int)$replica['id']; ?>">
                                    <input type="hidden" name="id_evento_replica" value="<?php echo $evento_repliche_id; ?>">
                                    <button type="submit" class="secondary-pill">Elimina replica</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>Nessuna replica presente per questo evento.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<footer class="site-footer">
    <p>&copy; 2026 EasyTicket</p>
</footer>
</body>
</html>