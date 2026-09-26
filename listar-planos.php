<?php
// listar-planos.php
//
// Fonte única dos pacotes exibidos em planos.html — assim o preço mostrado
// no card é garantidamente o mesmo que o gerar-pix.php vai cobrar.

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/config.php';

echo json_encode(['plans' => array_values($PLANS)]);
