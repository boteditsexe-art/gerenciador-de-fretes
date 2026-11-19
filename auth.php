
<?php
// GERENCIADOR DE FRETES
// Controle de acesso do administrador

if (session_status() === PHP_SESSION_NONE) {

    ini_set("session.use_strict_mode", "1");
    ini_set("session.use_only_cookies", "1");

    ini_set("session.cookie_httponly", "1");
    ini_set("session.cookie_samesite", "Lax");

    ini_set(
        "session.cookie_secure",
        !empty($_SERVER["HTTPS"]) &&
        $_SERVER["HTTPS"] !== "off" ? "1" : "0"
    );

    session_start();
}

// Verifica se o usuario e administrador
function ehAdministrador() {
    return isset($_SESSION["administrador"])
        && $_SESSION["administrador"] === true;
}

// Bloqueia operacoes exclusivas do administrador
function exigirAdministrador() {

    if (!ehAdministrador()) {

        http_response_code(403);

        exit(
            "Acesso negado. Somente o administrador " .
            "pode realizar esta operacao."
        );
    }
}
