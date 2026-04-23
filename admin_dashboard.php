<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Controllo sessione admin
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
        $errore = "Compila tutti i campi obbligatori.";
    } else {
        // Formattazione data per MySQL
        $data_evento_sql = str_replace('T', ' ', $data_evento) . ':00';
        $immagine = null;

        // Gestione Caricamento Immagine
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
                $errore = "Errore nel caricamento dell'immagine.";
            }
        }

        if ($errore === "") {
            $sql = "INSERT INTO evento (titolo, descrizione, data_evento, id_categoria, id_luogo, immagine, stato)
                    VALUES (?, ?, ?, ?, ?, ?, 'programmato')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssiis", $titolo, $descrizione, $data_evento_sql, $id_categoria, $id_luogo, $immagine);

            if ($stmt->execute()) {
                $messaggio = "Evento creato con successo.";
            } else {
                $errore = "Errore SQL: " . $stmt->error;
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
            $messaggio = "Evento eliminato definitivamente.";
        } else {
            $errore = "Errore durante l'eliminazione dell'evento.";
        }
        $stmt->close();
    }
}

/* =========================
   RECUPERO DATI
========================= */
// Categorie
$categorie = [];
$resCat = $conn->query("SELECT id, nome FROM categoria ORDER BY nome ASC");
while ($resCat && $row = $resCat->fetch_assoc()) {
    $categorie[] = $row;
}

// Luoghi
$luoghi = [];
$resLuogo = $conn->query("SELECT id, nome, citta FROM luogo ORDER BY citta, nome ASC");
while ($resLuogo && $row = $resLuogo->fetch_assoc()) {
    $luoghi[] = $row;
}

// Lista Eventi
$eventi = [];
$sqlEventi = "SELECT e.id, e.titolo, e.descrizione, e.data_evento, e.immagine,
                     c.nome AS categoria, l.nome AS luogo, l.citta
              FROM evento e
              JOIN categoria c ON e.id_categoria = c.id
              JOIN luogo l ON e.id_luogo = l.id
              ORDER BY e.data_evento DESC";
$resEventi = $conn->query($sqlEventi);
while ($resEventi && $row = $resEventi->fetch_assoc()) {
    $eventi[] = $row;
}

$username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'admin';
$conn->close();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - EasyTicket</title>
    <link rel="icon" type="image/x-icon" href="img/icn_sito_sf.png">
    <link rel="stylesheet" href="css/style1.css?v=100">
    <style>
        .event-list-table { width: 100%; border-collapse: collapse; margin-top: 20px; background: #fff; border-radius: 8px; overflow: hidden; }
        .event-list-table th, .event-list-table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .event-list-table th { background-color: #f8f9fa; font-weight: 600; }
        .img-preview { width: 60px; height: 40px; object-fit: cover; border-radius: 4px; }
        .btn-edit { text-decoration: none; color: #fff; background: #3498db; padding: 6px 12px; border-radius: 20px; font-size: 0.85rem; }
        .btn-delete { background: #e74c3c; border: none; color: white; padding: 6px 12px; border-radius: 20px; cursor: pointer; font-size: 0.85rem; }
        .action-cell { display: flex; gap: 10px; align-items: center; }
    </style>
</head>
<body>

<header class="site-header">
    <div class="header-inner">
        <a href="home.php" class="brand">
            <img src="img/logo_sito.png" alt="Logo EasyTicket">
        </a>
        <nav class="user-nav">
            <span class="user-pill primary-pill"><?php echo $username; ?></span>
            <a href="logout.php" class="user-pill secondary-pill">Logout</a>
        </nav>
    </div>
</header>

<main class="page-shell">
    
    <section class="section-block">
        <div class="section-heading">
            <h2>Area Amministrazione</h2>
            <p>Gestione semplificata del catalogo eventi.</p>
        </div>

        <?php if ($messaggio): ?>
            <div class="admin-card" style="border-left: 5px solid #2ecc71; color: #27ae60;">
                <?php echo htmlspecialchars($messaggio); ?>
            </div>
        <?php endif; ?>

        <?php if ($errore): ?>
            <div class="admin-card" style="border-left: 5px solid #e74c3c; color: #c0392b;">
                <?php echo htmlspecialchars($errore); ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="section-block">
        <div class="section-heading">
            <h2>Aggiungi Nuovo Evento</h2>
        </div>
        <div class="admin-card">
            <form action="admin_dashboard.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="azione" value="aggiungi_evento">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <label for="titolo">Titolo Evento</label>
                        <input type="text" id="titolo" name="titolo" required>

                        <label for="id_categoria">Categoria</label>
                        <select id="id_categoria" name="id_categoria" required>
                            <option value="">Seleziona...</option>
                            <?php foreach ($categorie as $cat): ?>
                                <option value="<?php echo (int)$cat['id']; ?>"><?php echo htmlspecialchars($cat['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>

                        <label for="id_luogo">Luogo</label>
                        <select id="id_luogo" name="id_luogo" required>
                            <option value="">Seleziona...</option>
                            <?php foreach ($luoghi as $l): ?>
                                <option value="<?php echo (int)$l['id']; ?>"><?php echo htmlspecialchars($l['citta'] . " - " . $l['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="data_evento">Data e Ora</label>
                        <input type="datetime-local" id="data_evento" name="data_evento" required>

                        <label for="immagine">Immagine Copertina</label>
                        <input type="file" id="immagine" name="immagine" accept="image/*">

                        <label for="descrizione">Descrizione Breve</label>
                        <textarea id="descrizione" name="descrizione" rows="3"></textarea>
                    </div>
                </div>

                <button type="submit" class="admin-submit" style="margin-top: 10px;">Crea Evento</button>
            </form>
        </div>
    </section>

    <section class="section-block">
        <div class="section-heading">
            <h2>Eventi Esistenti</h2>
            <p>Elenco completo degli eventi caricati a sistema.</p>
        </div>

        <div class="admin-card" style="padding: 0; overflow-x: auto;">
            <table class="event-list-table">
                <thead>
                    <tr>
                        <th>Immagine</th>
                        <th>Evento</th>
                        <th>Data</th>
                        <th>Luogo / Categoria</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($eventi)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 20px;">Nessun evento trovato.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($eventi as $ev): ?>
                        <tr>
                            <td>
                                <?php if ($ev['immagine']): ?>
                                    <img src="<?php echo htmlspecialchars($ev['immagine']); ?>" class="img-preview" alt="Evento">
                                <?php else: ?>
                                    <div style="width: 60px; height: 40px; background: #eee; border-radius: 4px;"></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($ev['titolo']); ?></strong><br>
                                <small style="color: #666;"><?php echo htmlspecialchars(substr($ev['descrizione'], 0, 50)) . '...'; ?></small>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($ev['data_evento'])); ?></td>
                            <td>
                                <small>
                                    <strong><?php echo htmlspecialchars($ev['categoria']); ?></strong><br>
                                    <?php echo htmlspecialchars($ev['citta']); ?>
                                </small>
                            </td>
                            <td class="action-cell">
                                <a href="modifica_evento.php?id=<?php echo $ev['id']; ?>" class="btn-edit">Modifica</a>
                                
                                <form action="admin_dashboard.php" method="post" onsubmit="return confirm('Sei sicuro di voler eliminare questo evento?');" style="display:inline;">
                                    <input type="hidden" name="azione" value="elimina_evento">
                                    <input type="hidden" name="id_evento" value="<?php echo $ev['id']; ?>">
                                    <button type="submit" class="btn-delete">Elimina</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

</main>

<footer class="site-footer">
    <p>&copy; 2026 EasyTicket - Dashboard Amministrativa</p>
</footer>

</body>
</html>