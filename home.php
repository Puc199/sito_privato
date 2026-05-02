<?php
require_once 'init.php';

// Legge gli eventi dal database
$events = [];
$sql = "SELECT 
            e.id, 
            e.titolo, 
            MIN(r.data_ora_inizio) AS data_evento, 
            e.immagine, 
            c.nome AS categoria, 
            l.nome AS luogo
        FROM evento e
        JOIN replica_evento r ON r.id_evento = e.id
        JOIN categoria c ON e.id_categoria = c.id
        JOIN luogo l ON e.id_luogo = l.id
        GROUP BY e.id, e.titolo, e.immagine, c.nome, l.nome
        ORDER BY data_evento ASC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
}

$role = isset($_SESSION['ruolo']) ? (int)$_SESSION['ruolo'] : null;
$username = isset($_SESSION['username']) ? esc($_SESSION['username']) : null;
$isLogged = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
$heroTitle = "Benvenuto su EasyTicket";
$heroSubtitle = $isLogged
    ? "Scopri i prossimi eventi, gestisci i tuoi biglietti e prenota in pochi click."
    : "Prenota i tuoi eventi in modo semplice, veloce e sicuro.";
$heroBackground = "img/concerto.jpg";
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EasyTicket</title>
    <link rel="icon" type="image/x-icon" href="img/icn_sito_sf.png">
    <link rel="stylesheet" href="css/style1.css?v=30">
</head>
<body>
    <header class="site-header">
        <div class="header-inner">
            <a href="home.php" class="brand">
                <img src="img/logo_sito.png" alt="Logo EasyTicket">
            </a>

            <nav class="user-nav">
                <?php if ($isLogged): ?>
                    <a href="<?php echo $role === 1 ? 'admin_dashboard.php' : 'User_dashboard.php'; ?>" class="user-pill primary-pill">
                        <?php echo $username; ?>
                    </a>
                    <a href="logout.php" class="user-pill secondary-pill">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="user-pill primary-pill">Login</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="page-shell">
        <section class="hero-section" style="background: linear-gradient(rgba(7, 25, 41, 0.45), rgba(7, 25, 41, 0.55)), url('<?php echo htmlspecialchars($heroBackground, ENT_QUOTES, 'UTF-8'); ?>') center/cover no-repeat;">
            <div class="hero-overlay"></div>
            <div class="hero-content">
                <h1><?php echo $heroTitle; ?></h1>
                <p><?php echo $heroSubtitle; ?></p>
                <a href="#eventi" class="hero-cta">Vedi Spettacoli</a>
            </div>
        </section>

        <section class="category-bar">
            <div class="category-item">Tutti gli Eventi</div>
            <div class="category-item">Concerti</div>
            <div class="category-item">Teatro</div>
            <div class="category-item">Sport</div>
            <div class="category-item">Festival</div>
            <div class="category-search">Cerca</div>
        </section>

        <section class="section-block" id="eventi">
            <div class="section-heading">
                <h2>Eventi in evidenza</h2>
                <p>Seleziona un evento per vedere i dettagli e procedere con la prenotazione.</p>
            </div>

            <?php if (!empty($events)): ?>
                <div class="matches-grid">
                    <?php foreach ($events as $event): ?>
                        <article class="match-card" onclick="handleEventClick(<?php echo (int)$event['id']; ?>)">
                            <div class="match-card-top">
                                <span class="match-badge"><?php echo htmlspecialchars($event['categoria']); ?></span>
                                <span class="match-date"><?php echo htmlspecialchars($event['data_evento']); ?></span>
                            </div>

                            <div class="match-logos">
                                <?php if (!empty($event['immagine'])): ?>
                                    <img src="<?php echo htmlspecialchars($event['immagine']); ?>" alt="<?php echo htmlspecialchars($event['titolo']); ?>">
                                <?php else: ?>
                                    <img src="img/evento-default.png" alt="Evento">
                                <?php endif; ?>
                            </div>

                            <div class="match-details">
                                <h3><?php echo htmlspecialchars($event['titolo']); ?></h3>
                                <p><?php echo htmlspecialchars($event['luogo']); ?></p>
                            </div>

                            <div class="match-card-bottom">
                                <span class="match-action">Vai all'evento</span>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h3>Nessun evento disponibile</h3>
                    <p>Al momento non ci sono eventi caricati nel sistema.</p>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <footer class="site-footer">
        <p>&copy; 2026 EasyTicket</p>
    </footer>

    <script>
        function handleEventClick(eventId) {
    <?php if ($isLogged): ?>
        <?php if ($role === 1): ?>
            window.location.href = "admin_dashboard.php?id=" + eventId;
        <?php elseif ($role === 2): ?>
            window.location.href = "User_dashboard.php";
        <?php else: ?>
            window.location.href = "login.php";
        <?php endif; ?>
    <?php else: ?>
        window.location.href = "login.php";
    <?php endif; ?>
}
    </script>
</body>
</html>