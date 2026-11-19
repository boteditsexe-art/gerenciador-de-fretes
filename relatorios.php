
<?php
// GERENCIADOR DE FRETES - UNIPAR
// RELATORIOS POR DATA

function carregar($nome) {
    $caminho = __DIR__ . "/" . $nome;

    if (!file_exists($caminho)) {
        return [];
    }

    $dados = json_decode(
        file_get_contents($caminho),
        true
    );

    return is_array($dados) ? $dados : [];
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

function enderecoCompleto($e) {
    if (!$e) {
        return "";
    }

    return
        ($e["rua"] ?? "") .
        ", nº " . ($e["numero"] ?? "") .
        " - " . ($e["bairro"] ?? "") .
        " - " . ($e["cidade"] ?? "") .
        "/" . ($e["estado"] ?? "") .
        (!empty($e["cep"])
            ? " - CEP " . $e["cep"]
            : "");
}

function enderecoFrete($frete, $tipo, $enderecos) {
    // Utiliza o endereço completo salvo no frete.
    if (!empty($frete[$tipo . "_completa"])) {
        return $frete[$tipo . "_completa"];
    }

    // Recupera o endereço dos fretes antigos.
    $endereco = buscar(
        $enderecos,
        $frete[$tipo . "_id"] ?? ""
    );

    if ($endereco) {
        return enderecoCompleto($endereco);
    }

    return $frete[$tipo] ?? "";
}

function descricaoProdutos($itens) {
    $nomes = [];

    foreach ($itens as $item) {
        $nomes[] =
            ($item["nome"] ?? "Produto") .
            " - " .
            ($item["quantidade"] ?? 0) .
            " un.";
    }

    return implode("; ", $nomes);
}

$fretes = carregar("fretes.json");
$enderecos = carregar("enderecos.json");

$data = $_GET["data"] ?? date("Y-m-d");
$consultar = isset($_GET["consultar"]);
$baixar = isset($_GET["baixar"]);

$dataObj = DateTime::createFromFormat("!Y-m-d", $data);

$dataValida =
    $dataObj &&
    $dataObj->format("Y-m-d") === $data;

$resultados = [];

if ($dataValida && ($consultar || $baixar)) {
    foreach ($fretes as $frete) {
        if (($frete["data"] ?? "") === $data) {
            $resultados[] = $frete;
        }
    }
}

// BAIXAR RELATORIO CSV
if ($baixar && $dataValida) {

    $nomeArquivo = "relatorio_fretes_" . $data . ".csv";

    header("Content-Type: text/csv; charset=UTF-8");
    header(
        'Content-Disposition: attachment; filename="' .
        $nomeArquivo . '"'
    );
    header("Cache-Control: no-store");

    $saida = fopen("php://output", "w");

    // BOM para acentos no Excel
    fwrite($saida, "\xEF\xBB\xBF");

    fputcsv($saida, [
        "Data",
        "Transportador",
        "Origem",
        "Destino",
        "Produtos",
        "Peso total (kg)"
    ], ";", '"', "");

    foreach ($resultados as $frete) {

        fputcsv($saida, [
            date("d/m/Y", strtotime($frete["data"])),
            $frete["transportador"] ?? "",
            enderecoFrete($frete, "origem", $enderecos),
            enderecoFrete($frete, "destino", $enderecos),
            descricaoProdutos($frete["produtos"] ?? []),
            $frete["peso_total"] ?? 0
        ], ";", '"', "");
    }

    fclose($saida);
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Relatórios de Fretes</title>

    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      rel="stylesheet">

<link rel="stylesheet" href="tema_preto_dourado.css">

</head>

<body class="bg-light">

<div class="container mt-5 mb-5">

    <h1 class="text-primary mb-4">
        Relatórios de Fretes
    </h1>

    <div class="card mb-4">
        <div class="card-body">

            <h4 class="mb-3">
                Consultar Fretes por Data
            </h4>

            <form method="GET" action="relatorios.php">

                <label class="form-label">
                    Selecione a data do frete
                </label>

                <input
                    type="date"
                    name="data"
                    class="form-control mb-3"
                    value="<?= mostrar($data) ?>"
                    required>

                <button
                    type="submit"
                    name="consultar"
                    value="1"
                    class="btn btn-primary">
                    Consultar Fretes
                </button>

                <a
                    href="index.php"
                    class="btn btn-secondary">
                    Voltar ao Menu
                </a>

            </form>

        </div>
    </div>

    <?php if (!$dataValida): ?>

        <div class="alert alert-danger">
            Informe uma data válida.
        </div>

    <?php elseif ($consultar): ?>

        <h2 class="mb-3">
            Resultado da Consulta
        </h2>

        <p>
            <strong>Data:</strong>
            <?= mostrar($dataObj->format("d/m/Y")) ?>
        </p>

        <p>
            <strong>Fretes encontrados:</strong>
            <?= count($resultados) ?>
        </p>

        <?php if (empty($resultados)): ?>

            <div class="alert alert-warning">
                Nenhum frete encontrado nessa data.
            </div>

        <?php else: ?>

            <div class="table-responsive">

                <table class="table table-striped table-bordered">

                    <thead class="table-dark">
                        <tr>
                            <th>Data</th>
                            <th>Transportador</th>
                            <th>Origem</th>
                            <th>Destino</th>
                            <th>Produtos</th>
                            <th>Peso total</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($resultados as $frete): ?>

                        <tr>

                            <td>
                                <?= mostrar(
                                    date(
                                        "d/m/Y",
                                        strtotime($frete["data"])
                                    )
                                ) ?>
                            </td>

                            <td>
                                <?= mostrar(
                                    $frete["transportador"] ?? ""
                                ) ?>
                            </td>

                            <td>
                                <?= mostrar(
                                    enderecoFrete(
                                        $frete,
                                        "origem",
                                        $enderecos
                                    )
                                ) ?>
                            </td>

                            <td>
                                <?= mostrar(
                                    enderecoFrete(
                                        $frete,
                                        "destino",
                                        $enderecos
                                    )
                                ) ?>
                            </td>

                            <td>
                                <?php foreach (
                                    ($frete["produtos"] ?? []) as $item
                                ): ?>
                                    <div>
                                        <?= mostrar(
                                            $item["nome"] ?? ""
                                        ) ?>
                                        -
                                        <?= mostrar(
                                            $item["quantidade"] ?? 0
                                        ) ?>
                                        un.
                                    </div>
                                <?php endforeach; ?>
                            </td>

                            <td>
                                <?= mostrar(
                                    $frete["peso_total"] ?? 0
                                ) ?> kg
                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>
                </table>
            </div>

            <a
                href="relatorios.php?data=<?= urlencode($data) ?>&baixar=1"
                class="btn btn-success">
                Baixar Relatório (Excel/CSV)
            </a>

        <?php endif; ?>

    <?php endif; ?>

</div>

</body>
</html>
