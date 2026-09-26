<?php
// config.php
//
// Credenciais e catálogo de pacotes. Este arquivo é incluído pelos outros
// scripts PHP — ele nunca deve ser acessado diretamente pelo navegador
// (por isso a pasta includes/ e storage/ têm um .htaccess bloqueando acesso
// direto; mantenha config.php fora de uma pasta pública se seu provedor
// permitir isso).

// ─── CONFIGURE AQUI ───────────────────────────────────────────
define('SYNCPAY_CLIENT_ID', '2e5daa15-df51-478e-a39d-17532d4941db');
define('SYNCPAY_CLIENT_SECRET', '6c115f95-ed70-4b26-a245-a8decf39466d');
define('SYNCPAY_BASE_URL', 'https://api.syncpayments.com.br');

// URL pública do seu webhook-pix.php, para receber a confirmação de
// pagamento em tempo real. Ajuste para o seu domínio real.
define('WEBHOOK_URL', 'https://seudominio.com/webhook-pix.php');

// Tempo (em minutos) mostrado no cronômetro do checkout. A SyncPay não
// retorna prazo de expiração no cash-in, então esse valor é só de UX.
define('PIX_EXPIRATION_MINUTES', 15);
// ──────────────────────────────────────────────────────────────

// Preço de cada pacote fica SÓ AQUI, no servidor. O front-end (checkout.html)
// manda apenas o "id" do pacote (ex.: ?plano=bronze) — nunca o valor em
// reais. Assim, mesmo que alguém edite a URL, o valor cobrado é sempre este.
$PLANS = [
    'bronze' => [
        'id' => 'bronze',
        'name' => 'Pacote Bronze',
        'amount' => 16.90,
        'description' => 'Pacote Bronze',
    ],
    'prata' => [
        'id' => 'prata',
        'name' => 'Pacote Prata',
        'amount' => 21.90,
        'description' => 'Pacote Prata',
    ],
    'ouro' => [
        'id' => 'ouro',
        'name' => 'Pacote Ouro',
        'amount' => 29.90,
        'description' => 'Pacote Ouro',
    ],
];
