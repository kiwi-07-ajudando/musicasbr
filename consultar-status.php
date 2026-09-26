<?php
// consultar-status.php
//
// Usado pelo checkout.html via polling (a cada 5s) para saber se o Pix já
// foi pago. Primeiro olha o cache local (que o webhook-pix.php atualiza);
// se ainda não tiver a confirmação, pergunta direto para a SyncPay.

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/syncpay.php';
require_once __DIR__ . '/includes/storage.php';

$identifier = $_GET['identifier'] ?? null;

if (!$identifier) {
    http_response_code(422);
    echo json_encode(['error' => 'identifier é obrigatório']);
    exit;
}

$transactions = storage_read('transactions.json');
$local = $transactions[$identifier] ?? null;

if ($local && in_array($local['status'], ['completed', 'paid'], true)) {
    echo json_encode(['status' => 'completed']);
    exit;
}

try {
    $data = syncpay_get_transaction($identifier);
    $status = $data['status'] ?? 'pending';

    if ($local) {
        $transactions[$identifier]['status'] = $status;
        storage_write('transactions.json', $transactions);
    }

    echo json_encode(['status' => $status]);
} catch (Exception $e) {
    // Falha pontual na consulta não deve travar o checkout — o front-end
    // tenta de novo automaticamente no próximo ciclo de polling.
    echo json_encode(['status' => 'pending']);
}
