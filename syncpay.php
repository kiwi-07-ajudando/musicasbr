<?php
// includes/syncpay.php
//
// Toda a comunicação com a API da SyncPay fica centralizada aqui.

require_once __DIR__ . '/storage.php';

/**
 * Retorna um access_token válido, reaproveitando o token em cache
 * (arquivo storage/token_cache.json) enquanto ele não estiver perto de
 * expirar. Só gera um token novo quando necessário, como recomenda a
 * documentação da SyncPay (não gerar um token a cada requisição).
 */
function syncpay_get_access_token() {
    $cache = storage_read('token_cache.json');
    $now = time();

    if (!empty($cache['access_token']) && !empty($cache['expires_at']) && $now < ($cache['expires_at'] - 60)) {
        return $cache['access_token'];
    }

    $response = syncpay_http_post('/api/partner/v1/auth-token', [
        'client_id' => SYNCPAY_CLIENT_ID,
        'client_secret' => SYNCPAY_CLIENT_SECRET,
    ]);

    if (empty($response['body']['access_token'])) {
        $desc = $response['body']['error_description'] ?? 'credenciais inválidas ou não configuradas';
        throw new Exception('Não foi possível autenticar na SyncPay: ' . $desc);
    }

    $data = $response['body'];
    storage_write('token_cache.json', [
        'access_token' => $data['access_token'],
        'expires_at' => $now + intval($data['expires_in']),
    ]);

    return $data['access_token'];
}

/**
 * Cria uma cobrança Pix (cash-in) e devolve o array com pix_code e
 * identifier, como a SyncPay retorna.
 */
function syncpay_create_pix($amount, $description) {
    $token = syncpay_get_access_token();

    $payload = [
        'amount' => $amount,
        'description' => $description,
    ];

    if (defined('WEBHOOK_URL') && WEBHOOK_URL) {
        $payload['webhook_url'] = WEBHOOK_URL;
    }

    $response = syncpay_http_post('/api/partner/v1/cash-in', $payload, $token);

    if ($response['http_code'] !== 200 || empty($response['body']['pix_code'])) {
        $msg = $response['body']['message'] ?? 'Erro ao gerar cobrança PIX.';
        throw new Exception($msg);
    }

    return $response['body'];
}

/**
 * Consulta o status atual de uma transação na SyncPay.
 */
function syncpay_get_transaction($identifier) {
    $token = syncpay_get_access_token();
    $response = syncpay_http_get('/api/partner/v1/transaction/' . urlencode($identifier), $token);

    if ($response['http_code'] === 404) {
        return ['status' => 'pending'];
    }

    if ($response['http_code'] !== 200) {
        throw new Exception('Erro ao consultar transação.');
    }

    return $response['body']['data'] ?? ['status' => 'pending'];
}

function syncpay_http_post($path, $payload, $token = null) {
    $headers = ['Content-Type: application/json', 'Accept: application/json'];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    $ch = curl_init(SYNCPAY_BASE_URL . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 30,
    ]);

    $raw = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        throw new Exception('Erro de conexão com a SyncPay: ' . $err);
    }

    return ['http_code' => $httpCode, 'body' => json_decode($raw, true)];
}

function syncpay_http_get($path, $token) {
    $ch = curl_init(SYNCPAY_BASE_URL . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token, 'Accept: application/json'],
        CURLOPT_TIMEOUT => 30,
    ]);

    $raw = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        throw new Exception('Erro de conexão com a SyncPay: ' . $err);
    }

    return ['http_code' => $httpCode, 'body' => json_decode($raw, true)];
}
