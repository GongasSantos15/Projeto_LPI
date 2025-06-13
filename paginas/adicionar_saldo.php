<?php
    
    // Inicia a sessão
    session_start();

    // Includes
    include 'C:\xampp\htdocs\lpi\Projeto_LPI\basedados\basedados.h';
    include 'const_utilizadores.php';
    include 'dados_navbar.php';

    // Verifica se o user já iniciou sessão, senão redireciona para a página de login
    if (!isset($_SESSION['id_utilizador'])) {
        header('Location: entrar.php');
        exit();
    }

    // Obtém o ID, tipo e nome do utilizador
    $id_utilizador = $_SESSION['id_utilizador'];
    $tipo_utilizador = $_SESSION['tipo_utilizador'];
    $nome_utilizador = $_SESSION['nome_utilizador'];


    // Variáveis de mensagens que vão ser apresentadas ao utilizador
    $mensagem_erro = '';
    $mensagem_sucesso = '';

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
        if ($tem_login && $_SESSION['tipo_utilizador'] == CLIENTE) {
            $sql_contagem = "SELECT COUNT(*) as total 
                         FROM alerta a
                         JOIN utilizador_alerta ua ON a.id_alerta = ua.id_alerta
                         WHERE ua.id_utilizador = ? AND a.estado = 1";
            
            // Prepara a consulta para evitar SQL Injection, utilizando prepared statements para maior segurança
            // Executa a consulta SQL que verifica o número de alertas para o cliente
            // E mostra os alertas se a consulta SQL retornar maior que 0
            $stmt_contagem = $conn->prepare($sql_contagem);
            if ($stmt_contagem) {
                $stmt_contagem->bind_param("i", $_SESSION['id_utilizador']);
                if ($stmt_contagem->execute()) {
                    $resultado_contagem = $stmt_contagem->get_result();
                    $linha_contagem = $resultado_contagem->fetch_assoc();
                    $numero_alertas = $linha_contagem['total'];
                    $mostrar_alertas = $numero_alertas > 0;
                }
                $stmt_contagem->close();
            }
        } else if (!$tem_login) {
            // Para Visitantes (tipo_utilizador == 4)
            $sql_contagem = "SELECT COUNT(*) as total 
                         FROM alerta a
                         JOIN utilizador_alerta ua ON a.id_alerta = ua.id_alerta
                         WHERE ua.id_utilizador = ? AND a.estado = 1";

            // Prepara a consulta para evitar SQL Injection, utilizando prepared statements para maior segurança
            // Executa a consulta SQL que verifica o número de alertas para o visitante
            // E mostra os alertas se a consulta SQL retornar maior que 0
            $stmt_contagem = $conn->prepare($sql_contagem);

            if ($stmt_contagem) {
                $id_visitante = VISITANTE;
                $stmt_contagem->bind_param("i", $id_visitante);
                if ($stmt_contagem->execute()) {
                    $resultado_contagem = $stmt_contagem->get_result();
                    $linha_contagem = $resultado_contagem->fetch_assoc();
                    $numero_alertas = $linha_contagem['total'];
                    $mostrar_alertas = $numero_alertas > 0;
                }
            }
        }
    }

    // Processa a submissão do formulário para adicionar saldo (Processar POST)
    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        // Obtém o valor que o utilizador quer adicionar através do método POST e valida-o como número decimal
        $valor = filter_input(INPUT_POST, 'valor', FILTER_VALIDATE_FLOAT);

        // Verifica se o valor que o utilizador quer adicionar se é um número válido e positivo e exibe uma mensage de erro ao utilizador caso não seja
        if ($valor === false || $valor <= 0) {
            $mensagem_erro = 'Por favor, insira um valor positivo válido.';
        } else {
            if ($conn) {
                // 1. Selecionar o utilizador com a carteira
                $sql = "SELECT id_carteira FROM utilizador WHERE id = ?";
                $stmt = $conn->prepare($sql);

                if($stmt) {
                    $stmt->bind_param("i", $id_utilizador);
                    $stmt->execute();
                    $resultado = $stmt->get_result();

                    if ($resultado && $resultado->num_rows > 0) {
                        $linha = $resultado->fetch_assoc();
                        $id_carteira = $linha['id_carteira'];

                        // 2. Selecionar o valor da carteira correspondente ao utilizador
                        $sql_carteira = "UPDATE carteira SET saldo = saldo + ? WHERE id_carteira = ?";
                        $stmt_carteira = $conn->prepare($sql_carteira);

                        if ($stmt_carteira) {
                            $stmt_carteira->bind_param("di", $valor, $id_carteira);
                            $stmt_carteira->execute();

                            if ($stmt_carteira->affected_rows > 0) {
                                $mensagem_sucesso = 'Saldo atualizado com sucesso!';

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
                                $mensagem_erro = 'Erro ao atualizar o saldo.';
                            }

                            $stmt_carteira->close();
                        } else {
                            $mensagem_erro = 'Erro ao preparar a query de atualização do saldo.';
                        }
                    } else {
                        $mensagem_erro = 'Utilizador não encontrado.';
                    }
                    $stmt->close();
                } else {
                    $mensagem_erro = 'Erro ao preparar a query para obter a carteira.';
                }
                $conn->close();

            } else {
                $mensagem_erro = 'Erro: Falha na conexão com a base de dados.';
            }
        }
    }
?>

<!------------------------------------------------------------------------------ COMEÇO DO HTML ------------------------------------------------------------------------------->
<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="utf-8">
        <title>FelixBus - Adicionar Saldo</title>
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
                <!-- Voltar para a página inicial de acordo com o tipo de utilizador -->
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
                        
                        <!-- Link de Alertas - só aparece se houver alertas -->
                        <?php if ($mostrar_alertas || $_SESSION['tipo_utilizador'] == 1): ?>
                            <a href="consultar_alertas.php" class="nav-item nav-link position-relative">
                                Alertas
                                <?php if ($numero_alertas > 0): ?>
                                    <span class="alert-badge"><?php echo $numero_alertas; ?></span>
                                <?php endif; ?>
                            </a>
                        <?php endif; ?>

                        <!-- Só aparece estas abas se o utilizador tiver login, for admin (utilizadores) ou admin e funcionario (bilhetes) -->
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
                                <?php if ($_SESSION['tipo_utilizador'] != 2): ?>
                                    <li><a class="dropdown-item" href="adicionar_saldo.php"><i class="fas fa-plus-circle"></i>Adicionar</a></li>
                                    <li><a class="dropdown-item" href="remover_saldo.php"><i class="fas fa-minus-circle"></i>Remover</a></li>
                                <?php endif; ?>

                                <!-- Opção de Consulta de Clientes só aparece ao admin e ao funcionario -->
                                <?php if(in_array($_SESSION['tipo_utilizador'], [1,2])): ?>
                                    <li><a class="dropdown-item" href="consultar_saldo_clientes.php"><i class="fas fa-user"></i>Consulta Clientes</a></li>
                                <?php endif; ?>

                            </ul>
                        </div>

                        <!-- Submenu dos Bilhetes -->
                        <?php if ($_SESSION['tipo_utilizador'] == 3): ?>
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

            <!-- Container Principal -->
            <div class="rounded shadow" style="max-width: 1200px; width: 100%; margin-top: 150px;">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="text-white m-0">Adicionar Saldo</h3>
                </div>    

                <!-- Div Mensagens -->
                <?php
                    if (!empty($mensagem_erro)) {
                        echo '<div class="alert alert-danger">' . htmlspecialchars($mensagem_erro) . '</div>';
                    }
                    if (!empty($mensagem_sucesso)) {
                        // Redireciona para a página inicial após 2 segundos
                        echo '<div class="alert alert-success">' . htmlspecialchars($mensagem_sucesso) . '</div>';
                        echo '<script>
                            setTimeout(function() {
                                window.location.href = $pagina_inicial;
                            }, 2000);
                        </script>';
                    }
                ?>

                <div class="bg-gradient mb-3 p-5 position-relative mx-auto mt-3 animated slideInDown">
                    <!-- Formulário com método POST que envia os dados para o adicionar_saldo (esta página) -->
                    <form action="adicionar_saldo.php" method="POST">
                        <!-- Formulário com opção para inserir um saldo para ser somado sobre o valor já existente -->
                        <div class="mb-3">
                            <label for="valor" class="form-label">Quanto dinheiro (€) pretende adicionar?</label>
                            <input name="valor" id="valor" type="number" step="0.01" min="0.01" class="form-control text-dark" required/>
                        </div>
                        <div class="d-flex justify-content-center">
                            <input type="submit" value="Adicionar Saldo" class="btn btn-success rounded-pill py-2 px-5">
                        </div>
                    </form>
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