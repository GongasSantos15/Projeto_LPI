<?php
    // Inicia a Sessão
    session_start();

    // Include conexão à BD
    include("../basedados/basedados.h");
    include("dados_navbar.php");

    // Verifica se o utilizador tem sessão iniciada, senão tiver redireciona para a página de login
    if (!isset($_SESSION['id_utilizador'])) {
        header("Location: entrar.php");
        exit();
    }   

    // Variável para armazenar mensagens de erro e sucesso PHP
    $mensagem_erro = '';
    $mensagem_sucesso = '';

    // Verifica se há uma mensagem de sucesso na sessão
    if (isset($_SESSION['mensagem_sucesso'])) {
        $mensagem_sucesso = $_SESSION['mensagem_sucesso'];
        unset($_SESSION['mensagem_sucesso']);
    }

    // Verifica se o utilizador tem o login feito   
    $tem_login = isset($_SESSION['id_utilizador']) && !empty($_SESSION['id_utilizador']); 
    $mostrar_alertas = false;
    $numero_alertas_cliente = 0;

    // Determina a página inicial correta baseada no tipo de utilizador
    $pagina_inicial = 'index.php';
    if ($tem_login && isset($_SESSION['tipo_utilizador'])) {
        switch ($_SESSION['tipo_utilizador']) {
            case 1: $pagina_inicial = 'pagina_inicial_admin.php'; break;
            case 2: $pagina_inicial = 'pagina_inicial_func.php'; break;
            case 3: $pagina_inicial = 'pagina_inicial_cliente.php'; break;
        }
    }

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
            // Para Visitante
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

    $id_utilizador = $_SESSION['id_utilizador'];
    $dados_utilizador = [];

    if ($conn) {
        // Consulta SQL para encontrar os dados do utilizador
        $sql = "SELECT nome_utilizador, nome_proprio FROM utilizador WHERE id = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("i", $id_utilizador);

            if($stmt->execute()) {
                $resultado = $stmt->get_result();
                if ($resultado && $resultado->num_rows > 0) {
                    $dados_utilizador = $resultado->fetch_assoc();
                }
            } else {
                $mensagem_erro = "Erro ao executar a consulta: " . $stmt->error;
            }

            $stmt->close();
        } else {
            $mensagem_erro = "Erro ao preparar a consulta: " . $conn->$connect_error;
        }
        
        $conn->close();
    } else {
        $mensagem_erro = "Erro na conexão à base de dados.";
    }

?>

<!------------------------------------------------------------------------------ COMEÇO DO HTML ------------------------------------------------------------------------------->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>FelixBus - Consultar e Editar Dados</title>
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
    <!-- RODA PARA O CARREGAMENTO DA PAGINA -->
    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
    
    <div class="container-fluid hero-header text-light min-vh-100 d-flex align-items-center justify-content-center">

        <!-- BARRA DE NAVEGAÇÃO -->
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
                    
                    <!-- Aba de Alertas - só aparece se houver alertas ou se for admin -->
                    <?php if ($mostrar_alertas): ?>
                        <a href="consultar_alertas.php" class="nav-item nav-link position-relative">
                            Alertas
                            <?php if ($numero_alertas_cliente > 0): ?>
                                <span class="alert-badge"><?php echo $numero_alertas_cliente; ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>

                    <!-- A aba dos Utilizadores só aparece ao administrador e a dos Bilhetes aparece ao administrador e ao funcionario -->
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
                     <!-- Dropdown da Carteira (Contém o valor da carteira e as opções de Adicionar, Remover e Consulta Clientes (admin e funcionario)) -->
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

                    <?php if($_SESSION['tipo_utilizador'] == 3): ?>
                        <!-- Dropdown dos Bilhetes (Só aparece ao Cliente) -->
                        <div class="nav-item dropdown">
                            <a href="#" class="nav-link dropdown-toggle" id="ticketsDropdownLink" role="button" aria-expanded="false">
                                <i class="fa fa-ticket-alt me-2"></i> <?php echo $numero_bilhetes; ?>
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="ticketsDropdownLink">
                                <li><a class="dropdown-item" href="consultar_bilhetes.php"><i class="fas fa-eye"></i>Consultar Bilhetes</a></li>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <!-- Dropdown do Utilizador (Contém o nome do utilizador e as opções de Logout e Consultar Dados) -->
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
        
        <!-- Container Principal -->
        <div class="rounded shadow" style="max-width: 1200px; width: 100%; margin-top: 150px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="text-white m-0">Consultar e Editar Dados</h3>
            </div>

            <!-- Div mensagens -->
            <?php
                if (!empty($mensagem_erro)) {
                    echo '<div class="alert alert-danger">' . htmlspecialchars($mensagem_erro) . '</div>';
                }
                if (!empty($mensagem_sucesso)) {
                    echo '<div class="alert alert-success">' . htmlspecialchars($mensagem_sucesso) . '</div>';
                }
            ?>

            <!-- Daodos do Utilizador -->
            <?php if (!empty($dados_utilizador)): ?>
                <div class="row g-3">
                    <div class="col-12">
                        <div class="bg-gradient mb-3 position-relative mx-auto mt-3 animated slideInDown">
                            <div class="card-body p-4">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <div class="d-flex align-items-center mb-3">
                                            <i class="fas fa-user text-primary me-2 fa-lg"></i>
                                            <h5 class="card-title text-primary mb-0">Dados do Utilizador</h5>
                                        </div>
                                        
                                        <div>
                                            <p class="card-text mb-2 text-white"><strong><i class="fas fa-id-card text-info me-1"></i>Nome Próprio:</strong> <?php echo htmlspecialchars($dados_utilizador['nome_proprio']); ?></p>
                                            <p class="card-text mb-2 text-white"><strong><i class="fas fa-user-tag text-warning me-1"></i>Nome de Utilizador:</strong> <?php echo htmlspecialchars($dados_utilizador['nome_utilizador']); ?></p>
                                            <p class="card-text mb-2 text-white"><strong><i class="fas fa-key text-danger me-1"></i>Palavra Passe:</strong></p>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 text-md-end">
                                        <button type="button" id="botao-edicao" class="btn btn-warning rounded-pill py-2 px-4">
                                            <i class="fas fa-edit me-2"></i>Editar Dados
                                        </button>
                                    </div>
                                </div>

                                <!-- Formulário de edição (inicialmente oculto) -->
                                <div id="formulario-edicao" style="display: none;">
                                    <hr class="text-white my-4">
                                    <form id="profileEditForm" action="editar_dados.php" method="POST">
                                         <!-- Formulário com os dados do utilizador a alterar -->
                                        <input type="hidden" name="id_utilizador" value="<?php echo htmlspecialchars($id_utilizador); ?>">
                                        
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="mb-3">
                                                    <label for="nome-proprio" class="form-label text-white">
                                                        <i class="fas fa-id-card me-1"></i>Nome Próprio:
                                                    </label>
                                                    <input name="nome-proprio" id="nome-proprio" type="text" 
                                                           class="text-dark form-control" 
                                                           value="<?php echo htmlspecialchars($dados_utilizador['nome_proprio']); ?>" 
                                                           required />
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="mb-3">
                                                    <label for="nome" class="form-label text-white">
                                                        <i class="fas fa-user-tag me-1"></i>Nome de Utilizador:
                                                    </label>
                                                    <input name="nome" id="nome" type="text" 
                                                           class="text-dark form-control" 
                                                           value="<?php echo htmlspecialchars($dados_utilizador['nome_utilizador']); ?>" 
                                                           required />
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="mb-3">
                                                    <label for="nome" class="form-label text-white">
                                                        <i class="fas fa-key me-1"></i>Palavra Passe:
                                                    </label>
                                                    <input name="palavra_passe" id="palavra_passe" type="text" 
                                                           class="text-dark form-control" 
                                                           value="" 
                                                           required />
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex justify-content-end gap-2 mt-3">
                                            <button type="button" id="botao-cancelar" class="btn btn-outline-danger rounded-pill">
                                                <i class="fas fa-times me-2"></i>Cancelar
                                            </button>
                                            <button type="submit" class="btn btn-success rounded-pill">
                                                <i class="fas fa-save me-2"></i>Guardar Alterações
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php else: ?>
                <div class="text-center">
                    <div class="mb-4">
                        <i class="fas fa-user-times text-light" style="font-size: 4rem; opacity: 0.5;"></i>
                    </div>
                    <p class="text-center text-white fs-5 mb-4">Não foi possível carregar os seus dados.</p>
                </div>
            <?php endif; ?>
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

    <!-- Scripts JS -->
    <script>
        $(document).ready(function() {
            // Botão de edição
            $('#botao-edicao').click(function() {
                $('#formulario-edicao').slideDown();
                $(this).hide();
            });

            // Botão de cancelar
            $('#botao-cancelar').click(function() {
                $('#formulario-edicao').slideUp();
                $('#botao-edicao').show();
                
                // Restaurar valores originais
                $('#nome-proprio').val('<?php echo htmlspecialchars($dados_utilizador['nome_proprio'] ?? ''); ?>');
                $('#nome').val('<?php echo htmlspecialchars($dados_utilizador['nome_utilizador'] ?? ''); ?>');
            });
        });
    </script>
</body>

</html>