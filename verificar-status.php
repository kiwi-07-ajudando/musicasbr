<?php
// verificar-status.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// ─── CONFIGURE AQUI ───────────────────────────────────────────
define('NEXUSPAG_API_KEY', 'COLOQUE_SUA_CHAVE_AQUI');  // A MESMA chave usada em gerar-pix.php
define('NEXUSPAG_BASE_URL', 'https://nexuspag.com');
// ──────────────────────────────────────────────────────────────

$id = $_GET['id'] ?? '';
if (empty($id)) {
    http_response_code(400);
    echo json_encode(['error' => 'ID não informado']);
    exit;
}

$ch = curl_init(NEXUSPAG_BASE_URL . '/api/pix/' . urlencode($id));
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'x-api-key: ' . NEXUSPAG_API_KEY,
    ],
    CURLOPT_TIMEOUT => 15,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);

// Status vem dentro de transaction.status
$status = $data['transaction']['status'] ?? 'pending';

echo json_encode(['status' => $status]);
