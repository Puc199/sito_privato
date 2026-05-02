<?php
require_once 'init.php';

$events = [];
$sql = "
    SELECT
        e.id,
        e.titolo,
        e.descrizione,
        e.immagine,
        e.stato,
        c.nome AS categoria,
        l.nome AS luogo,
        l.citta,
        MIN(r.data_ora_inizio) AS prima_data
    FROM evento e
    JOIN categoria c ON e.id_categoria = c.id
    JOIN luogo l ON e.id_luogo = l.id
    LEFT JOIN replica_evento r ON r.id_evento = e.id
    WHERE e.stato = 'programmato'
    GROUP BY e.id, e.titolo, e.descrizione, e.immagine, e.stato, c.nome, l.nome, l.citta
    ORDER BY prima_data ASC
";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
}

$role      = isset($_SESSION['ruolo']) ? (int)$_SESSION['ruolo'] : null;
$username  = isset($_SESSION['username']) ? esc($_SESSION['username']) : null;
$isLogged  = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
$conn->close();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>EasyTicket</title>
    <link rel="stylesheet" href="css/style1.css">
    <link rel="icon" type="image/png" href="img/icn_sito_sf.png">
</head>
<body>
<header class="site-header">
    <div class="header-inner">
        <a href="home.php" class="brand"><img src="img/logo_sito.png" alt="EasyTicket"></a>
        <nav class="user-nav">
            <?php if ($isLogged): ?>
                <?php if ($role === 1): ?>
                    <a href="admin_dashboard.php" class="user-pill primary-pill">Admin</a>
                <?php else: ?>
                    <a href="User_dashboard.php" class="user-pill primary-pill"><?php echo $username; ?></a>
                <?php endif; ?>
                <a href="logout.php" class="user-pill secondary-pill">Logout</a>
            <?php else: ?>
                <a href="login.php" class="user-pill secondary-pill">Accedi</a>
                <a href="registra.php" class="user-pill primary-pill">Registrati</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="page-shell">
    <section class="hero-section" style="background-image:url('img/concerto.jpg')">
        <div class="hero-content">
            <h1>Benvenuto su EasyTicket</h1>
            <p><?php echo $isLogged ? "Scopri i prossimi eventi e prenota in pochi click." : "Prenota i tuoi eventi in modo semplice, veloce e sicuro."; ?></p>
            <?php if (!$isLogged): ?>
                <a href="registra.php" class="hero-cta">Inizia ora</a>
            <?php endif; ?>
        </div>
    </section>

    <section class="section-block">
        <div class="section-heading">
            <h2>Eventi in evidenza</h2>
            <p>Seleziona un evento per vedere i dettagli e procedere con la prenotazione.</p>
        </div>

        <?php if (empty($events)): ?>
            <div class="empty-state">
                <h3>Nessun evento disponibile</h3>
                <p>Al momento non ci sono eventi caricati nel sistema.</p>
            </div>
        <?php else: ?>
            <div class="events-grid">
                <?php foreach ($events as $event): ?>
                    <a href="evento.php?id=<?php echo (int)$event['id']; ?>" class="event-card">
                        <div class="event-thumb">
                            <img src="<?php echo !empty($event['immagine']) ? esc($event['immagine']) : 'img/evento-default.png'; ?>"
                                 alt="<?php echo esc($event['titolo']); ?>" loading="lazy">
                        </div>
                        <div class="event-info">
                            <span class="event-category"><?php echo esc($event['categoria']); ?></span>
                            <h3><?php echo esc($event['titolo']); ?></h3>
                            <p><?php echo esc(mb_strimwidth((string)($event['descrizione'] ?? ''), 0, 100, '...')); ?></p>
                            <div class="event-meta">
                                <span><?php echo esc($event['luogo'] . ' - ' . $event['citta']); ?></span>
                                <?php if (!empty($event['prima_data'])): ?>
                                    <span><?php echo esc(date('d/m/Y H:i', strtotime($event['prima_data']))); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<footer class="site-footer"><p>&copy; 2026 EasyTicket</p></footer>
</body>
</html>