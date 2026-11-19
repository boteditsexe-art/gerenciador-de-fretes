
<?php
require_once __DIR__ . "/auth.php";

$erro = "";

if (ehAdministrador()) {
    header("Location: produtos.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $usuario = trim($_POST["usuario"] ?? "");
    $senha = $_POST["senha"] ?? "";

    // Arquivo privado de configuracao do administrador
    
$arquivo = __DIR__ . "/privado/admin.json";


    if (is_file($arquivo)) {

        $admin = json_decode(
            file_get_contents($arquivo),
            true
        );

        if (
            is_array($admin) &&
            isset($admin["usuario"], $admin["senha_hash"]) &&
            hash_equals($admin["usuario"], $usuario) &&
            password_verify($senha, $admin["senha_hash"])
        ) {
            session_regenerate_id(true);

            $_SESSION["administrador"] = true;

            header("Location: produtos.php");
            exit;
        }
    }

    $erro = "Usuário ou senha incorretos.";
}

function exibir($texto) {
    return htmlspecialchars(
        (string)$texto,
        ENT_QUOTES,
        "UTF-8"
    );
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Login do Administrador</title>

    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      rel="stylesheet">

    <style>
        body {
            background: #17191d;
            color: #ffffff;
            min-height: 100vh;
        }

        .login-card {
            background: #202227;
            border: 1px solid #44464c;
            border-radius: 16px;
            max-width: 430px;
            margin: 90px auto;
            padding: 35px;
        }

        h2 {
            color: #d5a43b;
        }

        .form-label {
            color: #ffffff;
        }

        .form-control {
            background: #292b30;
            border: 1px solid #55575d;
            color: #ffffff;
        }

        .form-control:focus {
            background: #292b30;
            color: #ffffff;
            border-color: #d5a43b;
            box-shadow: 0 0 0 3px #d5a43b33;
        }

        .btn-dourado {
            background: #d5a43b;
            color: #17191d;
            font-weight: bold;
            border: none;
        }

        .btn-dourado:hover {
            background: #edbd54;
            color: #17191d;
        }

        .voltar {
            color: #d5a43b;
            text-decoration: none;
        }
    </style>
</head>

<body>
<div class="container">

    <div class="login-card">

        <h2 class="text-center mb-4">
            Administrador
        </h2>

        <?php if ($erro !== ""): ?>
            <div class="alert alert-danger">
                <?= exibir($erro) ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <label class="form-label">
                Usuário
            </label>

            <input
                type="text"
                name="usuario"
                class="form-control mb-3"
                autocomplete="username"
                required>

            <label class="form-label">
                Senha
            </label>

            <input
                type="password"
                name="senha"
                class="form-control mb-4"
                autocomplete="current-password"
                required>

            <button
                type="submit"
                class="btn btn-dourado w-100">
                Entrar como Administrador
            </button>

        </form>

        <div class="text-center mt-4">
            <a href="index.php" class="voltar">
                Voltar ao Menu Principal
            </a>
        </div>

    </div>

</div>
</body>
</html>
