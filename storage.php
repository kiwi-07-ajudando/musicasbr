<?php
// includes/storage.php
//
// Hospedagem PHP compartilhada normalmente não tem banco de dados incluso
// de graça, então usamos arquivos JSON simples (com trava de arquivo para
// evitar corromper os dados em acessos simultâneos) para guardar:
//   - o access_token da SyncPay em cache (evita gerar um token a cada request)
//   - o status de cada transação criada (para o webhook atualizar e a
//     consulta de status responder mais rápido)
//
// Isso é suficiente para o volume de um checkout pequeno/médio. Se o
// negócio crescer, troque por um banco de dados (MySQL, que a maioria dos
// provedores de hospedagem PHP já oferece de graça).

function storage_path($filename) {
    return __DIR__ . '/../storage/' . $filename;
}

function storage_read($filename) {
    $path = storage_path($filename);
    if (!file_exists($path)) {
        return [];
    }

    $fp = fopen($path, 'r');
    if (!$fp) {
        return [];
    }

    flock($fp, LOCK_SH);
    $content = stream_get_contents($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

function storage_write($filename, $data) {
    $path = storage_path($filename);

    $fp = fopen($path, 'c+');
    if (!$fp) {
        return false;
    }

    flock($fp, LOCK_EX);
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($data, JSON_PRETTY_PRINT));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    return true;
}
