
<?php
// GERENCIADOR DE FRETES
// CADASTRO, EDICAO E EXCLUSAO DE PRODUTOS

require_once __DIR__ . "/auth.php";

$arquivo = __DIR__ . "/produtos.json";
$produtos = [];
$mensagem = "";
$erro = "";
$produtoEditar = null;

// Proteger textos exibidos na tela
function mostrar($valor) {
    return htmlspecialchars(
        (string)$valor,
        ENT_QUOTES,
        "UTF-8"
    );
}

// Carregar produtos existentes
if (file_exists($arquivo)) {
    $conteudo = file_get_contents($arquivo);
    $dados = json_decode($conteudo, true);

    if (is_array($dados)) {
        $produtos = $dados;
    } else {
        $erro = "Erro ao ler os produtos cadastrados.";
    }
}

// Criar token de seguranca
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(
        random_bytes(32)
    );
}

// Salvar lista de produtos
function salvarProdutos($arquivo, $produtos) {
    $json = json_encode(
        $produtos,
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

// Receber formularios
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals(
            $_SESSION["csrf_token"],
            $token
        )
    ) {
        http_response_code(403);
        exit("Solicitacao nao autorizada.");
    }

    $acao = $_POST["acao"] ?? "salvar";

    // EXCLUIR PRODUTO
    if ($acao === "excluir") {

        // Somente administrador pode excluir
        exigirAdministrador();

        $idExcluir = $_POST["id"] ?? "";
        $novaLista = [];
        $encontrou = false;

        foreach ($produtos as $produto) {
            if (
                ($produto["id"] ?? "") === $idExcluir
            ) {
                $encontrou = true;
            } else {
                $novaLista[] = $produto;
            }
        }

        if (!$encontrou) {
            $erro = "Produto nao encontrado.";
        } elseif (
            salvarProdutos($arquivo, $novaLista)
        ) {
            header(
                "Location: produtos.php?excluido=1"
            );
            exit;
        } else {
            $erro = "Erro ao excluir produto.";
        }

    } elseif ($acao === "salvar") {

        // CADASTRAR OU EDITAR
        // Visitantes tambem podem realizar estas acoes

        $id = trim($_POST["id"] ?? "");
        $nome = trim($_POST["nome"] ?? "");

        $quantidade = filter_input(
            INPUT_POST,
            "quantidade",
            FILTER_VALIDATE_INT
        );

        $peso = filter_input(
            INPUT_POST,
            "peso",
            FILTER_VALIDATE_FLOAT
        );

        $descricao = trim(
            $_POST["descricao"] ?? ""
        );

        if (
            $nome === "" ||
            $quantidade === false ||
            $quantidade === null ||
            $quantidade <= 0 ||
            $peso === false ||
            $peso === null ||
            !is_finite((float)$peso) ||
            $peso <= 0
        ) {
            $erro = "Preencha os campos corretamente.";
        } else {

            $encontrou = false;

            if ($id !== "") {

                // EDITAR PRODUTO
                foreach ($produtos as &$produto) {

                    if (
                        ($produto["id"] ?? "") === $id
                    ) {
                        $produto["nome"] = $nome;
                        $produto["quantidade"] = $quantidade;
                        $produto["peso"] = $peso;
                        $produto["descricao"] = $descricao;

                        $encontrou = true;
                        break;
                    }
                }

                unset($produto);

                if (!$encontrou) {
                    $erro = "Produto nao encontrado.";
                }

            } else {

                // NOVO PRODUTO
                $produtos[] = [
                    "id" => uniqid("prod_"),
                    "nome" => $nome,
                    "quantidade" => $quantidade,
                    "peso" => $peso,
                    "descricao" => $descricao
                ];
            }

            if ($erro === "") {

                if (
                    salvarProdutos($arquivo, $produtos)
                ) {
                    if ($id !== "") {
                        header(
                            "Location: produtos.php?editado=1"
                        );
                    } else {
                        header(
                            "Location: produtos.php?salvo=1"
                        );
                    }

                    exit;
                } else {
                    $erro = "Erro ao salvar produto.";
                }
            }
        }

    } else {
        $erro = "Operacao invalida.";
    }
}

// Carregar produto para edicao
if (
    $_SERVER["REQUEST_METHOD"] === "GET" &&
    isset($_GET["editar"])
) {
    $idEditar = $_GET["editar"];

    foreach ($produtos as $produto) {
        if (
            ($produto["id"] ?? "") === $idEditar
        ) {
            $produtoEditar = $produto;
            break;
        }
    }

    if ($produtoEditar === null) {
        $erro = "Produto nao encontrado.";
    }
}

// Mensagens
if (isset($_GET["salvo"])) {
    $mensagem = "Produto salvo com sucesso!";
}

if (isset($_GET["editado"])) {
    $mensagem = "Produto atualizado com sucesso!";
}

if (isset($_GET["excluido"])) {
    $mensagem = "Produto excluido com sucesso!";
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Cadastro de Produtos</title>

    <!-- Bootstrap -->
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      rel="stylesheet">

    <!-- Tema preto e dourado -->
    <link
      rel="stylesheet"
      href="tema_preto_dourado.css">
</head>

<body class="bg-light">

<div class="container mt-5 mb-5">

    <h1 class="text-primary mb-4">
        Cadastro de Produtos
    </h1>

    <!-- ACESSO ADMINISTRATIVO -->
    <div class="mb-3">

        <?php if (ehAdministrador()): ?>

            <span class="badge bg-success">
                Administrador conectado
            </span>

        <?php else: ?>

            <a href="login.php"
               class="btn btn-outline-warning btn-sm">
                Acesso do Administrador
            </a>

        <?php endif; ?>

    </div>

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

    <!-- FORMULARIO DE CADASTRO -->
    <div class="card mb-4">
        <div class="card-body">

            <h4 class="mb-3">
                <?= $produtoEditar
                    ? "Editar Produto"
                    : "Novo Produto" ?>
            </h4>

            <form method="POST"
                  action="produtos.php">

                <input type="hidden"
                       name="acao"
                       value="salvar">

                <input type="hidden"
                       name="csrf_token"
                       value="<?= mostrar(
                           $_SESSION["csrf_token"]
                       ) ?>">

                <input type="hidden"
                       name="id"
                       value="<?= mostrar(
                           $produtoEditar["id"] ?? ""
                       ) ?>">

                <label class="form-label">
                    Nome do produto
                </label>

                <input
                    type="text"
                    name="nome"
                    class="form-control mb-3"
                    value="<?= mostrar(
                        $produtoEditar["nome"] ?? ""
                    ) ?>"
                    required>

                <label class="form-label">
                    Quantidade
                </label>

                <input
                    type="number"
                    name="quantidade"
                    min="1"
                    class="form-control mb-3"
                    value="<?= mostrar(
                        $produtoEditar["quantidade"] ?? ""
                    ) ?>"
                    required>

                <label class="form-label">
                    Peso por unidade (kg)
                </label>

                <input
                    type="number"
                    name="peso"
                    min="0.01"
                    step="0.01"
                    class="form-control mb-3"
                    value="<?= mostrar(
                        $produtoEditar["peso"] ?? ""
                    ) ?>"
                    required>

                <label class="form-label">
                    Descrição
                </label>

                <textarea
                    name="descricao"
                    class="form-control mb-3"
                ><?= mostrar(
                    $produtoEditar["descricao"] ?? ""
                ) ?></textarea>

                <button
                    type="submit"
                    class="btn btn-success">

                    <?= $produtoEditar
                        ? "Atualizar Produto"
                        : "Salvar Produto" ?>

                </button>

                <?php if ($produtoEditar): ?>

                    <a href="produtos.php"
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

    <!-- PRODUTOS CADASTRADOS -->
    <h2>Produtos Cadastrados</h2>

    <?php if (empty($produtos)): ?>

        <p>Nenhum produto cadastrado.</p>

    <?php else: ?>

        <div class="table-responsive">

            <table class="table table-striped">

                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Quantidade</th>
                        <th>Peso (kg)</th>
                        <th>Descrição</th>
                        <th>Ações</th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach ($produtos as $p): ?>

                    <tr>
                        <td>
                            <?= mostrar($p["nome"] ?? "") ?>
                        </td>

                        <td>
                            <?= mostrar($p["quantidade"] ?? "") ?>
                        </td>

                        <td>
                            <?= mostrar($p["peso"] ?? "") ?>
                        </td>

                        <td>
                            <?= mostrar($p["descricao"] ?? "") ?>
                        </td>

                        <td>
                            <div class="d-flex gap-2">

                                <!-- EDITAR: TODOS -->
                                <a
                                    href="produtos.php?editar=<?= urlencode($p["id"]) ?>"
                                    class="btn btn-warning btn-sm">
                                    Editar
                                </a>

                                <!-- EXCLUIR: SO ADMIN -->
                                <?php if (ehAdministrador()): ?>

                                    <form
                                        method="POST"
                                        action="produtos.php"
                                        onsubmit="return confirm('Tem certeza que deseja excluir este produto?');">

                                        <input
                                            type="hidden"
                                            name="acao"
                                            value="excluir">

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= mostrar(
                                                $_SESSION["csrf_token"]
                                            ) ?>">

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= mostrar(
                                                $p["id"]
                                            ) ?>">

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

    <?php endif; ?>

</div>

</body>
</html>
