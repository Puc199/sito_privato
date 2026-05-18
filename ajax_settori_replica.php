<?php
require_once 'init.php';

header('Content-Type: application/json');

$response = [
    'success' => false,
    'settori' => [],
    'message' => 'Replica non valida.'
];

$id_replica = isset($_GET['id_replica']) ? (int)$_GET['id_replica'] : 0;

if ($id_replica <= 0) {
    echo json_encode($response);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT
            es.id,
            es.id_settore,
            s.nome AS nome_settore,
            es.prezzo,
            es.posti_disponibili
        FROM evento_settore es
        JOIN settore s ON es.id_settore = s.id
        WHERE es.id_replica_evento = ?
        ORDER BY s.nome ASC
    ");
    $stmt->execute([$id_replica]);

    $settori = $stmt->fetchAll();

    $response['success'] = true;
    $response['settori'] = $settori;
    $response['message'] = empty($settori) ? 'Nessun settore disponibile.' : '';
} catch (PDOException $e) {
    $response['message'] = 'Errore nel recupero dei settori.';
}

echo json_encode($response);
?>