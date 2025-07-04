<?php
    // Inicia a Sessão
    session_start();

    // Includes
    include("../basedados/basedados.h");
    include("dados_navbar.php");
    include("const_utilizadores.php");

    // Se o utilizador não estiver autenticado, redireciona para a página de login
    if (!isset($_SESSION['id_utilizador'])  || $_SESSION['tipo_utilizador'] == 3) {
        header("Location: entrar.php");
        exit();
    }   

    // Verifica se o utilizador tem o login feito   
    $tem_login = isset($_SESSION['id_utilizador']) && !empty($_SESSION['id_utilizador']); 
    $mostrar_alertas = false;
    $numero_alertas = 0;

    $mensagem_erro = '';
    $mensagem_sucesso = '';

    if (isset($_SESSION['mensagem_sucesso'])) {
        $mensagem_sucesso = $_SESSION['mensagem_sucesso'];
        unset($_SESSION['mensagem_sucesso']);
    }

    // Determina a página inicial correta baseada no tipo de utilizador
    $pagina_inicial = 'index.php';
    if ($tem_login && isset($_SESSION['tipo_utilizador'])) {
        switch ($_SESSION['tipo_utilizador']) {
            case 1: $pagina_inicial = 'pagina_inicial_admin.php'; break;
            case 2: $pagina_inicial = 'pagina_inicial_func.php'; break;
            case 3: $pagina_inicial = 'pagina_inicial_cliente.php'; break;
        }
    }  

    // Inicializa o array de clientes para armazenar os dados
    $clientes = [];

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

        // Consulta para obter os dados dos clientes (tipo_utilizador = 3)
        $sql = "SELECT
                    u.id AS id_utilizador,
                    u.nome_proprio,
                    u.nome_utilizador,
                    c.id_carteira,
                    c.saldo
                FROM
                    utilizador u
                LEFT JOIN
                    carteira c ON u.id = c.id_carteira
                WHERE
                    u.tipo_utilizador = 3
                ORDER BY
                    u.nome_proprio ASC";

        $stmt = $conn->prepare($sql);

        if ($stmt) {
            if($stmt->execute()) {
                $resultado = $stmt->get_result();
                $clientes = $resultado->fetch_all(MYSQLI_ASSOC); 
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
<html lang="pt">

<head>
    <meta charset="utf-8">
    <title>FelixBus - Gestão de Carteiras</title>
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
            <a href="<?php echo htmlspecialchars($pagina_inicial) ?>" class="navbar-brand p-0">
                <!-- Voltar para a página inicial de acordo com o tipo de utilizador -->
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
                <h3 class="text-white m-0">Gestão da Carteira dos Clientes</h3>
            </div>

            <!-- Div Mensagens -->
            <?php if (!empty($mensagem_erro)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($mensagem_erro); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($mensagem_sucesso)): ?>
                <div class="alert alert-success" id="mensagem-sucesso">
                    <?php echo htmlspecialchars($mensagem_sucesso); ?>
                </div>
                <script>
                    // Esconde a mensagem de sucesso após 3 segundos
                    setTimeout(function() {
                        document.getElementById('mensagem-sucesso').style.display = 'none';
                    }, 3000);
                </script>
            <?php endif; ?>

            <!-- Zona onde é apresentado o saldo de todos os clientes -->
            <?php if (!empty($clientes)): ?>
                <div class="row g-3">
                    <?php foreach ($clientes as $cliente): ?>
                        <div class="col-12">
                            <div class="bg-gradient mb-3 position-relative mx-auto mt-3 animated slideInDown">
                                <div class="card-body p-4">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <div class="d-flex align-items-center mb-4">
                                                <i class="fas fa-wallet text-primary me-2 fa-lg"></i>
                                                <h5 class="card-title text-primary mb-0">
                                                    <?php echo htmlspecialchars($cliente['nome_proprio']); ?>(@cliente) 
                                                </h5>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <p class="card-text mb-1"><strong><i class="fas fa-user text-info me-1"></i>ID Cliente:</strong> #<?php echo htmlspecialchars($cliente['id_utilizador']); ?></p>
                                                    <p class="card-text mb-1"><strong><i class="fas fa-credit-card text-warning me-1"></i>ID Carteira:</strong> #<?php echo htmlspecialchars($cliente['id_carteira'] ?? 'N/A'); ?></p>
                                                </div>
                                                <div class="col-sm-6">
                                                    <p class="card-text mb-1">
                                                        <strong><i class="fas fa-coins me-1"></i>Saldo Atual:</strong>
                                                        <span class="badge bg-<?php echo ($cliente['saldo'] ?? 0) > 0 ? 'success' : 'secondary'; ?> fs-6">
                                                            <?php echo number_format($cliente['saldo'] ?? 0, 2, ',', '.'); ?> €
                                                        </span>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-4 text-md-end">
                                            <div class="mb-3">
                                                <button type="button" class="btn btn-warning rounded-pill py-2 px-4 botao-edicao" data-cliente-id="<?php echo $cliente['id_utilizador']; ?>">
                                                    <i class="fas fa-edit me-2"></i>Editar Saldo
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Formulário de edição (inicialmente oculto) -->
                                    <div id="formulario-edicao-<?php echo $cliente['id_utilizador']; ?>" class="formulario-edicao" style="display: none;">
                                        <hr class="text-white my-4">
                                        <form action="editar_saldo_clientes.php" method="POST">
                                            <input type="hidden" name="id_utilizador" value="<?php echo htmlspecialchars($cliente['id_utilizador']); ?>">
                                            
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="mb-3">
                                                        <label class="form-label text-white">
                                                            <i class="fas fa-euro-sign text-success me-1"></i>Novo Saldo:
                                                        </label>
                                                        <div class="input-group">
                                                            <input type="number" 
                                                                   name="novo_saldo" 
                                                                   class="form-control bg-dark text-light border-primary" 
                                                                   value="<?php echo htmlspecialchars($cliente['saldo'] ?? 0); ?>" 
                                                                   step="0.01" 
                                                                   min="0" 
                                                                   required>
                                                            <span class="input-group-text bg-primary text-white">€</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="d-flex justify-content-end gap-2 mt-3">
                                                <button type="button" class="btn btn-outline-danger rounded-pill botao-cancelar" data-cliente-id="<?php echo $cliente['id_utilizador']; ?>">
                                                    <i class="fas fa-times me-2"></i>Cancelar
                                                </button>
                                                <button type="submit" class="btn btn-success rounded-pill">
                                                    <i class="fas fa-save me-2"></i>Atualizar Saldo
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
            <?php else: ?>
                <div class="text-center">
                    <div class="mb-4">
                        <i class="fas fa-users text-light" style="font-size: 4rem; opacity: 0.5;"></i>
                    </div>
                    <p class="text-center text-white fs-5 mb-4">Nenhum cliente encontrado.</p>
                </div>
            <?php endif; ?>
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

    <!-- Bibliotecas JavaScript -->
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
    
    <!-- Código JS -->
    <script>
        $(document).ready(function() {
            // Botão de edição - para cada cliente
            $('.botao-edicao').click(function() {
                var clienteId = $(this).data('cliente-id');
                $('#formulario-edicao-' + clienteId).slideDown();
                $(this).hide();
            });

            // Botão de cancelar - para cada cliente
            $(document).on('click', '.botao-cancelar', function() {
                var clienteId = $(this).data('cliente-id');
                $('#formulario-edicao-' + clienteId).slideUp();
                $('.botao-edicao[data-cliente-id="' + clienteId + '"]').show();
            });
            
            // Confirmação antes de submeter
            $('form').submit(function(e) {
                var novoSaldo = $(this).find('input[name="novo_saldo"]').val();
                var nomeCliente = $(this).closest('.card-body').find('.card-title').text().trim();
                
                if (!confirm('Tem certeza que deseja alterar o saldo de ' + nomeCliente + ' para ' + novoSaldo + '€?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>