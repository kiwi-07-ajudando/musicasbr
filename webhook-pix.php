<?php
// webhook-pix.php
//
// Cadastre esta URL pública (ex.: https://seudominio.com/webhook-pix.php)
// no campo WEBHOOK_URL do config.php, ou no painel da SyncPay. Ela recebe a
// notificação assim que o status de uma cobrança muda — mais rápido e mais
// confiável do que só depender do polling do checkout.

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/storage.php';

$event = json_decode(file_get_contents('php://input'), true);

// O formato exato do payload varia por evento (veja a seção "Webhooks" da
// documentação da SyncPay); tratamos de forma tolerante, pegando o
// identifier e o status onde quer que eles venham.
$identifier = $event['identifier']
    ?? $event['data']['identifier']
    ?? $event['data']['transaction']['reference_id']
    ?? null;

$status = $event['status']
    ?? $event['data']['status']
    ?? $event['data']['transaction']['status']
    ?? null;

if ($identifier && $status) {
    $transactions = storage_read('transactions.json');
    $transactions[$identifier] = array_merge($transactions[$identifier] ?? [], [
        'status' => $status,
        'updated_at' => time(),
    ]);
    storage_write('transactions.json', $transactions);
}

// Sempre responde 200, para a SyncPay não ficar reenviando o webhook.
http_response_code(200);
echo json_encode(['ok' => true]);
