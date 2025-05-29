<?php

    // Include BD
    include("..\basedados\basedados.h");

    // Inicia a sessão
    session_start();

    // Verifica o estado de login
    $temLogin = isset($_SESSION['id_utilizador']) && !empty($_SESSION['id_utilizador']);
    $nome_utilizador = $temLogin ? ($_SESSION['nome_utilizador'] ?? 'Utilizador') : '';
    $valor_carteira = 0;
    $numero_bilhetes = 0;
    $id_utilizador = null;

    if ($temLogin) {
        $id_utilizador = $_SESSION['id_utilizador'];

        if ($conn) {
            // Consulta para obter dados da carteira
            $sql = "SELECT id_carteira FROM utilizador WHERE id = ?";
            $stmt = $conn->prepare($sql);

            if ($stmt) {
                $stmt->bind_param("i", $id_utilizador);
                $stmt->execute();
                $resultado = $stmt->get_result();  

                if ($resultado && $resultado->num_rows > 0) {
                    $linha = $resultado->fetch_assoc();
                    $id_carteira = $linha['id_carteira'];

                    $sql_carteira = "SELECT saldo FROM carteira WHERE id_carteira = ?";
                    $stmt_carteira = $conn->prepare($sql_carteira);

                    if ($stmt_carteira) {
                        $stmt_carteira->bind_param("i", $id_carteira);
                        $stmt_carteira->execute();
                        $resultado_carteira = $stmt_carteira->get_result();

                        if ($resultado_carteira && $resultado_carteira->num_rows > 0) {
                            $linha_carteira = $resultado_carteira->fetch_assoc();
                            $valor_carteira = number_format($linha_carteira['saldo'], 2, ',', '.');
                        }

                        $stmt_carteira->close();
                    }
                }

                $stmt->close();
            }

            // Consulta para contar os bilhetes do utilizador
            $sql_bilhetes = "SELECT COUNT(*) as total_bilhetes FROM bilhete WHERE id_utilizador = ? AND estado = 1";
            $stmt_bilhetes = $conn->prepare($sql_bilhetes);

            if ($stmt_bilhetes) {
                $stmt_bilhetes->bind_param("i", $id_utilizador);
                $stmt_bilhetes->execute();
                $resultado_bilhetes = $stmt_bilhetes->get_result();

                if ($resultado_bilhetes && $resultado_bilhetes->num_rows > 0) {
                    $linha_bilhetes = $resultado_bilhetes->fetch_assoc();
                    $numero_bilhetes = $linha_bilhetes['total_bilhetes'];
                }

                $stmt_bilhetes->close();
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
    </style>

    <script src="main.js" defer></script>
</head>

<body>
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
                    <a href="consultar_rotas.php" class="nav-item nav-link">Rotas</a>
                    <a href="consultar_utilizadores.php" class="nav-item nav-link">Utilizadores</a>
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
                        </ul>
                    </div>

                    <!-- Dropdown dos Bilhetes -->
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link dropdown-toggle" id="ticketsDropdownLink" role="button" aria-expanded="false">
                            <i class="fa fa-ticket-alt me-2"></i> <?php echo $numero_bilhetes; ?>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="ticketsDropdownLink">
                            <li><a class="dropdown-item" href="consultar_bilhetes.php"><i class="fas fa-eye"></i>Consultar Bilhetes</a></li>
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

     <!-- Footer Start -->
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
    <!-- Footer End -->

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