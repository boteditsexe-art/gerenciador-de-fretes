
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Gerenciador de Fretes</title>

    <!-- Bootstrap -->
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
      rel="stylesheet">

    <style>
        body {
            background-color: #17191d;
            color: #ffffff;
            font-family: Arial, sans-serif;
            min-height: 100vh;
        }

        .painel {
            background-color: #202227;
            border: 1px solid #393b40;
            border-radius: 18px;
            padding: 35px;
            max-width: 950px;
            margin: 55px auto;
        }

        .logo {
            width: 65px;
            height: 65px;
            background-color: transparent;
            color: #d5a43b;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 35px;
        }

        h1 {
            color: #ffffff;
            font-weight: bold;
        }

        .dourado {
            color: #d5a43b;
        }

        .subtitulo {
            color: #a9abb2;
        }

        .linha {
            border-color: #555;
            margin: 25px 0;
        }

        .card-menu {
            display: block;
            background-color: #292b30;
            border: 1px solid #44464c;
            border-radius: 12px;
            padding: 25px;
            color: #ffffff;
            text-decoration: none;
            transition: 0.2s;
            height: 100%;
        }

        .card-menu:hover {
            background-color: #34363c;
            border-color: #d5a43b;
            color: #ffffff;
            transform: translateY(-3px);
        }

        .icone {
            font-size: 30px;
            margin-bottom: 12px;
            color: #d5a43b;
        }

        .card-menu h4 {
            font-size: 19px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .card-menu p {
            color: #b7b8bd;
            font-size: 14px;
            margin-bottom: 0;
        }

        .botao-relatorio {
            display: block;
            background-color: #d5a43b;
            color: #17191d;
            text-align: center;
            padding: 16px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: bold;
            transition: 0.2s;
        }

        .botao-relatorio:hover {
            background-color: #edbd54;
            color: #17191d;
        }

        .rodape {
            color: #8f9198;
            font-size: 13px;
            text-align: center;
            margin-top: 30px;
        }

        @media (max-width: 576px) {
            .painel {
                padding: 20px;
                margin: 20px auto;
            }

            h1 {
                font-size: 25px;
            }
        }
   
 </style>
</head>

<body>

<div class="container">

    <main class="painel">

        <!-- TITULO PRINCIPAL -->
        <div class="d-flex align-items-center gap-3">

            <div class="logo">
                <i class="bi bi-truck"></i>
            </div>

            <div>
                <h1 class="mb-1">
                    Gerenciador de Fretes
                </h1>
            </div>

        </div>

        <hr class="linha">

        <h2 class="mb-2">
            Painel Principal
        </h2>

        <p class="subtitulo mb-4">
            Selecione uma das opções para continuar.
        </p>

        <!-- MENU PRINCIPAL -->
        <div class="row g-3">

            <!-- PRODUTOS -->
            <div class="col-md-6">
                <a href="produtos.php"
                   class="card-menu">

                    <div class="icone">
                        <i class="bi bi-box-seam"></i>
                    </div>

                    <h4>Produtos</h4>

                    <p>
                        Cadastrar e editar produtos.
                    </p>
                </a>
            </div>

            <!-- ENDERECOS -->
            <div class="col-md-6">
                <a href="enderecos.php"
                   class="card-menu">

                    <div class="icone">
                        <i class="bi bi-geo-alt"></i>
                    </div>

                    <h4>Endereços</h4>

                    <p>
                        Gerenciar origens e destinos.
                    </p>
                </a>
            </div>

            <!-- TRANSPORTADORES -->
            <div class="col-md-6">
                <a href="transportadores.php"
                   class="card-menu">

                    <div class="icone">
                        <i class="bi bi-truck-front"></i>
                    </div>

                    <h4>Transportadores</h4>

                    <p>
                        Cadastrar veículos e transportadores.
                    </p>
                </a>
            </div>

            <!-- FRETES -->
            <div class="col-md-6">
                <a href="fretes.php"
                   class="card-menu">

                    <div class="icone">
                        <i class="bi bi-clipboard-check"></i>
                    </div>

                    <h4>Fretes</h4>

                    <p>
                        Criar fretes com vários produtos.
                    </p>
                </a>
            </div>

        </div>

        <!-- RELATORIOS -->
        <div class="mt-4">

            <a href="relatorios.php"
               class="botao-relatorio">

                <i class="bi bi-file-earmark-text me-2"></i>
                Relatórios de Fretes

            </a>

        </div>

        <!-- RODAPE -->
        <div class="rodape">
            Projeto Prático - Fundamentos de
            Programação para Internet
        </div>

    </main>

</div>

</body>
</html>
