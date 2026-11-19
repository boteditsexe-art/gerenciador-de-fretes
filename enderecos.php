
<?php
// GERENCIADOR DE FRETES
// CADASTRO DE ENDERECOS
// EXCLUSAO SOMENTE PARA ADMINISTRADOR

require_once __DIR__ . "/auth.php";

$arquivo = __DIR__ . "/enderecos.json";
$enderecos = [];
$mensagem = "";
$erro = "";
$editar = null;
$dadosValidos = true;

// Carregar enderecos ja cadastrados
if (file_exists($arquivo)) {
    $conteudo = file_get_contents($arquivo);
    $dados = json_decode($conteudo, true);

    if (is_array($dados) && array_is_list($dados)) {
        $enderecos = $dados;
    } else {
        $dadosValidos = false;
        $erro = "Erro ao ler enderecos.json. Nenhuma alteracao sera salva ate corrigir o arquivo.";
    }
}

// Proteger textos na tela
function mostrar($valor) {
    return htmlspecialchars(
        (string)$valor,
        ENT_QUOTES,
        "UTF-8"
    );
}

// Criar token de seguranca
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Salvar enderecos no JSON
function salvarEnderecos($arquivo, $enderecos) {
    $json = json_encode(
        $enderecos,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    );

    if ($json === false) {
        return false;
    }

    return file_put_contents(
        $arquivo,
        $json,
        LOCK_EX
    ) !== false;
}

// Processar formularios
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        http_response_code(403);
        exit("Solicitacao nao autorizada.");
    }

    $acao = $_POST["acao"] ?? "salvar";

    // BLOQUEAR EXCLUSAO PARA VISITANTES
    if ($acao === "excluir") {
        exigirAdministrador();
    }

    if (!$dadosValidos) {
        http_response_code(500);
        exit("Erro ao ler enderecos.json. Operacao cancelada.");
    }

    // EXCLUIR ENDERECO
    if ($acao === "excluir") {

        $idExcluir = $_POST["id"] ?? "";
        $novaLista = [];
        $encontrou = false;

        foreach ($enderecos as $endereco) {

            if (($endereco["id"] ?? "") === $idExcluir) {
                $encontrou = true;
            } else {
                $novaLista[] = $endereco;
            }
        }

        if (!$encontrou || $idExcluir === "") {
            $erro = "Endereco nao encontrado.";
        } elseif (salvarEnderecos($arquivo, $novaLista)) {

            header("Location: enderecos.php?excluido=1");
            exit;

        } else {
            $erro = "Erro ao excluir o endereco.";
        }

    // SALVAR OU EDITAR ENDERECO
    } elseif ($acao === "salvar") {

        $id = trim($_POST["id"] ?? "");
        $tipo = trim($_POST["tipo"] ?? "");
        $rua = trim($_POST["rua"] ?? "");
        $numero = trim($_POST["numero"] ?? "");
        $bairro = trim($_POST["bairro"] ?? "");
        $cidade = trim($_POST["cidade"] ?? "");
        $estado = strtoupper(trim($_POST["estado"] ?? ""));
        $cep = trim($_POST["cep"] ?? "");

        if (
            !in_array($tipo, ["Origem", "Destino"], true) ||
            $rua === "" ||
            $numero === "" ||
            $bairro === "" ||
            $cidade === "" ||
            !preg_match('/^[A-Z]{2}$/', $estado)
        ) {

            $erro = "Preencha os campos corretamente.";

        } else {

            $novo = [
                "id" => $id !== "" ? $id : uniqid("end_"),
                "tipo" => $tipo,
                "rua" => $rua,
                "numero" => $numero,
                "bairro" => $bairro,
                "cidade" => $cidade,
                "estado" => $estado,
                "cep" => $cep
            ];

            $encontrou = false;

            if ($id !== "") {

                // EDITAR ENDERECO EXISTENTE
                foreach ($enderecos as $chave => $endereco) {

                    if (($endereco["id"] ?? "") === $id) {
                        $enderecos[$chave] = $novo;
                        $encontrou = true;
                        break;
                    }
                }

            } else {

                // CADASTRAR NOVO ENDERECO
                $enderecos[] = $novo;
                $encontrou = true;
            }

            if (!$encontrou) {

                $erro = "Endereco nao encontrado.";

            } elseif (salvarEnderecos($arquivo, $enderecos)) {

                header("Location: enderecos.php?salvo=1");
                exit;

            } else {

                $erro = "Erro ao salvar o endereco.";
            }
        }

    } else {
        $erro = "Operacao invalida.";
    }
}

// Carregar endereco para edicao
if (isset($_GET["editar"])) {

    foreach ($enderecos as $endereco) {

        if (($endereco["id"] ?? "") === $_GET["editar"]) {
            $editar = $endereco;
            break;
        }
    }

    if ($editar === null && $erro === "") {
        $erro = "Endereco nao encontrado.";
    }
}

// Mensagens de sucesso
if (isset($_GET["salvo"])) {
    $mensagem = "Endereco salvo com sucesso!";
}

if (isset($_GET["excluido"])) {
    $mensagem = "Endereco excluido com sucesso!";
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Cadastro de Endereços</title>

    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      rel="stylesheet">

    <link rel="stylesheet" href="tema_preto_dourado.css">
</head>

<body class="bg-light">

<div class="container mt-5 mb-5">

    <h1 class="text-primary mb-4">
        Cadastro de Endereços
    </h1>

    <!-- ACESSO ADMINISTRATIVO -->
    <div class="mb-3">

        <?php if (ehAdministrador()): ?>

            <span class="badge bg-success">
                Administrador conectado
            </span>

            <a href="logout.php"
               class="btn btn-outline-warning btn-sm ms-2">
                Sair do Administrador
            </a>

        <?php else: ?>

            <a href="login.php"
               class="btn btn-outline-warning btn-sm">
                Acesso do Administrador
            </a>

        <?php endif; ?>

    </div>

    <!-- MENSAGENS -->
    <?php if ($mensagem !== ""): ?>
        <div class="alert alert-success">
            <?= mostrar($mensagem) ?>
        </div>
    <?php endif; ?>

    <?php if ($erro !== ""): ?>
        <div class="alert alert-danger">
            <?= mostrar($erro) ?>
        </div>
    <?php endif; ?>

    <!-- FORMULARIO -->
    <div class="card mb-4">
        <div class="card-body">

            <h4>
                <?= $editar ? "Editar Endereço" : "Novo Endereço" ?>
            </h4>

            <form method="POST" action="enderecos.php">

                <input type="hidden"
                       name="acao"
                       value="salvar">

                <input type="hidden"
                       name="csrf_token"
                       value="<?= mostrar($_SESSION["csrf_token"]) ?>">

                <input type="hidden"
                       name="id"
                       value="<?= mostrar($editar["id"] ?? "") ?>">

                <label class="form-label">
                    Tipo de endereço
                </label>

                <select name="tipo"
                        class="form-select mb-3"
                        required>

                    <option value="">Selecione</option>

                    <?php foreach (["Origem", "Destino"] as $tipo): ?>

                        <option value="<?= mostrar($tipo) ?>"
                            <?= ($editar["tipo"] ?? "") === $tipo
                                ? "selected" : "" ?>>

                            <?= mostrar($tipo) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

                <label class="form-label">Rua</label>

                <input type="text"
                       name="rua"
                       class="form-control mb-3"
                       value="<?= mostrar($editar["rua"] ?? "") ?>"
                       required>

                <label class="form-label">Número</label>

                <input type="text"
                       name="numero"
                       class="form-control mb-3"
                       value="<?= mostrar($editar["numero"] ?? "") ?>"
                       required>

                <label class="form-label">Bairro</label>

                <input type="text"
                       name="bairro"
                       class="form-control mb-3"
                       value="<?= mostrar($editar["bairro"] ?? "") ?>"
                       required>

                <label class="form-label">Cidade</label>

                <input type="text"
                       name="cidade"
                       class="form-control mb-3"
                       value="<?= mostrar($editar["cidade"] ?? "") ?>"
                       required>

                <label class="form-label">
                    Estado (UF)
                </label>

                <input type="text"
                       name="estado"
                       maxlength="2"
                       placeholder="PR"
                       class="form-control mb-3"
                       value="<?= mostrar($editar["estado"] ?? "") ?>"
                       required>

                <label class="form-label">CEP</label>

                <input type="text"
                       name="cep"
                       placeholder="00000-000"
                       class="form-control mb-3"
                       value="<?= mostrar($editar["cep"] ?? "") ?>">

                <button type="submit"
                        class="btn btn-success">
                    <?= $editar
                        ? "Atualizar Endereço"
                        : "Salvar Endereço" ?>
                </button>

                <?php if ($editar): ?>

                    <a href="enderecos.php"
                       class="btn btn-warning">
                        Cancelar Edição
                    </a>

                <?php endif; ?>

                <a href="index.php"
                   class="btn btn-secondary">
                    Voltar ao Menu
                </a>

            </form>

        </div>
    </div>

    <!-- ENDERECOS CADASTRADOS -->
    <h2>Endereços Cadastrados</h2>

    <div class="table-responsive">

        <table class="table table-striped">

            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Rua</th>
                    <th>Número</th>
                    <th>Bairro</th>
                    <th>Cidade</th>
                    <th>UF</th>
                    <th>CEP</th>
                    <th>Ações</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($enderecos as $e): ?>

                <tr>

                    <td><?= mostrar($e["tipo"] ?? "") ?></td>
                    <td><?= mostrar($e["rua"] ?? "") ?></td>
                    <td><?= mostrar($e["numero"] ?? "") ?></td>
                    <td><?= mostrar($e["bairro"] ?? "") ?></td>
                    <td><?= mostrar($e["cidade"] ?? "") ?></td>
                    <td><?= mostrar($e["estado"] ?? "") ?></td>
                    <td><?= mostrar($e["cep"] ?? "") ?></td>

                    <td>
                        <div class="d-flex gap-2">

                            <!-- EDITAR: TODOS PODEM -->
                            <a class="btn btn-warning btn-sm"
                               href="enderecos.php?editar=<?= urlencode($e["id"]) ?>">
                                Editar
                            </a>

                            <!-- EXCLUIR: SO ADMINISTRADOR -->
                            <?php if (ehAdministrador()): ?>

                                <form method="POST"
                                      action="enderecos.php"
                                      onsubmit="return confirm('Tem certeza que deseja excluir este endereço?');">

                                    <input type="hidden"
                                           name="acao"
                                           value="excluir">

                                    <input type="hidden"
                                           name="csrf_token"
                                           value="<?= mostrar($_SESSION["csrf_token"]) ?>">

                                    <input type="hidden"
                                           name="id"
                                           value="<?= mostrar($e["id"]) ?>">

                                    <button type="submit"
                                            class="btn btn-danger btn-sm">
                                        Excluir
                                    </button>

                                </form>

                            <?php endif; ?>

                        </div>
                    </td>

                </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    </div>

</div>

</body>
</html>
