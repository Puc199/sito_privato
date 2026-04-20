<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$partitaId = isset($_GET['id']) ? intval($_GET['id']) : 0;

function getWalletBalance($username) {
    $conn = new mysqli("localhost", "root", "", "sito");
    if ($conn->connect_error) {
        die("Connessione fallita: " . $conn->connect_error);
    }
    $stmt = $conn->prepare("SELECT saldo FROM utente WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $walletBalance = $row['saldo'];

    $stmt->close();
    $conn->close();
    return $walletBalance;
}

function getTicketPrices() {
    $conn = new mysqli("localhost", "root", "", "sito");
    if ($conn->connect_error) {
        die("Connessione fallita: " . $conn->connect_error);
    }

    $prices = array();
    $result = $conn->query("SELECT 'VIP' as sector, prezzo FROM vip UNION SELECT 'Tribuna', prezzo FROM tribuna UNION SELECT 'Curva', prezzo FROM curva");
    while ($row = $result->fetch_assoc()) {
        $prices[$row['sector']] = $row['prezzo'];
    }

    $conn->close();
    return $prices;
}

function getMatchDetails($partitaId) {
    $conn = new mysqli("localhost", "root", "", "sito");
    if ($conn->connect_error) {
        die("Connessione fallita: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("
        SELECT p.*,s2.nome AS nome_squadra_ospite 
        FROM partita p
        JOIN squadre s2 ON p.id_squadraOspite = s2.id
        WHERE p.id = ?");
    $stmt->bind_param("i", $partitaId);
    $stmt->execute();
    $result = $stmt->get_result();
    $matchDetails = $result->fetch_assoc();

    $stmt->close();
    $conn->close();
    return $matchDetails;
}

function getAvailableSeats($partitaId) {
    $conn = new mysqli("localhost", "root", "", "sito");
    if ($conn->connect_error) {
        die("Connessione fallita: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("SELECT Posti_vip, Posti_tribuna, Posti_curva FROM partita WHERE id = ?");
    $stmt->bind_param("i", $partitaId);
    $stmt->execute();
    $result = $stmt->get_result();
    $seats = $result->fetch_assoc();

    $stmt = $conn->prepare("SELECT posto, settore FROM biglietto WHERE ID_Partita = ?");
    $stmt->bind_param("i", $partitaId);
    $stmt->execute();
    $result = $stmt->get_result();
    $occupiedSeats = [];
    while ($row = $result->fetch_assoc()) {
        $occupiedSeats[$row['settore']][] = $row['posto'];
    }

    $stmt->close();
    $conn->close();

    return ['available' => $seats, 'occupied' => $occupiedSeats];
}

$walletBalance = getWalletBalance($username);
$prices = getTicketPrices();
$matchDetails = getMatchDetails($partitaId);
$seatsData = getAvailableSeats($partitaId);

if (!$matchDetails) {
    echo "Nessuna partita trovata con l'ID specificato.";
    exit();
}

$allSeatsFull = ($seatsData['available']['Posti_vip'] == 0) && ($seatsData['available']['Posti_tribuna'] == 0) && ($seatsData['available']['Posti_curva'] == 0);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ticketPrice = isset($_POST['ticket_price']) ? floatval($_POST['ticket_price']) : 0;
    $sector = $_POST['sector'];
    $seat = intval($_POST['seat']);

    if ($partitaId > 0 && $walletBalance >= $ticketPrice) {
        $conn = new mysqli("localhost", "root", "", "sito");
        if ($conn->connect_error) {
            die("Connessione fallita: " . $conn->connect_error);
        }

        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare("SELECT id FROM utente WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $userId = $result->fetch_assoc()['id'];
            $stmt->close();

            $sigilloFiscale = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyz"), 0, 15);

            $_SESSION['ticket'] = [
                'Sigillo_Fiscale' => $sigilloFiscale,
                'posto' => $seat,
                'prezzo' => $ticketPrice,
                'sector' => $sector
            ];

            $stmt = $conn->prepare("UPDATE utente SET saldo = saldo - ? WHERE id =?");
            $stmt->bind_param("di", $ticketPrice, $userId);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("INSERT INTO biglietto (Sigillo_Fiscale, Disponibilità, ID_Utente, ID_Partita, posto, settore, prezzo) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $disponibilita = 1;
            $stmt->bind_param("siiissi", $sigilloFiscale, $disponibilita, $userId, $partitaId, $seat, $sector, $ticketPrice);
            $stmt->execute();
            $stmt->close();

            $field = '';
            if ($sector === 'VIP') {
                $field = 'Posti_vip';
            } elseif ($sector === 'Tribuna') {
                $field = 'Posti_tribuna';
            } elseif ($sector === 'Curva') {
                $field = 'Posti_curva';
            }

            if ($field) {
                $stmt = $conn->prepare("UPDATE partita SET $field = $field - 1 WHERE id = ?");
                $stmt->bind_param("i", $partitaId);
                $stmt->execute();
                $stmt->close();
            }

            $stmt = $conn->prepare("UPDATE partita SET N_biglietti = N_biglietti - 1 WHERE id = ?");
            $stmt->bind_param("i", $partitaId);
            $stmt->execute();
            $stmt->close();

            $conn->commit();

            header("Location: confirmation.php");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            echo "Errore durante l'acquisto del biglietto: " . $e->getMessage();
        } finally {
            $conn->close();
        }
    } elseif ($walletBalance < $ticketPrice) {
        header("Location: ricarica_saldo.php");
        exit();
    } else {
        echo "La partita selezionata non esiste.";
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acquisto Biglietto</title>
    <link rel="stylesheet" href="css/style3.css">
    <link rel="icon" type="image/png" href="img/icn_sito_sf.png">
    <style>
        .notification {
            display: none;
            color: red;
            margin-top: 10px;
        }
        .posti-finiti {
            color: red;
            font-weight: bold;
            font-size: 1.2em;
        }
    </style>
</head>
<body>
    <header>
        <h1>Benvenuto, <?php echo htmlspecialchars($username); ?></h1>
        <p>Saldo: <?php echo $walletBalance; ?>€</p>
        <p>Partita: <?php echo htmlspecialchars($matchDetails['Squadra_C']) . " vs " . htmlspecialchars($matchDetails['nome_squadra_ospite']); ?></p>
    </header>
    <main>
        <?php if ($allSeatsFull) { ?>
            <div class="posti-finiti">Posti finiti</div>
        <?php } else { ?>
            <form id="ticketForm" method="post">
                <div class="form-group">
                    <label for="sector">Settore</label>
                    <select id="sector" name="sector">
                        <option value="">Seleziona un settore</option>
                        <?php foreach ($prices as $sector => $price) {
                            $availableSeatsKey = "Posti_" . strtolower($sector);
                            if ($seatsData['available'][$availableSeatsKey] > 0) { ?>
                                <option value="<?php echo htmlspecialchars($sector); ?>"><?php echo htmlspecialchars($sector); ?></option>
                            <?php } } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="seat">Posto</label>
                    <select id="seat" name="seat">
                        <option value="">Seleziona un posto</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="ticket_price">Prezzo</label>
                    <input type="text" id="ticket_price" name="ticket_price" readonly>
                </div>
                <input type="hidden" id="ticketPrice" name="ticket_price" value="">
                <button type="submit">Acquista Biglietto</button>
            </form>
        <?php } ?>
        <div id="notification" class="notification"></div>
    </main>
    <script>
        document.getElementById('sector').addEventListener('change', function() {
            const settore = this.value;
            const selezionePosto = document.getElementById('seat');
            selezionePosto.innerHTML = '<option value="">Seleziona un posto</option>';

            const postiDisponibili = <?php echo json_encode($seatsData['available']); ?>;
            const postiOccupati = <?php echo json_encode($seatsData['occupied']); ?>;

            if (settore) {
                const chiaveSettore = "Posti_" + settore.toLowerCase();
                const totalePosti = postiDisponibili[chiaveSettore];
                const postiOccupatiSettore = postiOccupati[settore] || [];
                
                for (let posto = 1; posto <= totalePosti; posto++) {
                    if (!postiOccupatiSettore.includes(posto)) {
                        const opzione = document.createElement('option');
                        opzione.value = posto;
                        opzione.textContent = posto;
                        selezionePosto.appendChild(opzione);
                    }
                }

                const prezzi = <?php echo json_encode($prices); ?>;
                const prezzo = prezzi[settore] || 0;
                document.getElementById('ticket_price').value = prezzo ? prezzo + "€" : "0€";
                document.getElementById('ticketPrice').value = prezzo;
            }
        });

        document.getElementById('ticketForm').addEventListener('submit', function(event) {
            const settore = document.getElementById('sector').value;
            const posto = document.getElementById('seat').value;
            const prezzoBiglietto = parseFloat(document.getElementById('ticket_price').value.replace('€', ''));
            const saldoWallet = <?php echo $walletBalance; ?>;
            const notifica = document.getElementById('notification');

            if (!settore || !posto) {
                notifica.textContent = "Per favore, seleziona sia un settore che un posto.";
                notifica.style.display = 'block';
                event.preventDefault();
            } else if (saldoWallet < prezzoBiglietto) {
                window.location.href = 'ricarica_saldo.php';
                event.preventDefault();
            } else {
                notifica.style.display = 'none';
            }
        });
    </script>
</body>
</html>
