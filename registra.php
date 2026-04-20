<!DOCTYPE html>
<html>
<head>
    <title>EasyTicket</title>
    <link rel="stylesheet" href="css/style2.css?v=20">
</head>
<body>
    <div class="main">
        <h1>EasyTicket</h1>
        <?php
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $servername = "localhost";
            $db_username = "root";
            $db_password = "";
            $dbname = "EasyTicket";

            $conn = new mysqli($servername, $db_username, $db_password, $dbname);

            if ($conn->connect_error) {
                die("Connessione fallita: " . $conn->connect_error);
            }

            $nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
            $cognome = isset($_POST['cognome']) ? trim($_POST['cognome']) : '';
            $data_nascita = isset($_POST['data_nascita']) ? $_POST['data_nascita'] : '';
            $username = isset($_POST['username']) ? trim($_POST['username']) : '';
            $password = isset($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : '';
            $id_ruolo = 2;
            $saldo = 0.00;

            $stmt = $conn->prepare("SELECT id FROM utente WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                echo "<p>Il nome utente è già presente. Scegli un altro nome utente.</p>";
            } else {
                $sql_utente = "INSERT INTO utente (nome, cognome, data_nascita, username, password, id_ruolo, saldo) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $insert_stmt = $conn->prepare($sql_utente);
                $insert_stmt->bind_param("sssssid", $nome, $cognome, $data_nascita, $username, $password, $id_ruolo, $saldo);

                if ($insert_stmt->execute()) {
                    echo "<p>Registrazione completata con successo. Reindirizzamento alla pagina di login...</p>";
                    header("Refresh: 3; URL=login.php");
                    exit();
                } else {
                    echo "<p>Errore nell'inserimento dell'utente: " . $insert_stmt->error . "</p>";
                }

                $insert_stmt->close();
            }

            $stmt->close();
            $conn->close();
        }
        ?>

        <form action="" method="post">
            <label for="first">Nome:</label>
            <input type="text" id="first" name="nome" placeholder="Inserisci Nome" required>

            <label for="last">Cognome:</label>
            <input type="text" id="last" name="cognome" placeholder="Inserisci cognome" required>

            <label for="dob">Data di Nascita:</label>
            <input type="date" id="dob" name="data_nascita" required>

            <label for="username">Username:</label>
            <input type="text" id="username" name="username" placeholder="Inserisci Nome Utente" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" placeholder="Inserisci Password" required>

            <div class="wrap">
                <button type="submit" name="bottone">Registrati</button>
            </div>
        </form>
    </div>
</body>
</html>