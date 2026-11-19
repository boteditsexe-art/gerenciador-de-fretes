
<?php
require_once __DIR__ . "/auth.php";
// GERENCIADOR DE FRETES - UNIPAR

function carregar($nome) {
    $caminho = __DIR__ . "/" . $nome;

    if (!file_exists($caminho)) {
        return [];
    }

    $dados = json_decode(
        file_get_contents($caminho),
        true
    );

    return is_array($dados) && array_is_list($dados) ? $dados : [];
}

function mostrar($valor) {
    return htmlspecialchars(
        (string)$valor,
        ENT_QUOTES,
        "UTF-8"
    );
}

function buscar($lista, $id) {
    foreach ($lista as $item) {
        if (($item["id"] ?? "") === $id) {
            return $item;
        }
    }

    return null;
}

// Montar endereco completo
function enderecoCompleto($e) {
    if (!$e) {
        return "";
    }

    return
        $e["rua"] .
        ", nº " . $e["numero"] .
        " - " . $e["bairro"] .
        " - " . $e["cidade"] .
        "/" . $e["estado"] .
        (!empty($e["cep"])
            ? " - CEP " . $e["cep"]
            : "");
}

$produtos = carregar("produtos.json");
$enderecos = carregar("enderecos.json");
$transportadores = carregar("transportadores.json");
$fretes = carregar("fretes.json");

$mensagem = "";
$erro = "";

// Evitar sobrescrever cadastros quando o JSON de fretes estiver danificado
$arquivoFretes = __DIR__ . "/fretes.json";
$fretesValidos = true;
if (file_exists($arquivoFretes)) {
    $conteudoFretes = file_get_contents($arquivoFretes);
    $dadosFretes = json_decode($conteudoFretes, true);
    if (!is_array($dadosFretes) || !array_is_list($dadosFretes)) {
        $fretesValidos = false;
        $erro = "O arquivo fretes.json não pôde ser lido. Alterações bloqueadas para preservar os dados.";
    }
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

function salvarFretes($arquivo, $lista) {
    $json = json_encode($lista, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return $json !== false && file_put_contents($arquivo, $json, LOCK_EX) !== false;
}

if (isset($_GET["salvo"])) {
    $mensagem = "Frete cadastrado com sucesso!";
}
if (isset($_GET["excluido"])) {
    $mensagem = "Frete excluído com sucesso!";
}

// Solicitações de alteração precisam de token válido
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";
    if (!is_string($token) || !hash_equals($_SESSION["csrf_token"], $token)) {
        http_response_code(403);
        exit("Solicitação não autorizada.");
    }
    if (($_POST["acao"] ?? "salvar") === "excluir") {
        exigirAdministrador();
    }
    if (!$fretesValidos) {
        http_response_code(500);
        exit("Arquivo fretes.json inválido. Operação cancelada.");
    }
}

// EXCLUIR FRETE SOMENTE COMO ADMINISTRADOR
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["acao"] ?? "") === "excluir") {
    $idExcluir = $_POST["id"] ?? "";
    if (!is_string($idExcluir) || $idExcluir === "") {
        $erro = "Frete não encontrado.";
    } else {
        $novaLista = [];
        $encontrou = false;
        foreach ($fretes as $f) {
            if (($f["id"] ?? "") === $idExcluir) {
                $encontrou = true;
            } else {
                $novaLista[] = $f;
            }
        }
        if (!$encontrou) {
            $erro = "Frete não encontrado.";
        } elseif (salvarFretes($arquivoFretes, $novaLista)) {
            header("Location: fretes.php?excluido=1");
            exit;
        } else {
            $erro = "Não foi possível excluir o frete.";
        }
    }
}

// CADASTRAR NOVO FRETE
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["acao"] ?? "salvar") === "salvar") {

    $transportadorId = $_POST["transportador"] ?? "";
    $origemId = $_POST["origem"] ?? "";
    $destinoId = $_POST["destino"] ?? "";
    $data = $_POST["data"] ?? "";

    $idsProdutos = $_POST["produto"] ?? [];
    $quantidades = $_POST["quantidade"] ?? [];

    $transportador = buscar(
        $transportadores,
        $transportadorId
    );

    $origem = buscar($enderecos, $origemId);
    $destino = buscar($enderecos, $destinoId);

    $dataObj = DateTime::createFromFormat(
        "!Y-m-d",
        $data
    );

    if (
        !$transportador ||
        !$origem ||
        !$destino ||
        $origemId === $destinoId ||
        ($origem["tipo"] ?? "") !== "Origem" ||
        ($destino["tipo"] ?? "") !== "Destino" ||
        !$dataObj ||
        $dataObj->format("Y-m-d") !== $data
    ) {
        $erro = "Confira o transportador, os endereços e a data.";
    } elseif (
        !is_array($idsProdutos) ||
        !is_array($quantidades) ||
        count($idsProdutos) === 0 ||
        count($idsProdutos) !== count($quantidades)
    ) {
        $erro = "Adicione pelo menos um produto.";
    } else {

        $itens = [];
        $pesoTotal = 0;
        $agrupados = [];

        foreach ($idsProdutos as $indice => $produtoId) {

            $produto = buscar($produtos, $produtoId);

            $quantidade = filter_var(
                $quantidades[$indice],
                FILTER_VALIDATE_INT
            );

            if (
                !$produto ||
                $quantidade === false ||
                $quantidade <= 0
            ) {
                $erro = "Confira os produtos e as quantidades.";
                break;
            }

            if (!isset($agrupados[$produtoId])) {
                $agrupados[$produtoId] = 0;
            }

            $agrupados[$produtoId] += $quantidade;
        }

        if ($erro === "") {

            foreach ($agrupados as $produtoId => $quantidade) {

                $produto = buscar($produtos, $produtoId);

                if (
                    $quantidade >
                    (int)($produto["quantidade"] ?? 0)
                ) {
                    $erro = "Quantidade maior que a cadastrada para: "
                          . $produto["nome"];
                    break;
                }

                $peso = $quantidade *
                    (float)$produto["peso"];

                $pesoTotal += $peso;

                $itens[] = [
                    "produto_id" => $produtoId,
                    "nome" => $produto["nome"],
                    "quantidade" => $quantidade,
                    "peso_unitario" => $produto["peso"],
                    "peso_total" => $peso
                ];
            }
        }

        if (
            $erro === "" &&
            $pesoTotal > (float)$transportador["capacidade"]
        ) {
            $erro = "O peso da carga ultrapassa a capacidade do veículo.";
        }

        if ($erro === "") {

            $fretes[] = [
                "id" => uniqid("frete_"),
                "data" => $data,
                "transportador_id" => $transportadorId,
                "transportador" => $transportador["nome"],

                "origem_id" => $origemId,
                "origem" => $origem["cidade"],
                "origem_completa" => enderecoCompleto($origem),

                "destino_id" => $destinoId,
                "destino" => $destino["cidade"],
                "destino_completo" => enderecoCompleto($destino),

                "produtos" => $itens,
                "peso_total" => $pesoTotal
            ];

            $salvou = salvarFretes($arquivoFretes, $fretes);

            if ($salvou) {
                header("Location: fretes.php?salvo=1");
                exit;
            }

            $erro = "Não foi possível salvar o frete.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Cadastro de Fretes</title>

    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      rel="stylesheet">

<link rel="stylesheet" href="tema_preto_dourado.css">

</head>

<body class="bg-light">

<div class="container mt-5 mb-5">

    <h1 class="text-primary mb-4">
        Cadastro de Fretes
    </h1>

    <div class="mb-3">
        <?php if (ehAdministrador()): ?>
            <span class="badge bg-success">Administrador conectado</span>
            <a href="logout.php" class="btn btn-outline-warning btn-sm ms-2">Sair do Administrador</a>
        <?php else: ?>
            <a href="login.php" class="btn btn-outline-warning btn-sm">Acesso do Administrador</a>
        <?php endif; ?>
    </div>

    <?php if ($mensagem): ?>
        <div class="alert alert-success">
            <?= mostrar($mensagem) ?>
        </div>
    <?php endif; ?>

    <?php if ($erro): ?>
        <div class="alert alert-danger">
            <?= mostrar($erro) ?>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-body">

            <h4 class="mb-3">Novo Frete</h4>

            <form method="POST">

                <input type="hidden" name="acao" value="salvar">
                <input type="hidden" name="csrf_token" value="<?= mostrar($_SESSION["csrf_token"]) ?>">

                <label class="form-label">
                    Transportador
                </label>

                <select name="transportador"
                        class="form-select mb-3"
                        required>

                    <option value="">Selecione</option>

                    <?php foreach ($transportadores as $t): ?>
                        <option value="<?= mostrar($t["id"]) ?>">
                            <?= mostrar($t["nome"]) ?>
                            -
                            <?= mostrar($t["veiculo"]) ?>
                        </option>
                    <?php endforeach; ?>

                </select>

                <label class="form-label">
                    Endereço de origem
                </label>

                <select name="origem"
                        class="form-select mb-3"
                        required>

                    <option value="">Selecione</option>

                    <?php foreach ($enderecos as $e): ?>
                        <?php if ($e["tipo"] === "Origem"): ?>
                            <option value="<?= mostrar($e["id"]) ?>">
                                <?= mostrar(enderecoCompleto($e)) ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>

                </select>

                <label class="form-label">
                    Endereço de destino
                </label>

                <select name="destino"
                        class="form-select mb-3"
                        required>

                    <option value="">Selecione</option>

                    <?php foreach ($enderecos as $e): ?>
                        <?php if ($e["tipo"] === "Destino"): ?>
                            <option value="<?= mostrar($e["id"]) ?>">
                                <?= mostrar(enderecoCompleto($e)) ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>

                </select>

                <label class="form-label">
                    Data do frete
                </label>

                <input type="date"
                       name="data"
                       value="<?= date("Y-m-d") ?>"
                       class="form-control mb-3"
                       required>

                <hr>

                <h4>Produtos do Frete</h4>

                <div id="listaProdutos">

                    <div class="row g-2 mb-3 produto-item">

                        <div class="col-md-7">

                            <label class="form-label">
                                Produto
                            </label>

                            <select name="produto[]"
                                    class="form-select"
                                    required>

                                <option value="">
                                    Selecione o produto
                                </option>

                                <?php foreach ($produtos as $p): ?>
                                    <option value="<?= mostrar($p["id"]) ?>">
                                        <?= mostrar($p["nome"]) ?>
                                    </option>
                                <?php endforeach; ?>

                            </select>

                        </div>

                        <div class="col-md-3">

                            <label class="form-label">
                                Quantidade
                            </label>

                            <input type="number"
                                   name="quantidade[]"
                                   min="1"
                                   value="1"
                                   class="form-control"
                                   required>

                        </div>

                        <div class="col-md-2">

                            <label class="form-label">
                                Ação
                            </label>

                            <button type="button"
                                    class="btn btn-danger w-100"
                                    onclick="removerProduto(this)">
                                Remover
                            </button>

                        </div>

                    </div>
                </div>

                <button type="button"
                        class="btn btn-primary mb-3"
                        onclick="adicionarProduto()">
                    + Adicionar Produto
                </button>

                <br>

                <button type="submit"
                        class="btn btn-success">
                    Salvar Frete
                </button>

                <a href="index.php"
                   class="btn btn-secondary">
                    Voltar ao Menu
                </a>

            </form>

        </div>
    </div>

    <h2>Fretes Cadastrados</h2>

    <?php if (empty($fretes)): ?>

        <p>Nenhum frete cadastrado.</p>

    <?php else: ?>

        <div class="table-responsive">

            <table class="table table-striped">

                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Transportador</th>
                        <th>Origem</th>
                        <th>Destino</th>
                        <th>Produtos</th>
                        <th>Peso total</th>
                        <th>Ações</th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach ($fretes as $f): ?>

                    <tr>

                        <td>
                            <?= mostrar(
                                date("d/m/Y", strtotime($f["data"]))
                            ) ?>
                        </td>

                        <td>
                            <?= mostrar($f["transportador"]) ?>
                        </td>

                        <td>
                            <?php
                            $origemExibir =
                                $f["origem_completa"]
                                ?? enderecoCompleto(
                                    buscar(
                                        $enderecos,
                                        $f["origem_id"] ?? ""
                                    )
                                );

                            echo mostrar(
                                $origemExibir !== ""
                                    ? $origemExibir
                                    : ($f["origem"] ?? "")
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            $destinoExibir =
                                $f["destino_completo"]
                                ?? enderecoCompleto(
                                    buscar(
                                        $enderecos,
                                        $f["destino_id"] ?? ""
                                    )
                                );

                            echo mostrar(
                                $destinoExibir !== ""
                                    ? $destinoExibir
                                    : ($f["destino"] ?? "")
                            );
                            ?>
                        </td>

                        <td>
                            <?php foreach ($f["produtos"] as $item): ?>
                                <div>
                                    <?= mostrar($item["nome"]) ?>
                                    -
                                    <?= mostrar($item["quantidade"]) ?>
                                    un.
                                </div>
                            <?php endforeach; ?>
                        </td>

                        <td>
                            <?= mostrar($f["peso_total"]) ?> kg
                        </td>

                        <td>
                            <?php if (ehAdministrador()): ?>
                                <form method="POST" action="fretes.php"
                                      onsubmit="return confirm('Tem certeza que deseja excluir este frete? O registro também deixará de aparecer nos relatórios.');">
                                    <input type="hidden" name="acao" value="excluir">
                                    <input type="hidden" name="csrf_token" value="<?= mostrar($_SESSION["csrf_token"]) ?>">
                                    <input type="hidden" name="id" value="<?= mostrar($f["id"] ?? "") ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Excluir</button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</div>

<script>
function adicionarProduto() {

    const lista = document.getElementById("listaProdutos");
    const original = lista.querySelector(".produto-item");
    const novo = original.cloneNode(true);

    novo.querySelector("select").value = "";

    novo.querySelector(
        'input[name="quantidade[]"]'
    ).value = "1";

    lista.appendChild(novo);
}

function removerProduto(botao) {

    const lista = document.getElementById("listaProdutos");

    const itens = lista.querySelectorAll(
        ".produto-item"
    );

    if (itens.length > 1) {
        botao.closest(".produto-item").remove();
    } else {
        alert(
            "O frete precisa ter pelo menos um produto."
        );
    }
}
</script>

</body>
</html>
