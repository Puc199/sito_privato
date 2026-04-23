<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. Controllo sessione: accesso solo a utenti loggati (ruolo 1=admin, 2=utente)
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
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

/* ==========================================================================
   GESTIONE AJAX (FETCH)
   Gestisce le richieste asincrone per caricare settori e posti
   ========================================================================== */
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    // Ritorna i settori per una specifica replica
    if ($_GET['ajax'] === 'get_sectors' && isset($_GET['replica_id'])) {
        $replica_id = (int)$_GET['replica_id'];
        $sql = "SELECT es.id, s.nome, es.prezzo, es.posti_disponibili 
                FROM evento_settore es 
                JOIN settore s ON es.id_settore = s.id 
                WHERE es.id_replica_evento = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $replica_id);
        $stmt->execute();
        $res = $stmt->get_result();
        echo json_encode($res->fetch_all(MYSQLI_ASSOC));
        exit;
    }

    // Ritorna i posti (totali vs occupati) per un settore
    if ($_GET['ajax'] === 'get_seats' && isset($_GET['evento_settore_id'])) {
        $es_id = (int)$_GET['evento_settore_id'];
        
        // Posti totali e info settore
        $stmt = $conn->prepare("SELECT posti_totali FROM evento_settore WHERE id = ?");
        $stmt->bind_param("i", $es_id);
        $stmt->execute();
        $total = $stmt->get_result()->fetch_assoc()['posti_totali'] ?? 0;
        
        // Posti già occupati
        $stmt = $conn->prepare("SELECT posto FROM biglietto WHERE id_evento_settore = ?");
        $stmt->bind_param("i", $es_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $occupied = [];
        while($row = $res->fetch_assoc()) { $occupied[] = (int)$row['posto']; }
        
        echo json_encode(['total' => $total, 'occupied' => $occupied]);
        exit;
    }
}

/* ==========================================================================
   CARICAMENTO DATI EVENTO PRINCIPALE
   ========================================================================== */
$id_evento = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_evento <= 0) { die("Evento non specificato."); }

$sql = "SELECT e.*, c.nome AS categoria_nome, l.nome AS luogo_nome, l.citta 
        FROM evento e 
        JOIN categoria c ON e.id_categoria = c.id 
        JOIN luogo l ON e.id_luogo = l.id 
        WHERE e.id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_evento);
$stmt->execute();
$evento = $stmt->get_result()->fetch_assoc();

if (!$evento) { die("Evento non trovato."); }

// Recupero repliche disponibili
$repliche = [];
$stmt = $conn->prepare("SELECT id, data_ora_inizio FROM replica_evento WHERE id_evento = ? AND stato = 'programmata' ORDER BY data_ora_inizio ASC");
$stmt->bind_param("i", $id_evento);
$stmt->execute();
$resRep = $stmt->get_result();
while($r = $resRep->fetch_assoc()) { $repliche[] = $r; }

$username = $_SESSION['username'] ?? 'Utente';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($evento['titolo']); ?> - EasyTicket</title>
    <link rel="stylesheet" href="css/style1.css?v=<?php echo time(); ?>">
    <link rel="icon" type="image/x-icon" href="img/icn_sito_sf.png">
    <style>
        .booking-container { display: grid; grid-template-columns: 1fr 350px; gap: 30px; margin-top: 30px; }
        .event-detail-card img { width: 100%; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .seat-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(45px, 1fr)); gap: 8px; margin-top: 15px; max-height: 300px; overflow-y: auto; padding: 10px; border: 1px solid #ddd; border-radius: 8px; }
        .seat-item { position: relative; }
        .seat-item input { position: absolute; opacity: 0; cursor: pointer; }
        .seat-label { display: block; text-align: center; padding: 8px 0; background: #ebf5fb; border: 1px solid #3498db; border-radius: 4px; font-size: 0.8rem; cursor: pointer; transition: 0.2s; }
        .seat-item input:checked + .seat-label { background: #f39c12; border-color: #e67e22; color: white; }
        .seat-item.occupied .seat-label { background: #ecf0f1; border-color: #bdc3c7; color: #95a5a6; cursor: not-allowed; }
        .price-tag { font-size: 1.5rem; color: #27ae60; font-weight: bold; margin: 15px 0; }
        .summary-box { background: #f9f9f9; padding: 15px; border-radius: 8px; border-left: 5px solid #3498db; margin-top: 20px; }
        @media (max-width: 850px) { .booking-container { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<header class="site-header">
    <div class="header-inner">
        <a href="home.php" class="brand">
            <img src="img/logo_sito.png" alt="Logo EasyTicket">
        </a>
        <nav class="user-nav">
            <?php if ((int)$_SESSION['ruolo'] === 1): ?>
                <a href="admin_dashboard.php" class="user-pill primary-pill">Admin</a>
            <?php else: ?>
                <a href="User_dashboard.php" class="user-pill primary-pill"><?php echo htmlspecialchars($username); ?></a>
            <?php endif; ?>
            <a href="logout.php" class="user-pill secondary-pill">Logout</a>
        </nav>
    </div>
</header>

<main class="page-shell">
    <section class="section-block">
        <div class="section-heading">
            <h1><?php echo htmlspecialchars($evento['titolo']); ?></h1>
            <p><?php echo htmlspecialchars($evento['categoria_nome']); ?> | <?php echo htmlspecialchars($evento['luogo_nome']); ?> (<?php echo htmlspecialchars($evento['citta']); ?>)</p>
        </div>

        <div class="booking-container">
            <div class="event-detail-card">
                <?php if ($evento['immagine']): ?>
                    <img src="<?php echo htmlspecialchars($evento['immagine']); ?>" alt="Poster">
                <?php endif; ?>
                <div class="admin-card">
                    <h3>Descrizione</h3>
                    <p><?php echo nl2br(htmlspecialchars($evento['descrizione'])); ?></p>
                </div>
            </div>

            <div class="purchase-form">
                <div class="admin-card">
                    <h3>Prenota Biglietti</h3>
                    <form id="bookingForm" action="conferma_acquisto.php" method="post">
                        <input type="hidden" name="id_evento" value="<?php echo $id_evento; ?>">

                        <label for="id_replica">Data e Ora</label>
                        <select name="id_replica" id="id_replica" required>
                            <option value="">Seleziona una data...</option>
                            <?php if (empty($repliche)): ?>
                                <option value="0">Data principale: <?php echo date('d/m/Y H:i', strtotime($evento['data_evento'])); ?></option>
                            <?php else: ?>
                                <?php foreach ($repliche as $r): ?>
                                    <option value="<?php echo $r['id']; ?>"><?php echo date('d/m/Y H:i', strtotime($r['data_ora_inizio'])); ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>

                        <div id="sector-container" style="display:none; margin-top:15px;">
                            <label for="id_settore">Settore</label>
                            <select name="id_settore" id="id_settore" required>
                                <option value="">Scegli settore...</option>
                            </select>
                            <div id="price-display" class="price-tag"></div>
                        </div>

                        <div id="seats-container" style="display:none; margin-top:15px;">
                            <label>Seleziona i tuoi posti</label>
                            <div id="seat-grid" class="seat-grid">
                                </div>
                            
                            <div style="margin-top:15px;">
                                <label for="quantita">Quantità selezionata</label>
                                <input type="number" name="quantita" id="quantita" value="0" readonly>
                            </div>
                        </div>

                        <div id="summary" class="summary-box" style="display:none;">
                            <strong>Totale: </strong> <span id="total-price">€ 0,00</span>
                        </div>

                        <button type="submit" id="btnSubmit" class="admin-submit" style="margin-top:20px; display:none;">
                            Procedi all'acquisto
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
</main>

<footer class="site-footer">
    <p>&copy; 2026 EasyTicket - Tutti i diritti riservati</p>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const replicaSelect = document.getElementById('id_replica');
    const sectorSelect = document.getElementById('id_settore');
    const sectorContainer = document.getElementById('sector-container');
    const seatsContainer = document.getElementById('seats-container');
    const seatGrid = document.getElementById('seat-grid');
    const priceDisplay = document.getElementById('price-display');
    const quantitaInput = document.getElementById('quantita');
    const summaryBox = document.getElementById('summary');
    const totalPriceSpan = document.getElementById('total-price');
    const btnSubmit = document.getElementById('btnSubmit');

    let currentPrice = 0;

    // Quando cambio Replica -> Carico Settori
    replicaSelect.addEventListener('change', function() {
        const replicaId = this.value;
        if (!replicaId) { resetUI(); return; }

        fetch(`evento.php?id=<?php echo $id_evento; ?>&ajax=get_sectors&replica_id=${replicaId}`)
            .then(res => res.json())
            .then(data => {
                sectorSelect.innerHTML = '<option value="">Scegli settore...</option>';
                data.forEach(s => {
                    sectorSelect.innerHTML += `<option value="${s.id}" data-price="${s.prezzo}">${s.nome} (€ ${parseFloat(s.prezzo).toFixed(2)})</option>`;
                });
                sectorContainer.style.display = 'block';
                seatsContainer.style.display = 'none';
                btnSubmit.style.display = 'none';
            });
    });

    // Quando cambio Settore -> Carico Posti
    sectorSelect.addEventListener('change', function() {
        const esId = this.value;
        const selectedOption = this.options[this.selectedIndex];
        currentPrice = parseFloat(selectedOption.getAttribute('data-price') || 0);

        if (!esId) { seatsContainer.style.display = 'none'; return; }

        fetch(`evento.php?id=<?php echo $id_evento; ?>&ajax=get_seats&evento_settore_id=${esId}`)
            .then(res => res.json())
            .then(data => {
                seatGrid.innerHTML = '';
                for (let i = 1; i <= data.total; i++) {
                    const isOccupied = data.occupied.includes(i);
                    const seatDiv = document.createElement('div');
                    seatDiv.className = `seat-item ${isOccupied ? 'occupied' : ''}`;
                    
                    seatDiv.innerHTML = `
                        <input type="checkbox" name="posti[]" value="${i}" id="p${i}" ${isOccupied ? 'disabled' : ''}>
                        <label class="seat-label" for="p${i}">P${i}</label>
                    `;
                    seatGrid.appendChild(seatDiv);
                }
                
                priceDisplay.textContent = `Prezzo unitario: € ${currentPrice.toFixed(2)}`;
                seatsContainer.style.display = 'block';
                updateSelection();
            });
    });

    // Gestione click sui posti (delegation)
    seatGrid.addEventListener('change', function(e) {
        if (e.target.name === 'posti[]') {
            updateSelection();
        }
    });

    function updateSelection() {
        const selectedSeats = document.querySelectorAll('input[name="posti[]"]:checked');
        const count = selectedSeats.length;
        quantitaInput.value = count;
        
        if (count > 0) {
            const total = count * currentPrice;
            totalPriceSpan.textContent = `€ ${total.toLocaleString('it-IT', {minimumFractionDigits: 2})}`;
            summaryBox.style.display = 'block';
            btnSubmit.style.display = 'block';
        } else {
            summaryBox.style.display = 'none';
            btnSubmit.style.display = 'none';
        }
    }

    function resetUI() {
        sectorContainer.style.display = 'none';
        seatsContainer.style.display = 'none';
        summaryBox.style.display = 'none';
        btnSubmit.style.display = 'none';
    }
});
</script>

</body>
</html>
<?php $conn->close(); ?>