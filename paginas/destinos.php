<?php 
    // Inicia a sessão
    session_start();

    // Include conexão à BD
    include("../basedados/basedados.h"); 
    include("dados_navbar.php");

    // Verifica se o utilizador tem o login feito   
    $tem_login = isset($_SESSION['id_utilizador']) && !empty($_SESSION['id_utilizador']);
    $mostrar_alertas = false;
    $numero_alertas_cliente = 0;
    
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

    if ($conn) {
        // Para CLIENTE (tipo_utilizador == 3)
        if ($tem_login && $_SESSION['tipo_utilizador'] == 3) {
            $sql_count = "SELECT COUNT(*) as total 
                            FROM alerta a
                            JOIN utilizador_alerta ua ON a.id_alerta = ua.id_alerta
                            WHERE ua.id_utilizador = ? AND a.estado = 1";
            
            $stmt_count = $conn->prepare($sql_count);
            if ($stmt_count) {
                $stmt_count->bind_param("i", $_SESSION['id_utilizador']);
                if ($stmt_count->execute()) {
                    $resultado_count = $stmt_count->get_result();
                    $row_count = $resultado_count->fetch_assoc();
                    $numero_alertas_cliente = $row_count['total'];
                    $mostrar_alertas = $numero_alertas_cliente > 0;
                }
                $stmt_count->close();
            }
        } else if (!$tem_login) {
            // Para Visitantes
            $sql_count = "SELECT COUNT(*) as total 
                         FROM alerta a
                         JOIN utilizador_alerta ua ON a.id_alerta = ua.id_alerta
                         WHERE ua.id_utilizador = 4 AND a.estado = 1";
            $result = $conn->query($sql_count);
            if ($result) {
                $row = $result->fetch_assoc();
                $numero_alertas_cliente = $row['total'];
                $mostrar_alertas = $numero_alertas_cliente > 0;
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>FelixBus - Destinos</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <!-- Favicon -->
    <link href="favicon.ico" rel="icon">

    <!-- Fontes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Nunito:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Bibliotecas -->
    <link href="animate.min.css" rel="stylesheet">
    <link href="owl.carousel.min.css" rel="stylesheet">
    <link href="tempusdominus-bootstrap-4.min.css" rel="stylesheet" />

    <link href="bootstrap.min.css" rel="stylesheet">

    <link href="style.css" rel="stylesheet">

    <style>
        .dropdown:hover > .dropdown-menu {
            display: block;
        }

        /* Estilos específicos para clientes */
        .alerta-card-cliente {
            border-left: 4px solid #28a745;
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(40, 167, 69, 0.05));
            position: relative;
        }
        
        .alerta-card-cliente:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(40, 167, 69, 0.2);
        }

        /* Badge de notificação para alertas */
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

        /* Destaque especial para alertas do cliente */
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

    <!-- BARRA DE NAVEGAÇÃO -->
    <div class="container-fluid position-relative p-0">
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
                    <a href="destinos.php" class="nav-item nav-link active">Destinos</a>
                    <a href="consultar_rotas.php" class="nav-item nav-link">Rotas</a>

                    <!-- Link de Alertas - só aparece se houver alertas ou é admin -->
                    <?php if ($mostrar_alertas || $_SESSION['tipo_utilizador'] == 1): ?>
                        <a href="consultar_alertas.php" class="nav-item nav-link position-relative">
                            Alertas
                            <?php if ($numero_alertas_cliente > 0): ?>
                                <span class="alert-badge"><?php echo $numero_alertas_cliente; ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>

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
                    <!-- Dropdown da Carteira -->
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link dropdown-toggle" id="walletDropdownLink" role="button" aria-expanded="false">
                            <i class="fa fa-wallet me-2"></i> 
                            <?php echo isset($_SESSION['valor_carteira']) ? $_SESSION['valor_carteira'] : '0,00'; ?> €
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="walletDropdownLink">
                            <?php if ($_SESSION['tipo_utilizador'] != 2): ?>
                                <li><a class="dropdown-item" href="adicionar_saldo.php"><i class="fas fa-plus-circle"></i>Adicionar</a></li>
                                <li><a class="dropdown-item" href="remover_saldo.php"><i class="fas fa-minus-circle"></i>Remover</a></li>
                            <?php endif; ?>

                            <?php if(in_array($_SESSION['tipo_utilizador'], [1,2])): ?>
                                <li><a class="dropdown-item" href="consultar_saldo_clientes.php"><i class="fas fa-user"></i>Consulta Clientes</a></li>
                            <?php endif; ?>

                        </ul>
                    </div>

                    <!-- Dropdown dos Bilhetes -->
                    <?php if ($_SESSION['tipo_utilizador'] == 3): ?>
                        <div class="nav-item dropdown">
                            <a href="#" class="nav-link dropdown-toggle" id="ticketsDropdownLink" role="button" aria-expanded="false">
                                <i class="fa fa-ticket-alt me-2"></i> <?php echo $numero_bilhetes; ?>
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="ticketsDropdownLink">
                                <li><a class="dropdown-item" href="consultar_bilhetes.php"><i class="fas fa-eye"></i>Consultar Bilhetes</a></li>
                            </ul>
                        </div>
                    <?php endif; ?>

                <!-- Dropdown do Utilizador -->
                <div class="nav-item dropdown">
                    <a href="#" class="nav-link d-flex align-items-center text-primary me-3 dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa fa-user-circle fa-2x me-2"></i>
                        <span><?php echo htmlspecialchars($nome_utilizador); ?></span>
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="consultar_dados.php"><i class="fas fa-user-cog me-2"></i> Consultar Dados</a></li>
                        <li><a class="dropdown-item" href="sair.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </div>
                <?php else: ?>
                    <a href="entrar.php" class="btn btn-primary rounded-pill py-2 px-4">Entrar</a>
                <?php endif; ?>
            </div>
        </nav>

        <div class="container-fluid bg-primary py-5 hero-header align-content-center">
            <div class="container-xxl py-5 destination">
                <div class="container">
                    <div class="text-center wow fadeInUp" data-wow-delay="0.1s">
                        <h1 class="mb-5 text-primary">Destinos</h1>
                    </div>
                    <div class="row g-3">
                        <div class="col-lg-7 col-md-6">
                            <div class="row g-3">
                                <div class="col-lg-12 col-md-12 wow zoomIn" data-wow-delay="0.1s">
                                    <a class="position-relative d-block overflow-hidden" href="https://www.lisboa.pt/" target="_blank">
                                        <img class="img-fluid" src="destino_1.jpg" alt="Cidade de Lisboa">
                                        <div class="bg-white text-success fw-bold position-absolute bottom-0 end-0 m-3 py-1 px-2">Lisboa</div>
                                    </a>
                                </div>
                                <div class="col-lg-6 col-md-12 wow zoomIn" data-wow-delay="0.3s">
                                    <a class="position-relative d-block overflow-hidden" href="https://www.cm-castelobranco.pt/" target="_blank">
                                        <img class="img-fluid" src="destino_2.jpg" alt="Cidade de Castelo Branco">
                                        <div class="bg-white text-success fw-bold position-absolute bottom-0 end-0 m-3 py-1 px-2">Castelo Branco</div>
                                    </a>
                                </div>
                                <div class="col-lg-6 col-md-12 wow zoomIn" data-wow-delay="0.5s">
                                    <a class="position-relative d-block overflow-hidden" href="https://www.cm-faro.pt/" target="_blank">
                                        <img class="img-fluid" src="destino_3.jpg" alt="Cidade de Faro" style="height: 270px;">
                                        <div class="bg-white text-success fw-bold position-absolute bottom-0 end-0 m-3 py-1 px-2">Faro</div>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-5 col-md-6 wow zoomIn" data-wow-delay="0.7s" style="min-height: 350px;">
                            <a class="position-relative d-block h-100 overflow-hidden" href="https://www.cm-porto.pt/" target="_blank">
                                <img class="img-fluid position-absolute w-100 h-100" src="destino_4.jpg" alt="Cidade do Porto" style="object-fit: cover;">
                                <div class="bg-white text-success fw-bold position-absolute bottom-0 end-0 m-3 py-1 px-2">Porto</div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Começo Rodapé -->
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
    <!-- Começo Rodapé -->

    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="wow.min.js"></script>
    <script src="easing.min.js"></script>
    <script src="waypoints/waypoints.min.js"></script>
    <script src="owl.carousel.min.js"></script>
    <script src="moment.min.js"></script>
    <script src="moment-timezone.min.js"></script>
    <script src="tempusdominus-bootstrap-4.min.js"></script>

    <script src="main.js"></script>
</body>

</html>