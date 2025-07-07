<?php
    // Inclusão de arquivos de configuração e utilitários
    include '../basedados/basedados.h'; 
    include 'const_utilizadores.php';
    include 'dados_navbar.php';

    // Inicialização de variáveis
    $mensagem_erro = "";
    
    // Verifica se o utilizador tem o login feito   
    $mostrar_alertas = false;
    $numero_alertas_cliente = 0;
    $tipo_utilizador = 0;
    
    $pesquisa = isset($_GET['pesquisa']) ? trim($_GET['pesquisa']) : "";
    $filtro_cliente = isset($_GET['filtro_cliente']) ? $_GET['filtro_cliente'] : "";
    $ordenacao = isset($_GET['ordenacao']) ? $_GET['ordenacao'] : "id_asc";
    
    // Lista de bilhetes e clientes
    $bilhetes = [];
    $clientes = [];
    
    // Inicia a sessão se ainda não estiver iniciada
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // Verificar sessão
    if (!isset($_SESSION['id_utilizador'])) {
        header("Location: entrar.php");
        exit();
    }
    
    // Obter o nome do utilizador na sessão
    $nome_utilizador = isset($_SESSION['nome_utilizador']) ? $_SESSION['nome_utilizador'] : "";
    // Get idUtilizador from session
    $id_utilizador = intval($_SESSION['id_utilizador']);

    // Determina página inicial baseada no tipo de utilizador
    $pagina_inicial = "index.php";
    $tipo_utilizador = intval($_SESSION['tipo_utilizador']);
    
    // Variável para verificar se o login foi feito, baseada na existência de id_utilizador na sessão
    $tem_login = isset($_SESSION['id_utilizador']);

    if ($tem_login && isset($_SESSION['tipo_utilizador'])) {
        $tipo = intval($_SESSION['tipo_utilizador']);
        switch ($tipo) {
            case 1: // Admin
                $pagina_inicial = "pagina_inicial_admin.php";
                break;
            case 2: // Funcionário
                $pagina_inicial = "pagina_inicial_func.php";
                break;
            case 3: // Cliente
                $pagina_inicial = "pagina_inicial_cliente.php";
                break;
            default:
                $pagina_inicial = "index.php";
        }
    }

    global $conn; 
    if ($conn != null) {
        try {
            // Para Clientes (tipo_utilizador == 3)
            if ($tem_login && intval($_SESSION['tipo_utilizador']) == CLIENTE) {
                $sql_contagem = "SELECT COUNT(*) as total 
                                 FROM alerta a 
                                 JOIN utilizador_alerta ua ON a.id_alerta = ua.id_alerta 
                                 WHERE ua.idUtilizador = ? AND a.estado = 1";
                
                $stmt_contagem = $conn->prepare($sql_contagem);
                $stmt_contagem->bind_param("i", $id_utilizador);
                $stmt_contagem->execute();
                
                $resultado_contagem = $stmt_contagem->get_result();
                if ($resultado_contagem->num_rows > 0) {
                    $row = $resultado_contagem->fetch_assoc();
                    $numero_alertas_cliente = $row['total'];
                    $mostrar_alertas = $numero_alertas_cliente > 0;
                }
                $stmt_contagem->close();
            } else if (!$tem_login) {
                // Para Visitantes
                $sql_contagem = "SELECT COUNT(*) as total 
                                 FROM alerta a 
                                 JOIN utilizador_alerta ua ON a.id_alerta = ua.id_alerta 
                                 WHERE ua.idUtilizador = 4 AND a.estado = 1";
                $stmt = $conn->prepare($sql_contagem);
                $stmt->execute();
                $resultado = $stmt->get_result();
                if ($resultado->num_rows > 0) {
                    $row = $resultado->fetch_assoc();
                    $numero_alertas_cliente = $row['total'];
                    $mostrar_alertas = $numero_alertas_cliente > 0;
                }
                $stmt->close();
            }
        } catch (mysqli_sql_exception $e) {
            error_log("Erro ao contar alertas: " . $e->getMessage());
        }
    }

    // Processa o formulário POST
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Obtém e valida o valor
        $valor_str = isset($_POST['valor']) ? $_POST['valor'] : "";
        $valor = 0;
        $saldo_atual = 0;

        // Verifica se o valor é nulo ou vazio e tenta converter
        if (!empty($valor_str)) {
            $valor = filter_var($valor_str, FILTER_VALIDATE_FLOAT);
            if ($valor === false) {
                $mensagem_erro = "Por favor, insira um valor positivo válido.";
            }
        } else {
            $mensagem_erro = "Por favor, insira um valor positivo válido.";
        }
        
        // Verifica se o valor é positivo e se ainda não há erros
        if (empty($mensagem_erro) && $valor <= 0) {
            $mensagem_erro = "Por favor, insira um valor positivo válido.";
        } 
        
        // Só continua se não houver erros iniciais
        if (empty($mensagem_erro)) {
            if ($conn != null) {
                $pstmt = null;
                $rs = null;
                try {
                    // Selecionz o id da carteira do utilizador e o saldo atual
                    $sql_obter_dados_carteira = "SELECT c.id_carteira, c.saldo FROM utilizador u JOIN carteira c ON u.id_carteira = c.id_carteira WHERE u.id = ?";
                    $pstmt = $conn->prepare($sql_obter_dados_carteira);
                    $pstmt->bind_param("i", $id_utilizador);
                    $pstmt->execute();
                    $rs = $pstmt->get_result();

                    if ($rs->num_rows > 0) {
                        $row = $rs->fetch_assoc();
                        $id_carteira = $row['id_carteira'];
                        $saldo_atual = $row['saldo'];

                        if ($valor > $saldo_atual) {
                            $mensagem_erro = "Saldo insuficiente. Não pode levantar mais do que tem na carteira.";
                        } else {
                            // Atualiza o saldo da carteira
                            $sql_atualiza_carteira = "UPDATE carteira SET saldo = saldo - ? WHERE id_carteira = ?";
                            $pstmt->close();
                            $pstmt = $conn->prepare($sql_atualiza_carteira);
                            $pstmt->bind_param("di", $valor, $id_carteira);
                            $linhas_afetadas = $pstmt->execute();

                            if ($linhas_afetadas) {
                                
                                // Obter o novo saldo da carteira
                                $sql_obtem_novo_saldo = "SELECT saldo FROM carteira WHERE id_carteira = ?";
                                $pstmt->close();
                                $pstmt = $conn->prepare($sql_obtem_novo_saldo);
                                $pstmt->bind_param("i", $id_carteira);
                                $pstmt->execute();
                                $rs->close();
                                $rs = $pstmt->get_result();
                                if ($rs->num_rows > 0) {
                                    $novo_saldo_row = $rs->fetch_assoc();
                                    $novo_saldo = number_format($novo_saldo_row['saldo'], 2, ',', '.'); // Formata para PT-PT
                                    $_SESSION['valor_carteira'] = $novo_saldo;
                                }
                                
                                // Redireciona baseado no tipo de utilizador
                                if ($tipo_utilizador == CLIENTE) {
                                    header("Location: pagina_inicial_cliente.php");
                                    exit();
                                } else if ($tipo_utilizador == FUNCIONARIO) {
                                    header("Location: pagina_inicial_func.php");
                                    exit();
                                } else {
                                    header("Location: pagina_inicial_admin.php");
                                    exit();
                                }
                            } else {
                                $mensagem_erro = "Erro ao atualizar o saldo: " . $conn->error;
                            }
                        }
                    } else {
                        $mensagem_erro = "Utilizador não encontrado ou carteira não associada.";
                    }
                } catch (mysqli_sql_exception $e) {
                    $mensagem_erro = "Erro ao processar a solicitação: " . $e->getMessage();
                    error_log("Erro SQL: " . $e->getMessage());
                } finally {
                    if ($rs != null) $rs->close();
                    if ($pstmt != null) $pstmt->close();
                }
            } else {
                $mensagem_erro = "Erro: Falha na conexão com a base de dados.";
            }
        }
        
        // Se houver um erro mostrar ao utilizador
        if (!empty($mensagem_erro)) {
            $_SESSION['mensagem_erro'] = $mensagem_erro;
            header("Location: remover_saldo.php");
            exit();
        }
    }
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>FelixBus - Remover Saldo</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

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

    <style>
        .dropdown:hover > .dropdown-menu {
            display: block;
        }
        .alerta-card-cliente {
            border-left: 4px solid #28a745;
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(40, 167, 69, 0.05));
            position: relative;
        }
        .alerta-card-cliente:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(40, 167, 69, 0.2);
        }
        .alert-badge {
            position: absolute;
            top: 0px;
            right: -8px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        .alerta-cliente-destaque {
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.15), rgba(40, 167, 69, 0.1));
            border: 2px solid rgba(40, 167, 69, 0.3);
            border-radius: 15px;
        }
    </style>
</head>
<body>
    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
    <div class="container-fluid hero-header text-light min-vh-100 d-flex align-items-center justify-content-center">
        <nav class="navbar navbar-expand-lg navbar-light px-5 px-lg-5 py-3 py-lg-3">
            <a href="<?= $pagina_inicial ?>" class="navbar-brand p-0">
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
                    
                    <?php if ($mostrar_alertas) { ?>
                        <a href="consultar_alertas.php" class="nav-item nav-link position-relative">
                            Alertas
                            <?php if ($numero_alertas_cliente > 0) { ?>
                                <span class="alert-badge"><?= $numero_alertas_cliente ?></span>
                            <?php } ?>
                        </a>
                    <?php } ?>

                    <?php if ($tem_login && isset($_SESSION['tipo_utilizador'])) { ?>
                        <?php if ($tipo_utilizador == 1 || $tipo_utilizador == 2) { ?>
                            <?php if ($tipo_utilizador == 1) { ?>
                                <a href="consultar_utilizadores.php" class="nav-item nav-link">Utilizadores</a>
                            <?php } ?>
                            <a href="consultar_bilhetes.php" class="nav-item nav-link">Bilhetes</a>
                        <?php } ?>
                    <?php } ?>
                </div>

                <?php if ($tem_login) { ?>
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link dropdown-toggle active" id="submenu-carteira" role="button" aria-expanded="false">
                            <i class="fa fa-wallet me-2"></i> 
                            <?= isset($_SESSION['valor_carteira']) ? $_SESSION['valor_carteira'] : "0,00" ?> €
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="submenu-carteira">

                            <?php if (intval($_SESSION['tipo_utilizador']) != 2) { ?>
                                <li><a class="dropdown-item" href="adicionar_saldo.php"><i class="fas fa-plus-circle"></i>Adicionar</a></li>
                                <li><a class="dropdown-item" href="remover_saldo.php"><i class="fas fa-minus-circle"></i>Remover</a></li>
                            <?php } ?>

                            <?php $tipo_utilizador_sessao = intval($_SESSION['tipo_utilizador']); ?>
                            <?php if($tipo_utilizador_sessao == 1 || $tipo_utilizador_sessao == 2) { ?>
                                <li><a class="dropdown-item" href="consultar_saldo_clientes.php"><i class="fas fa-user"></i>Consulta Clientes</a></li>
                            <?php } ?>

                        </ul>
                    </div>

                    <?php if (intval($_SESSION['tipo_utilizador']) == 3) { ?>
                        <div class="nav-item dropdown">
                            <a href="#" class="nav-link dropdown-toggle" id="submenu-bilhetes" role="button" aria-expanded="false">
                                <i class="fa fa-ticket-alt me-2"></i> <?= $numero_bilhetes ?>
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="submenu-bilhetes">
                                <li><a class="dropdown-item" href="consultar_bilhetes.php"><i class="fas fa-eye"></i>Consultar Bilhetes</a></li>
                            </ul>
                        </div>
                    <?php } ?>

                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link d-flex align-items-center text-primary me-3 dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa fa-user-circle fa-2x me-2"></i>
                            <span><?= $nome_utilizador ?></span>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="submenu-utilizador">
                            <li><a class="dropdown-item" href="consultar_dados.php"><i class="fas fa-user-cog me-2"></i> Consultar Dados</a></li>
                            <li><a class="dropdown-item" href="sair.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </div>
                <?php } else { ?>
                    <a href="entrar.php" class="btn btn-primary rounded-pill py-2 px-4">Entrar</a>
                <?php } ?>
            </div>
        </nav>

        <div class="rounded shadow" style="max-width: 1200px; width: 100%; margin-top: 150px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="text-white m-0">Levantar Saldo</h3>
            </div>

            <?php 
                // Mostrar mensagem de erro 
                if (isset($_SESSION['mensagem_erro'])) {
                    $mensagem_erro = $_SESSION['mensagem_erro'];
                    unset($_SESSION['mensagem_erro']);
            ?>
                <div class="alert alert-danger"><?= $mensagem_erro ?></div>
            <?php } ?>

            <div class="alert alert-success">
            <script>
                setTimeout(function() {
                    window.location.href = "<?= $pagina_inicial ?>";
                }, 2000);
            </script>

            <div class="bg-gradient mb-3 p-5 position-relative mx-auto mt-3 animated slideInDown">
                <form action="remover_saldo.php" method="POST">
                    <div class="mb-3">
                        <label for="valor" class="form-label">Quanto dinheiro (€) pretende levantar?</label>
                        <input name="valor" id="valor" type="number" step="0.01" min="0.01" class="form-control text-dark" required/>
                    </div>
                    <div class="d-flex justify-content-center">
                        <input type="submit" value="Levantar Saldo" class="btn btn-success rounded-pill py-2 px-5">
                    </div>
                </form>
            </div>
        </div>
    </div>

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

    <div class="container-fluid bg-dark d-flex justify-content-center text-light footer pt-5 wow fadeIn" data-wow-delay="0.1s">
        <div class="container py-5">
            <div class="row">
                <div class="col-lg-4">
                    <h4 class="text-white mb-3">Localização</h4>
                    <p class="mb-2"><i class="fa fa-map-marker-alt me-3"></i>Rua 123, Castelo Branco, Portugal</p>
                </div>
                <div class="col-lg-4">
                    <h4 class="text-white mb-3">Contactos</h4>
                    <p class="mb-2"><i class="fa fa-phone me-3"></i><strong>Telemóvel:</strong> +351 925 887 788</p>
                    <p class="mb-2"><i class="fa fa-phone me-3"></i><strong>Telefone:</strong> +351 272 999 888</p>
                </div>
                <div class="col-lg-4">
                    <h4 class="text-white mb-3">Horário de Funcionamento</h4>
                    <p class="mb-2"><i class="fa fa-clock me-3"></i><strong>Segunda a Sexta:</strong> 9:00 - 17:00</p>
                    <p class="mb-2"><i class="fa fa-clock me-3"></i><strong>Sábados:</strong> 10:00 - 16:00</p>
                    <p class="mb-2"><i class="fa fa-clock me-3"></i><strong>Domingos e Feriados:</strong> 9:00 - 13:00</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>