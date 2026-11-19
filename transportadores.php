
<?php
// GERENCIADOR DE FRETES
// CADASTRO DE TRANSPORTADORES
// EXCLUSAO SOMENTE PELO ADMINISTRADOR

require_once __DIR__ . "/auth.php";

$arquivo = __DIR__ . "/transportadores.json";
$transportadores = [];
$mensagem = "";
$erro = "";
$editar = null;
$dadosValidos = true;

// Carregar cadastros existentes
if (file_exists($arquivo)) {
    $dados = json_decode(
        file_get_contents($arquivo),
        true
    );

    if (is_array($dados) && array_is_list($dados)) {
        $transportadores = $dados;
    } else {
        $dadosValidos = false;
        $erro = "Erro ao ler os transportadores cadastrados.";
    }
}

function mostrar($valor) {
    return htmlspecialchars(
        (string)$valor,
        ENT_QUOTES,
        "UTF-8"
    );
}

// Token contra solicitacoes falsas
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

function salvarTransportadores($arquivo, $lista) {
    $json = json_encode(
        $lista,
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

// Processar cadastro, edicao e exclusao
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

    // Visitantes nao podem excluir
    if ($acao === "excluir") {
        exigirAdministrador();
    }

    if (!$dadosValidos) {
        http_response_code(500);
        exit("Arquivo de dados invalido. Operacao cancelada.");
    }

    if ($acao === "excluir") {

        $idExcluir = $_POST["id"] ?? "";
        $novaLista = [];
        $encontrou = false;

        foreach ($transportadores as $t) {
            if (($t["id"] ?? "") === $idExcluir) {
                $encontrou = true;
            } else {
                $novaLista[] = $t;
            }
        }

        if (!$encontrou || $idExcluir === "") {
            $erro = "Transportador nao encontrado.";
        } elseif (
            salvarTransportadores($arquivo, $novaLista)
        ) {
            header(
                "Location: transportadores.php?excluido=1"
            );
            exit;
        } else {
            $erro = "Erro ao excluir transportador.";
        }

    } elseif ($acao === "salvar") {

        $id = trim($_POST["id"] ?? "");
        $nome = trim($_POST["nome"] ?? "");
        $documento = trim($_POST["documento"] ?? "");
        $telefone = trim($_POST["telefone"] ?? "");
        $veiculo = trim($_POST["veiculo"] ?? "");
        $placa = strtoupper(trim($_POST["placa"] ?? ""));

        $capacidade = filter_input(
            INPUT_POST,
            "capacidade",
            FILTER_VALIDATE_FLOAT
        );

        $tipos = [
            "Caminhão",
            "Caminhonete",
            "Carreta",
            "Van",
            "Outro"
        ];

        if (
            $nome === "" ||
            $documento === "" ||
            $telefone === "" ||
            !in_array($veiculo, $tipos, true) ||
            $placa === "" ||
            $capacidade === false ||
            $capacidade === null ||
            !is_finite((float)$capacidade) ||
            $capacidade <= 0
        ) {
            $erro = "Preencha todos os campos corretamente.";
        } else {

            $novo = [
                "id" => $id !== ""
                    ? $id
                    : uniqid("trans_"),
                "nome" => $nome,
                "documento" => $documento,
                "telefone" => $telefone,
                "veiculo" => $veiculo,
                "placa" => $placa,
                "capacidade" => $capacidade
            ];

            $encontrou = false;

            if ($id !== "") {
                foreach ($transportadores as $chave => $t) {
                    if (($t["id"] ?? "") === $id) {
                        $transportadores[$chave] = $novo;
                        $encontrou = true;
                        break;
                    }
                }
            } else {
                $transportadores[] = $novo;
                $encontrou = true;
            }

            if (!$encontrou) {
                $erro = "Transportador nao encontrado.";
            } elseif (
                salvarTransportadores(
                    $arquivo,
                    $transportadores
                )
            ) {
                header(
                    "Location: transportadores.php?salvo=1"
                );
                exit;
            } else {
                $erro = "Erro ao salvar transportador.";
            }
        }

    } else {
        $erro = "Operacao invalida.";
    }
}

// Carregar cadastro para edicao
if (isset($_GET["editar"])) {
    foreach ($transportadores as $t) {
        if (($t["id"] ?? "") === $_GET["editar"]) {
            $editar = $t;
            break;
        }
    }

    if ($editar === null && $erro === "") {
        $erro = "Transportador nao encontrado.";
    }
}

if (isset($_GET["salvo"])) {
    $mensagem = "Transportador salvo com sucesso!";
}

if (isset($_GET["excluido"])) {
    $mensagem = "Transportador excluido com sucesso!";
}

$tipos = [
    "Caminhão",
    "Caminhonete",
    "Carreta",
    "Van",
    "Outro"
];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Cadastro de Transportadores</title>

    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      rel="stylesheet">

    <link rel="stylesheet"
          href="tema_preto_dourado.css">
</head>

<body class="bg-light">

<div class="container mt-5 mb-5">

    <h1 class="text-primary mb-4">
        Cadastro de Transportadores
    </h1>

    <!-- IDENTIFICACAO DO ADMINISTRADOR -->
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
                <?= $editar
                    ? "Editar Transportador"
                    : "Novo Transportador" ?>
            </h4>

            <form method="POST"
                  action="transportadores.php">

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
                    Nome do transportador
                </label>

                <input type="text"
                       name="nome"
                       class="form-control mb-3"
                       value="<?= mostrar($editar["nome"] ?? "") ?>"
                       required>

                <label class="form-label">
                    CPF ou CNPJ
                </label>

                <input type="text"
                       name="documento"
                       class="form-control mb-3"
                       value="<?= mostrar($editar["documento"] ?? "") ?>"
                       required>

                <label class="form-label">
                    Telefone
                </label>

                <input type="text"
                       name="telefone"
                       class="form-control mb-3"
                       value="<?= mostrar($editar["telefone"] ?? "") ?>"
                       required>

                <label class="form-label">
                    Tipo de veículo
                </label>

                <select name="veiculo"
                        class="form-select mb-3"
                        required>

                    <option value="">Selecione</option>

                    <?php foreach ($tipos as $tipo): ?>

                        <option
                            value="<?= mostrar($tipo) ?>"
                            <?= ($editar["veiculo"] ?? "") === $tipo
                                ? "selected"
                                : "" ?>>

                            <?= mostrar($tipo) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

                <label class="form-label">
                    Placa do veículo
                </label>

                <input type="text"
                       name="placa"
                       class="form-control mb-3"
                       value="<?= mostrar($editar["placa"] ?? "") ?>"
                       required>

                <label class="form-label">
                    Capacidade de carga (kg)
                </label>

                <input type="number"
                       name="capacidade"
                       min="0.01"
                       step="0.01"
                       class="form-control mb-3"
                       value="<?= mostrar($editar["capacidade"] ?? "") ?>"
                       required>

                <button type="submit"
                        class="btn btn-success">

                    <?= $editar
                        ? "Atualizar Transportador"
                        : "Salvar Transportador" ?>

                </button>

                <?php if ($editar): ?>

                    <a href="transportadores.php"
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

    <!-- TABELA -->
    <h2>Transportadores Cadastrados</h2>

    <div class="table-responsive">

        <table class="table table-striped">

            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Documento</th>
                    <th>Telefone</th>
                    <th>Veículo</th>
                    <th>Placa</th>
                    <th>Capacidade</th>
                    <th>Ações</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($transportadores as $t): ?>

                <tr>

                    <td><?= mostrar($t["nome"] ?? "") ?></td>
                    <td><?= mostrar($t["documento"] ?? "") ?></td>
                    <td><?= mostrar($t["telefone"] ?? "") ?></td>
                    <td><?= mostrar($t["veiculo"] ?? "") ?></td>
                    <td><?= mostrar($t["placa"] ?? "") ?></td>

                    <td>
                        <?= mostrar($t["capacidade"] ?? "") ?> kg
                    </td>

                    <td>

                        <div class="d-flex gap-2">

                            <!-- TODOS PODEM EDITAR -->
                            <a
                              href="transportadores.php?editar=<?= urlencode($t["id"]) ?>"
                              class="btn btn-warning btn-sm">

                                Editar

                            </a>

                            <!-- SOMENTE ADMIN PODE EXCLUIR -->
                            <?php if (ehAdministrador()): ?>

                                <form
                                  method="POST"
                                  action="transportadores.php"
                                  onsubmit="return confirm('Tem certeza que deseja excluir este transportador?');">

                                    <input type="hidden"
                                           name="acao"
                                           value="excluir">

                                    <input type="hidden"
                                           name="csrf_token"
                                           value="<?= mostrar($_SESSION["csrf_token"]) ?>">

                                    <input type="hidden"
                                           name="id"
                                           value="<?= mostrar($t["id"]) ?>">

                                    <button
                                      type="submit"
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
