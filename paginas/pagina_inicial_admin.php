<?php

    // Inicia a sessão
    session_start();

    // Include BD
    include("..\basedados\basedados.h");
    include("dados_navbar.php");

    $tem_login = isset($_SESSION['id_utilizador']) && !empty($_SESSION['id_utilizador']);
    $mostrar_alertas = false;
    $numero_alertas_cliente = 0;

    if ($conn) {
        // Para CLIENTES (tipo_utilizador == 3)
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
    <title>FelixBus</title>
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

    <script src="main.js" defer></script>
</head>

<body>
    <!-- RODA DE CARREGAMENTO -->
    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
    <div class="container-fluid position-relative p-0">
        <nav class="navbar navbar-expand-lg navbar-light px-5 px-lg-5 py-3 py-lg-3">
            <a href="pagina_inicial_admin.php" class="navbar-brand p-0">
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
                    
                    <!-- Link de Alertas -->
                    <a href="consultar_alertas.php" class="nav-item nav-link position-relative">
                        Alertas
                        <?php if ($numero_alertas_cliente > 0): ?>
                            <span class="alert-badge"><?php echo $numero_alertas_cliente; ?></span>
                        <?php endif; ?>
                    </a>

                    <a href="consultar_utilizadores.php" class="nav-item nav-link">Utilizadores</a>
                    <a href="consultar_bilhetes.php" class="nav-item nav-link">Bilhetes</a>
                </div>

                <?php if ($temLogin): ?>
                    <!-- Dropdown da Carteira -->
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link dropdown-toggle" id="walletDropdownLink" role="button" aria-expanded="false">
                            <i class="fa fa-wallet me-2"></i> <?php echo $valor_carteira; ?> €
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="walletDropdownLink">
                            <li><a class="dropdown-item" href="adicionar_saldo.php"><i class="fas fa-plus-circle"></i>Adicionar</a></li>
                            <li><a class="dropdown-item" href="remover_saldo.php"><i class="fas fa-minus-circle"></i>Remover</a></li>
                            <li><a class="dropdown-item" href="consultar_saldo_clientes.php"><i class="fas fa-user"></i>Consulta Clientes</a></li>
                        </ul>
                    </div>

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

        <div class="container-fluid bg-primary py-5 hero-header">
            <div class="container py-5">
                <div class="row justify-content-center py-5">
                    <div class="col-lg-10 pt-lg-5 mt-lg-5 text-center">
                         <h1 class="display-3 text-white mb-3 animated slideInDown">A sua próxima viagem começa aqui!</h1>
                        <p class="fs-4 text-white mb-4 animated slideInDown">Viaje com a FelixBus e descubra novos destinos em Portugal!</p>
                        <div class="bg-gradient position-relative w-75 mx-auto mt-5 animated slideInDown">
                            <form method="GET" action="viagens.php" class="d-flex flex-wrap p-4 rounded text-light justify-content-center" style="gap: 2rem 0.5rem;">
                                <div class="me-4">
                                    <label class="form-label">Origem:</label>
                                    <select name="origem" id="origem" class="form-select bg-dark text-light border-primary" required>
                                        <option>A carregar...</option>
                                    </select>
                                </div>

                                <div class="me-4">
                                    <label class="form-label">Destino:</label>
                                    <select name="destino"  id="destino" class="form-select bg-dark text-light border-primary" required>
                                        <option>A carregar...</option>
                                    </select>
                                </div>

                                <div class="me-4">
                                    <label class="form-label">Data de viagem:</label>
                                    <input name="data" id="data" type="date" class="form-control bg-dark text-light border-primary" autocomplete="off" required/>
                                </div>

                                <div class="w-100 text-center mt-2">
                                    <input type="submit" value="Procurar" class="btn btn-primary text-light px-5 py-2 rounded-pill" />
                                </div>
                            </form>
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
    <!-- Fim Rodapé -->

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