
<?php
require_once __DIR__ . "/auth.php";

// Apagar os dados da sessão administrativa
$_SESSION = [];

// Apagar o cookie da sessão
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();

    setcookie(session_name(), "", [
        "expires" => time() - 3600,
        "path" => $params["path"],
        "domain" => $params["domain"],
        "secure" => $params["secure"],
        "httponly" => $params["httponly"],
        "samesite" => $params["samesite"] ?? "Lax"
    ]);
}

// Encerrar a sessão
session_destroy();

// Voltar à tela de Produtos
header("Location: produtos.php");
exit;
