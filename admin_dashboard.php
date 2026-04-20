<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Solo admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || (int)$_SESSION['ruolo'] !== 1) {
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

// Aggiunta evento
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['azione']) && $_POST['azione'] === 'aggiungi') {
    $titolo = trim($_POST['titolo']);
    $id_categoria = (int)$_POST['id_categoria'];
    $id_luogo = (int)$_POST['id_luogo'];
    $data_evento = $_POST['data_evento']; // formato datetime-local: 2026-07-18T18:00
    $data_evento = str_replace('T', ' ', $data_evento) . ':00';

    $immagine = null;

    if (!empty($_FILES['immagine']['name'])) {
        $uploadDir = "img/eventi/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }
        $nomeFile = time() . "_" . basename($_FILES['immagine']['name']);
        $targetPath = $uploadDir . $nomeFile;

        if (move_uploaded_file($_FILES['immagine']['tmp_name'], $targetPath)) {
            $immagine = $nomeFile;
        }
    }

    $sql = "INSERT INTO evento (titolo, descrizione, data_evento, id_categoria, id_luogo, immagine, stato)
            VALUES (?, NULL, ?, ?, ?, ?, 'programmato')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssiis", $titolo, $data_evento, $id_categoria, $id_luogo, $immagine);
    $stmt->execute();
    $stmt->close();
}

// Eliminazione evento
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['azione']) && $_POST['azione'] === 'elimina') {
    $id_evento = (int)$_POST['id_evento'];
    $del = $conn->prepare("DELETE FROM evento WHERE id = ?");
    $del->bind_param("i", $id_evento);
    $del->execute();
    $del->close();
}

// Carica categorie e luoghi per le select
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

// Eventi esistenti
$eventi = [];
$sqlEventi = "SELECT e.id, e.titolo, e.data_evento, c.nome AS categoria, l.nome AS luogo
              FROM evento e
              JOIN categoria c ON e.id_categoria = c.id
              JOIN luogo l ON e.id_luogo = l.id
              ORDER BY e.data_evento ASC";
$resEventi = $conn->query($sqlEventi);
if ($resEventi && $resEventi->num_rows > 0) {
    while ($row = $resEventi->fetch_assoc()) {
        $eventi[] = $row;
    }
}

$username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'admin';

$conn->close();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EasyTicket</title>
    <link rel="icon" type="image/x-icon" href="img/icn_sito_sf.png">
    <link rel="stylesheet" href="css/style1.css?v=20">
</head>
<body>
    <header class="site-header">
        <div class="header-inner">
            <a href="home.php" class="brand">
                <img src="img/logo_sito.png" alt="Logo EasyTicket">
            </a>

            <nav class="user-nav">
                <a href="admin_dashboard.php" class="user-pill primary-pill">
                    <?php echo $username; ?>
                </a>
                <a href="logout.php" class="user-pill secondary-pill">Logout</a>
            </nav>
        </div>
    </header>

    <main class="page-shell">
        <section class="section-block">
            <div class="section-heading">
                <h2>Amministrazione Eventi</h2>
                <p>Qui puoi aggiungere nuovi eventi o eliminarli.</p>
            </div>

            <div class="admin-grid">
                <!-- Card aggiungi evento -->
                <div class="admin-card">
                    <h3>Aggiungi evento</h3>
                    <form action="admin_dashboard.php" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="azione" value="aggiungi">

                        <label for="titolo">Titolo evento</label>
                        <input type="text" id="titolo" name="titolo" placeholder="Es. Summer Music Festival" required>

                        <label for="id_categoria">Categoria</label>
                        <select id="id_categoria" name="id_categoria" required>
                            <option value="">Seleziona categoria</option>
                            <?php foreach ($categorie as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>">
                                    <?php echo htmlspecialchars($cat['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label for="id_luogo">Luogo</label>
                        <select id="id_luogo" name="id_luogo" required>
                            <option value="">Seleziona luogo</option>
                            <?php foreach ($luoghi as $luogo): ?>
                                <option value="<?php echo $luogo['id']; ?>">
                                    <?php echo htmlspecialchars($luogo['citta'] . " - " . $luogo['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label for="data_evento">Data e ora</label>
                        <input type="datetime-local" id="data_evento" name="data_evento" required>

                        <label for="immagine">Immagine evento (per la card)</label>
                        <input type="file" id="immagine" name="immagine" accept="image/*">

                        <button type="submit" class="primary-btn">Aggiungi evento</button>
                    </form>
                </div>

                <!-- Card elimina evento -->
                <div class="admin-card">
                    <h3>Elimina evento</h3>
                    <?php if (!empty($eventi)): ?>
                        <form action="admin_dashboard.php" method="post">
                            <input type="hidden" name="azione" value="elimina">

                            <label for="id_evento">Evento</label>
                            <select id="id_evento" name="id_evento" required>
                                <?php foreach ($eventi as $e): ?>
                                    <option value="<?php echo $e['id']; ?>">
                                        <?php echo htmlspecialchars($e['titolo'] . " - " . $e['data_evento']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <button type="submit" class="danger-btn">Elimina</button>
                        </form>
                    <?php else: ?>
                        <p>Nessun evento da eliminare.</p>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <p>&copy; 2026 EasyTicket</p>
    </footer>
</body>
</html>