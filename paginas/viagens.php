<?php
    session_start();

    // Includes
    include("../basedados/basedados.h"); 
    include("dados_navbar.php");

    // Variável para armazenar mensagens de erro PHP (conexão, query, etc.)
    $mensagem_erro = '';
    $mensagem_sucesso = '';

    // Verifica se o utilizador tem o login feito   
    $tem_login = isset($_SESSION['id_utilizador']) && !empty($_SESSION['id_utilizador']);   

    // Determina a página inicial correta baseada no tipo de utilizador
    $pagina_inicial = 'index.php'; // Página padrão se não tiver login
    if ($tem_login && isset($_SESSION['tipo_utilizador'])) {
        switch ($_SESSION['tipo_utilizador']) {
            case 1: // Admin
                $pagina_inicial = 'pagina_inicial_admin.php';
                break;
            case 2: // Funcionário
                $pagina_inicial = 'pagina_inicial_func.php';
                break;
            case 3: // Cliente
                $pagina_inicial = 'pagina_inicial_cliente.php';
                break;
            default:
                $pagina_inicial = 'index.php';
        }
    }

    // Verifica se há uma mensagem de erro na sessão
    if (isset($_SESSION['mensagem_erro'])) {
        $mensagem_erro = $_SESSION['mensagem_erro'];
        // Limpa a variável de sessão para não exibir a mensagem novamente em carregamentos futuros
        unset($_SESSION['mensagem_erro']);
    }

    // Verifica se há uma mensagem de sucesso na sessão
    if (isset($_SESSION['mensagem_sucesso'])) {
        $mensagem_sucesso = $_SESSION['mensagem_sucesso'];
        // Limpa a variável de sessão para não exibir a mensagem novamente em carregamentos futuros
        unset($_SESSION['mensagem_sucesso']);
    }

    // Verifica se o formulário foi submetido
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Obtém os dados do formulário ou da sessão
        $origem = isset($_GET['origem']) ? $_GET['origem'] : (isset($_SESSION['origem']) ? $_SESSION['origem'] : '');
        $destino = isset($_GET['destino']) ? $_GET['destino'] : (isset($_SESSION['destino']) ? $_SESSION['destino'] : '');
        $data_viagem = isset($_GET['data']) ? $_GET['data'] : (isset($_SESSION['data_viagem']) ? $_SESSION['data_viagem'] : '');

        $resultados = [];

        // Só executa a consulta se tiver os parâmetros necessários e não houver mensagem de sucesso
        if ($conn && !empty($origem) && !empty($destino) && !empty($data_viagem) && empty($mensagem_sucesso)) {
            // Consulta SQL para encontrar as viagens correspondentes
            $sql = "SELECT
                        v.id AS id_viagem,
                        v.data_hora,
                        r.origem,
                        r.destino
                    FROM
                        viagem v
                    JOIN
                        rota r ON v.id_rota = r.id
                    WHERE
                        r.origem = ? AND r.destino = ? AND DATE(v.data_hora) = ?
                    ORDER BY
                        v.data_hora";

            $stmt = $conn->prepare($sql);

            if ($stmt) {
                    $stmt->bind_param("sss", $origem, $destino, $data_viagem);

                if($stmt->execute()) {
                    $resultado = $stmt->get_result();
                    $resultados = $resultado->fetch_all(MYSQLI_ASSOC);

                    if (!empty($resultados)) {
                        $_SESSION['id_viagem'] = $resultados[0]['id_viagem'];
                    }

                    $_SESSION['origem'] = $origem;
                    $_SESSION['destino'] = $destino;
                    $_SESSION['data_viagem'] = $data_viagem;
                }

                $stmt->close();
            }
        }
        
        if ($conn) {
            $conn->close();
        }
    }

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>FelixBus - Viagens</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <!-- Imagens, Fontes e CSS -->
    <link href="favicon.ico" rel="icon">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Nunito:wght@600;700;800&display=swap" rel="stylesheet">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <link href="animate.min.css" rel="stylesheet">
    <link href="owl.carousel.min.css" rel="stylesheet">
    <link href="tempusdominus-bootstrap-4.min.css" rel="stylesheet" />

    <link href="bootstrap.min.css" rel="stylesheet">

    <link href="style.css" rel="stylesheet">
</head>

<body>
    <!-- Começo Roda de Carregamento -->
    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
    <!-- Fim Roda de Carregamento -->

    <div class="container-fluid hero-header text-light min-vh-100 d-flex align-items-center justify-content-center">

        <!-- Barra de Navegação -->
        <nav class="navbar navbar-expand-lg navbar-light px-5 px-lg-5 py-3 py-lg-3">
            <a href="<?php echo htmlspecialchars($pagina_inicial) ?>" class="navbar-brand p-0">
                <h1 class="text-primary m-0"><i class="fa fa-map-marker-alt me-3"></i>FelixBus</h1>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                <span class="fa fa-bars"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarCollapse">
                <div class="navbar-nav ms-auto py-0">
                    <a href="sobre.php" class="nav-item nav-link">Sobre</a>
                    <a href="equipa.php" class="nav-item nav-link">Equipa</a>
                    <a href="destinos.php" class="nav-item nav-link">Destinos</a>
                    <a href="consultar_rotas.php" class="nav-item nav-link">Rotas</a>
                    <a href="consultar_alertas.php" class="nav-item nav-link">Alertas</a>

                    <!-- Verifica se o utilizador tem sessão iniciada e se é um administrador ou funcionário para apresentar as abas corretamente -->
                    <?php if ($tem_login && isset($_SESSION['tipo_utilizador'])) : ?>
                        <?php if (in_array($_SESSION['tipo_utilizador'], [1, 2])): ?>
                            <?php if ($_SESSION['tipo_utilizador'] == 1): ?>
                                <a href="consultar_utilizadores.php" class="nav-item nav-link">Utilizadores</a>
                            <?php endif; ?>
                            <a href="consultar_bilhetes.php" class="nav-item nav-link">Bilhetes</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <?php if ($tem_login): ?>
                    <!-- Submenu da Carteira -->
                    <div class="nav-item dropdown">
                       <a href="#" class="nav-link dropdown-toggle" id="submenu-carteira" role="button" aria-expanded="false">
                            <i class="fa fa-wallet me-2"></i> 
                            <?php echo isset($_SESSION['valor_carteira']) ? $_SESSION['valor_carteira'] : '0,00'; ?> €
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="submenu-carteira">
                            <li><a class="dropdown-item" href="adicionar_saldo.php"><i class="fas fa-plus-circle"></i>Adicionar</a></li>
                            <li><a class="dropdown-item" href="remover_saldo.php"><i class="fas fa-minus-circle"></i>Remover</a></li>

                            <?php if(in_array($_SESSION['tipo_utilizador'], [1,2])): ?>
                                <li><a class="dropdown-item" href="consultar_saldo_clientes.php"><i class="fas fa-user"></i>Consulta Clientes</a></li>
                            <?php endif; ?>

                        </ul>
                    </div>

                    <?php if($_SESSION['tipo_utilizador'] == 3): ?>
                        <!-- Submenu dos Bilhetes -->
                        <div class="nav-item dropdown">
                            <a href="#" class="nav-link dropdown-toggle" id="submenu-bilhetes" role="button" aria-expanded="false">
                                <i class="fa fa-ticket-alt me-2"></i> <?php echo $numero_bilhetes; ?>
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="submenu-bilhetes">
                                <li><a class="dropdown-item" href="consultar_bilhetes.php"><i class="fas fa-eye"></i>Consultar Bilhetes</a></li>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <!-- Submenu do Utilizador -->
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link d-flex align-items-center text-primary me-3 dropdown-toggle" id="submenu-utilizador" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa fa-user-circle fa-2x me-2"></i>
                            <span><?php echo htmlspecialchars($nome_utilizador); ?></span>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="submenu-utilizador">
                            <li><a class="dropdown-item" href="consultar_dados.php"><i class="fas fa-user-cog me-2"></i> Consultar Dados</a></li>
                            <li><a class="dropdown-item" href="sair.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="entrar.php" class="btn btn-primary rounded-pill py-2 px-4">Entrar</a>
                <?php endif; ?>
            </div>
        </nav>

        <div class="p-5 rounded shadow" style="max-width: 700px; width: 100%;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="text-white m-0">Viagens</h3>
            </div>

            <?php
                if (!empty($mensagem_erro)) {
                    echo '<div class="alert alert-danger">' . htmlspecialchars($mensagem_erro) . '</div>';
                }
                if (!empty($mensagem_sucesso)) {
                    echo '<div class="alert alert-success">' . htmlspecialchars($mensagem_sucesso) . '</div>';
                    echo '<script>
                        setTimeout(function() {
                            window.location.href = "index.php";
                        }, 2000);
                    </script>';
                }
            ?>

            <?php if (!empty($mensagem_sucesso)): ?>
                <!-- Se há mensagem de sucesso, não mostra mais nada -->
            <?php elseif (!empty($resultados)): ?>
                <div class="row g-3">
                    <?php foreach ($resultados as $viagem): ?>
                        <div class="col-12">
                            <div class="bg-gradient position-relative mx-auto mt-3 animated slideInDown">
                                <div class="card-body d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title text-primary">Viagem #<?php echo htmlspecialchars($viagem['id_viagem']); ?></h5>
                                        <p class="card-text"><strong>De:</strong> <?php echo htmlspecialchars($viagem['origem']); ?> </p>
                                        <p class="card-text"><strong>Para:</strong> <?php echo htmlspecialchars($viagem['destino']); ?></p>
                                        <p class="card-text"><strong>Data e Hora:</strong> <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($viagem['data_hora']))); ?></p>
                                    </div>
                                    <div>
                                        <?php if ($tem_login): ?>
                                            <?php if (in_array($_SESSION['tipo_utilizador'], [1, 2, 3])): ?>
                                                <form action="comprar_bilhete.php" method="GET" class="mb-3">
                                                    <input type="hidden" name="id_viagem" value="<?php echo htmlspecialchars($viagem['id_viagem']); ?>">

                                                    <?php if ($_SESSION['tipo_utilizador'] == 3): ?>
                                                        <!-- Cliente compra para si mesmo -->
                                                        <button type="submit" class="btn btn-primary text-light px-5 py-2 rounded-pill w-100 mt-3">
                                                            Comprar
                                                        </button>
                                                    <?php elseif (in_array($_SESSION['tipo_utilizador'], [1, 2])): ?>
                                                        <!-- Admin ou funcionário escolhe utilizador -->
                                                        <div class="mt-4">
                                                            <select name="id_utilizador" class="form-select bg-dark text-light border-primary" required>
                                                                <option value="">Selecione o utilizador</option>
                                                                <?php
                                                                include("../basedados/basedados.h");
                                                                $sql_utilizadores = "SELECT id, nome_utilizador FROM utilizador 
                                                                                    WHERE tipo_utilizador = 3";
                                                                $result_utilizadores = $conn->query($sql_utilizadores);
                                                                if ($result_utilizadores && $result_utilizadores->num_rows > 0) {
                                                                    while ($row = $result_utilizadores->fetch_assoc()) {
                                                                        echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['nome_utilizador']) . '</option>';
                                                                    }
                                                                }
                                                                if ($conn) $conn->close();
                                                                ?>
                                                            </select>
                                                        </div>
                                                        <button type="submit" class="btn btn-primary text-light px-5 py-2 rounded-pill w-100 mt-3">
                                                            Comprar para utilizador
                                                        </button>
                                                    <?php endif; ?>
                                                </form>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php elseif (!empty($origem) && !empty($destino) && !empty($data_viagem)): ?>
                <p class="text-center text-white">Nenhuma viagem encontrada para os critérios selecionados.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bibliotecas JS -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="wow.min.js"></script>
    <script src="easing.min.js"></script>
    <script src="waypoints.min.js"></script>
    <script src="owl.carousel.min.js"></script>
    <script src="moment.min.js"></script>
    <script src="moment-timezone.min.js"></script>
    <script src="tempusdominus-bootstrap-4.min.js"></script>

    <script src="main.js"></script>
</body>

</html>