<?php
// gerar-pix.php
//
// Adaptado para a SyncPay (antes apontava para outro gateway/NexusPag).
// Recebe apenas o "plano" escolhido — o valor em reais é sempre resolvido
// no servidor a partir de config.php, nunca confiado no que vem do front-end.

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/syncpay.php';
require_once __DIR__ . '/includes/storage.php';

$input = json_decode(file_get_contents('php://input'), true);
$planoId = $input['plano'] ?? null;

if (!$planoId || empty($PLANS[$planoId])) {
    http_response_code(422);
    echo json_encode(['error' => 'Pacote inválido ou não informado.']);
    exit;
}

$plano = $PLANS[$planoId];

try {
    $tx = syncpay_create_pix($plano['amount'], $plano['description']);

    // Guarda a transação localmente para o webhook conseguir marcar como
    // paga depois, e a consulta de status responder mais rápido.
    $transactions = storage_read('transactions.json');
    $transactions[$tx['identifier']] = [
        'status' => 'pending',
        'amount' => $plano['amount'],
        'description' => $plano['description'],
        'created_at' => time(),
    ];
    storage_write('transactions.json', $transactions);

    // Gera a imagem do QR Code a partir do "pix copia e cola" usando um
    // serviço público de geração de QR Code (evita depender de extensões
    // de imagem/Composer, que nem toda hospedagem PHP grátis oferece).
    $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=320x320&margin=8&data='
        . urlencode($tx['pix_code']);

    echo json_encode([
        'pix_code' => $tx['pix_code'],
        'qr_code_image' => $qrCodeUrl,
        'identifier' => $tx['identifier'],
        'amount' => $plano['amount'],
        'description' => $plano['description'],
        'expires_in_seconds' => PIX_EXPIRATION_MINUTES * 60,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
