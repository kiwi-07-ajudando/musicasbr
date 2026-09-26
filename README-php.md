# Checkout Pix — SyncPay (versão PHP)

Versão do checkout em PHP puro (sem Node, sem Composer), pensada para
hospedagem compartilhada comum (cPanel, Hostinger, InfinityFree etc.) —
inclusive planos gratuitos.

## Arquivos

```
config.php            → suas credenciais da SyncPay + preço dos 3 pacotes
includes/syncpay.php   → fala com a API da SyncPay (auth, criar pix, status)
includes/storage.php   → guarda token e status em arquivos JSON (sem precisar de banco)
gerar-pix.php          → cria a cobrança Pix do pacote escolhido
consultar-status.php   → usado pelo checkout para saber se já foi pago
listar-planos.php      → devolve os 3 pacotes para a página de seleção
webhook-pix.php        → recebe a confirmação de pagamento da SyncPay
planos.html            → página "escolha seu pacote"
checkout.html          → página de pagamento (QR Code + copia e cola + cronômetro)
storage/               → onde os arquivos JSON de cache/status são salvos
```

## Como instalar

1. **Edite `config.php`** e preencha:
   - `SYNCPAY_CLIENT_ID` e `SYNCPAY_CLIENT_SECRET` — gerados no painel da SyncPay.
   - `WEBHOOK_URL` — o endereço público de `webhook-pix.php` depois de
     publicado (ex.: `https://seudominio.com/webhook-pix.php`).
   - Os 3 pacotes no array `$PLANS`, se quiser mudar nome/preço.

2. **Envie todos os arquivos** desta pasta (mantendo a estrutura, incluindo
   `includes/` e `storage/`) para dentro da pasta pública do seu site — no
   cPanel, geralmente `public_html/` ou uma subpasta dela, via FTP ou o
   "Gerenciador de Arquivos".

3. **Dê permissão de escrita na pasta `storage/`** (ela precisa salvar o
   token de acesso e o status das transações). No Gerenciador de Arquivos
   do cPanel: clique com o botão direito na pasta → Permissões → `755`
   (ou `775`, se `755` não funcionar no seu provedor).

4. Acesse:
   ```
   https://seudominio.com/planos.html
   ```
   Escolha um pacote → você cai em `checkout.html?plano=bronze` (ou
   `prata`/`ouro`) já com o Pix gerado.

## Por que o preço nunca vem da URL

`checkout.html?plano=bronze` leva só o **nome** do pacote na URL. Quem decide
quanto cobrar é sempre o `gerar-pix.php`, olhando o array `$PLANS` dentro de
`config.php` no servidor. Se o preço viesse direto da URL (`?amount=1.00`),
qualquer pessoa poderia editar o link e pagar menos do que deveria — por
isso essa parte é proposital e não deve ser "simplificada".

## Sobre o QR Code

Como hospedagem PHP grátis nem sempre tem Composer ou extensões de imagem
liberadas, o QR Code é gerado por um serviço público
(`api.qrserver.com`), a partir do próprio "pix copia e cola" — que já é um
código feito para ser público/escaneado, então não há problema em passá-lo
nessa URL. Se preferir gerar o QR Code localmente (sem depender de terceiros),
dá para trocar por uma biblioteca como
[`chillerlan/php-qrcode`](https://github.com/chillerlan/php-qrcode) via
Composer, caso sua hospedagem permita.

## Sobre o cronômetro

A SyncPay não devolve prazo de expiração na criação do Pix — só `pix_code` e
`identifier`. O cronômetro do checkout é só uma referência de UX (padrão
15 minutos, ajustável em `PIX_EXPIRATION_MINUTES` no `config.php`). Quando
zera, a página para de consultar o status e oferece gerar um novo Pix.

## Testando localmente antes de subir (opcional)

Se tiver PHP instalado na sua máquina:
```bash
php -S localhost:8000
```
E acesse `http://localhost:8000/planos.html`.

## Próximos passos sugeridos

- Trocar os arquivos JSON em `storage/` por uma tabela MySQL, caso o volume
  de vendas cresça (a maioria dos provedores de hospedagem PHP já inclui
  MySQL grátis).
- Validar a assinatura do webhook da SyncPay antes de confiar no payload
  recebido em `webhook-pix.php` (veja a seção "Webhooks" da documentação:
  rotação de segredo de assinatura).
- Registrar o pedido (produto, cliente, valor) no banco de dados ao criar a
  cobrança, e não apenas em `storage/transactions.json`.
