<?php
    session_start();

    // Include conexão à BD
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
        unset($_SESSION['mensagem_erro']);
    }

    // Verifica se há uma mensagem de sucesso na sessão
    if (isset($_SESSION['mensagem_sucesso'])) {
        $mensagem_sucesso = $_SESSION['mensagem_sucesso'];
        unset($_SESSION['mensagem_sucesso']);
    }

    // Parâmetros de pesquisa e ordenação
    $pesquisa = isset($_GET['pesquisa']) ? trim($_GET['pesquisa']) : '';
    $ordenacao = isset($_GET['ordenacao']) ? $_GET['ordenacao'] : 'id_asc';

    $alertas = []; // Inicializa a variável alertas
    $numero_alertas_cliente = 0; // Contador de alertas para cliente

    if ($conn) {
        // Para clientes logados (tipo_utilizador == 3)
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
                }
                $stmt_count->close();
            }
        } else if (!$tem_login) {
            $sql_count = "SELECT COUNT(*) as total FROM utilizador_alerta WHERE id_utilizador = 4";
            $result = $conn->query($sql_count);
            if ($result) {
                $row = $result->fetch_assoc();
                $numero_alertas_cliente = $row['total'];
            }
        }

        // Constrói a consulta SQL baseada no tipo de utilizador
        if ($tem_login && $_SESSION['tipo_utilizador'] == 3) {
            // Consulta para clientes (apenas seus alertas ativos)
            $sql = "SELECT a.id_alerta, a.descricao, a.estado, ua.data_hora, u.nome_utilizador as nome_utilizador, u.id as id_utilizador 
                    FROM alerta a
                    JOIN utilizador_alerta ua ON a.id_alerta = ua.id_alerta
                    JOIN utilizador u ON ua.id_utilizador = u.id
                    WHERE ua.id_utilizador = ? AND a.estado = 1";
            
            if (!empty($pesquisa)) {
                $sql .= " AND (a.descricao LIKE ? OR a.id_alerta = ?)";
            }
        } else {
            // Consulta para admin/funcionários (todos os alertas)
            $sql = "SELECT a.id_alerta, a.descricao, a.estado, ua.data_hora, u.nome_utilizador as nome_utilizador, u.id as id_utilizador 
                    FROM alerta a
                    JOIN utilizador_alerta ua ON a.id_alerta = ua.id_alerta
                    JOIN utilizador u ON ua.id_utilizador = u.id";
            
            if (!empty($pesquisa)) {
                $sql .= " WHERE (a.descricao LIKE ? OR u.nome_utilizador LIKE ? OR a.id_alerta = ?)";
            }
        }
        
        // Adiciona ordenação
        switch ($ordenacao) {
            case 'id_asc':
                $sql .= " ORDER BY a.id_alerta ASC";
                break;
            case 'id_desc':
                $sql .= " ORDER BY a.id_alerta DESC";
                break;
            case 'data_asc':
                $sql .= " ORDER BY ua.data_hora ASC";
                break;
            case 'data_desc':
                $sql .= " ORDER BY ua.data_hora DESC";
                break;
            default:
                $sql .= " ORDER BY a.id_alerta ASC";
        }

        $stmt = $conn->prepare($sql);

        if ($stmt) {
            // Faz bind dos parâmetros
            if ($tem_login && $_SESSION['tipo_utilizador'] == 3) {
                // Para clientes
                if (!empty($pesquisa)) {
                    $termo_pesquisa = "%$pesquisa%";
                    $stmt->bind_param("isi", $_SESSION['id_utilizador'], $termo_pesquisa, $pesquisa);
                } else {
                    $stmt->bind_param("i", $_SESSION['id_utilizador']);
                }
            } else {
                // Para admin e funcionários
                if (!empty($pesquisa)) {
                    $termo_pesquisa = "%$pesquisa%";
                    $stmt->bind_param("ssi", $termo_pesquisa, $termo_pesquisa, $pesquisa);
                }
            }
            
            if($stmt->execute()) {
                $resultado = $stmt->get_result();
                $alertas = $resultado->fetch_all(MYSQLI_ASSOC);
            } else {
                $mensagem_erro = "Erro ao executar a consulta dos alertas: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $mensagem_erro = "Erro ao preparar a consulta dos alertas: " . $conn->$connect_error;
        }
    
        if ($conn) {
            $conn->close();
        }
    } else {
        $mensagem_erro = "Erro na conexão à base de dados";
    }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>FelixBus - Alertas</title>
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
        /* CSS personalizado para scroll nos alertas */
        .alertas-scroll-container {
            max-height: 400px;
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 10px;
        }
        
        /* Personalizar a scrollbar */
        .alertas-scroll-container::-webkit-scrollbar {
            width: 8px;
        }
        
        .alertas-scroll-container::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }
        
        .alertas-scroll-container::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 10px;
        }
        
        .alertas-scroll-container::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }
        
        /* Para Firefox */
        .alertas-scroll-container {
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.3) rgba(255, 255, 255, 0.1);
        }

        /* Estilos para os filtros */
        .filtros-container {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            backdrop-filter: blur(10px);
        }

        .search-input, .filtro-select {
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid transparent;
            border-radius: 50px;
            padding: 12px 20px;
            color: #333;
            transition: all 0.3s ease;
            height: 48px;
        }

        .search-input:focus, .filtro-select:focus {
            background: white;
            border-color: #007bff;
            box-shadow: 0 0 20px rgba(0, 123, 255, 0.3);
            outline: none;
        }

        .btn-limpar {
            background: rgba(220, 53, 69, 0.8);
            border: none;
            border-radius: 25px;
            padding: 10px 20px;
            color: white;
            transition: all 0.3s ease;
        }

        .btn-limpar:hover {
            background: rgba(220, 53, 69, 1);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.4);
        }

        .contador-resultados {
            background: rgba(40, 167, 69, 0.8);
            border-radius: 20px;
            padding: 8px 16px;
            color: white;
            font-weight: 500;
        }
        
        .alerta-card {
            border-left: 4px solid #ffc107;
            transition: all 0.3s ease;
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

        .alerta-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .badge-estado {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
        }

        .estado-ativo { background-color: #28a745; }
        .estado-anulado { background-color: #dc3545; }

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
    <div class="container-fluid hero-header text-light min-vh-100 d-flex align-items-center justify-content-center">
        
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
                    
                    <!-- Link de Alertas com contador -->
                    <?php if ($tem_login && $_SESSION['tipo_utilizador'] == 3 || !$tem_login): ?>
                        <a href="consultar_alertas.php" class="nav-item nav-link position-relative active">
                            Alertas
                            <?php if (isset($numero_alertas_cliente) && $numero_alertas_cliente > 0): ?>
                                <span class="alert-badge"><?php echo $numero_alertas_cliente; ?></span>
                            <?php endif; ?>
                        </a>
                    <?php else: ?>
                        <a href="consultar_alertas.php" class="nav-item nav-link">Alertas</a>
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
                            <li><a class="dropdown-item" href="adicionar_saldo.php"><i class="fas fa-plus-circle"></i>Adicionar</a></li>
                            <li><a class="dropdown-item" href="remover_saldo.php"><i class="fas fa-minus-circle"></i>Remover</a></li>

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

        <div class="rounded shadow" style="max-width: 1200px; width: 100%; margin-top: 150px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <?php if ($tem_login && $_SESSION['tipo_utilizador'] == 3): ?>
                    <h3 class="text-white m-0">Os Seus Alertas</h3>
                <?php else: ?>
                    <h3 class="text-white m-0">Consultar Alertas</h3>
                <?php endif; ?>
            </div>
            
            <?php if (isset($_SESSION['tipo_utilizador']) && $_SESSION['tipo_utilizador'] == 1): ?>
                <div class="text-center my-5">
                    <h5 class="text-white">Pretende adicionar um novo alerta? <a href="adicionar_alerta.php" class="text-info"> Clique aqui</a></h5>
                </div>
            <?php endif; ?>

            <?php if (!empty($mensagem_erro)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($mensagem_erro); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($mensagem_sucesso)): ?>
                <div class="alert alert-success" id="mensagem-sucesso">
                    <?php echo htmlspecialchars($mensagem_sucesso); ?>
                </div>
                <script>
                    // Esconde a mensagem de sucesso após 2 segundos
                    setTimeout(function() {
                        document.getElementById('mensagem-sucesso').style.display = 'none';
                    }, 2000);
                </script>
            <?php endif; ?>

            <!-- Filtros de Pesquisa e Ordenação -->
            <?php if (isset($_SESSION['tipo_utilizador']) && $_SESSION['tipo_utilizador'] == 1): ?>
                <div class="filtros-container">
                    <form method="GET" action="" class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label text-white mb-2">
                                <i class="fas fa-search me-2"></i>Pesquisar por Descrição<?php echo ($tem_login && $_SESSION['tipo_utilizador'] != 3) ? ', Utilizador' : ''; ?> ou ID:
                            </label>
                            <input type="text" 
                                name="pesquisa" 
                                class="form-control search-input" 
                                placeholder="Digite o termo de pesquisa..." 
                                value="<?php echo htmlspecialchars($pesquisa); ?>">
                        </div>
                        
                        <div class="col-md-5">
                            <label class="form-label text-white mb-2">
                                <i class="fas fa-sort me-2"></i>Ordenar por:
                            </label>
                            <select name="ordenacao" class="form-select filtro-select">
                                <option value="id_asc" <?php echo ($ordenacao == 'id_asc') ? 'selected' : ''; ?>>ID (Crescente)</option>
                                <option value="id_desc" <?php echo ($ordenacao == 'id_desc') ? 'selected' : ''; ?>>ID (Decrescente)</option>
                                <option value="data_asc" <?php echo ($ordenacao == 'data_asc') ? 'selected' : ''; ?>>Data (Mais Antigos)</option>
                                <option value="data_desc" <?php echo ($ordenacao == 'data_desc') ? 'selected' : ''; ?>>Data (Mais Recentes)</option>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <div class="d-flex justify-content-end">
                                <a href="?" class="btn btn-limpar rounded-pill">
                                    <i class="fas fa-times me-1"></i>Limpar
                                </a>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Contador de resultados -->
                    <div class="mt-3 d-flex justify-content-between align-items-center">
                        <div class="contador-resultados">
                            <i class="fas fa-bell me-2"></i>
                            <?php echo count($alertas); ?> alerta(s) encontrado(s)
                            <?php if (!empty($pesquisa)): ?>
                                para "<?php echo htmlspecialchars($pesquisa); ?>"
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($pesquisa)): ?>
                            <small class="text-light">
                                <i class="fas fa-info-circle me-1"></i>
                                Pesquisando por: <strong><?php echo htmlspecialchars($pesquisa); ?></strong>
                            </small>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($alertas)): ?>
                <!-- Container com scroll aplicado apenas aos alertas -->
                <div class="alertas-scroll-container">
                    <div class="row g-3">
                        <?php foreach ($alertas as $alerta): ?>
                            <div class="col-12">
                                <div class="bg-gradient position-relative mx-auto mt-3 animated slideInDown 
                                    <?php echo ($tem_login && $_SESSION['tipo_utilizador'] == 3) ? 
                                        'alerta-card-cliente alerta-cliente-destaque' : 'alerta-card'; ?>">
                                    <div class="card-body p-4">
                                        <div class="row align-items-center">
                                            <div class="col-md-8">
                                                <div class="d-flex align-items-center justify-content-between mb-3">
                                                    <div class="d-flex align-items-center mb-3">
                                                        <?php if ($tem_login && $_SESSION['tipo_utilizador'] == 3): ?>
                                                            <i class="fas fa-bell text-primary me-2 fa-lg"></i>
                                                            <h5 class="card-title text-primary mb-0">Notificação #<?php echo htmlspecialchars($alerta['id_alerta']); ?></h5>
                                                        <?php else: ?>
                                                            <i class="fas fa-exclamation-triangle text-warning me-2 fa-lg"></i>
                                                            <h5 class="card-title text-warning mb-0">Alerta #<?php echo htmlspecialchars($alerta['id_alerta']); ?></h5>
                                                        <?php endif; ?>
                                                    </div>
                                                    <span class="badge badge-estado estado-<?php echo ($alerta['estado'] == 1) ? 'ativo' : 'anulado'; ?>">
                                                        <?php echo ($alerta['estado'] == 1) ? 'Ativo' : 'Anulado'; ?>
                                                    </span>
                                                </div>
                                                <div class="row">
                                                    <div class="col-sm-12 mb-2">
                                                        <p class="card-text mb-1">
                                                            <strong><i class="fas fa-comment me-1"></i>
                                                            <?php echo ($tem_login && $_SESSION['tipo_utilizador'] == 3) ? 'Mensagem:' : 'Descrição:'; ?>
                                                            </strong> 
                                                            <?php echo htmlspecialchars($alerta['descricao']); ?>
                                                        </p>
                                                    </div>
                                                    <?php if ($tem_login && $_SESSION['tipo_utilizador'] != 3): ?>
                                                    <div class="col-sm-6">
                                                        <p class="card-text mb-1"><strong><i class="fas fa-user me-1"></i>Utilizador:</strong> <?php echo htmlspecialchars($alerta['nome_utilizador']); ?> (ID: <?php echo htmlspecialchars($alerta['id_utilizador']); ?>)</p>
                                                    </div>
                                                    <?php endif; ?>
                                                    <div class="col-sm-6">
                                                        <p class="card-text mb-1"><strong><i class="fas fa-clock me-1"></i>Data/Hora:</strong> <?php echo date('d/m/Y H:i', strtotime($alerta['data_hora'])); ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <?php if (isset($_SESSION['tipo_utilizador']) && $_SESSION['tipo_utilizador'] == 1): ?>
                                            <div class="col-md-4 text-md-end">
                                                <div class="d-flex flex-column gap-2">
                                                    <button type="button" class="btn btn-warning rounded-pill py-2 px-4 botao-edicao" data-alerta-id="<?php echo $alerta['id_alerta']; ?>">
                                                        <i class="fas fa-edit me-2"></i>Editar
                                                    </button>
                                                    <button type="button" class="btn btn-danger rounded-pill py-2 px-4 botao-eliminar" data-alerta-id="<?php echo $alerta['id_alerta']; ?>">
                                                        <i class="fas fa-trash me-2"></i>Eliminar
                                                    </button>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if (isset($_SESSION['tipo_utilizador']) && $_SESSION['tipo_utilizador'] == 1): ?>
                                        <!-- Formulário de edição (inicialmente oculto) -->
                                        <div id="formulario-edicao-<?php echo $alerta['id_alerta']; ?>" class="formulario-edicao" style="display: none;">
                                            <hr class="text-white my-4">
                                            <form action="editar_alerta.php" method="POST">
                                                <input type="hidden" name="id_alerta" value="<?php echo htmlspecialchars($alerta['id_alerta']); ?>">
                                                <input type="hidden" name="id_utilizador" value="<?php echo htmlspecialchars($alerta['id_utilizador']); ?>">
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <div class="mb-3">
                                                            <label class="form-label text-white">
                                                                <i class="fas fa-comment me-1"></i>Descrição:
                                                            </label>
                                                            <div class="view-mode">
                                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($alerta['descricao']); ?>" readonly>
                                                            </div>
                                                            <div class="edit-mode" style="display: none;">
                                                                <textarea name="descricao" class="form-control bg-dark text-light border-primary" rows="2" required><?php echo htmlspecialchars($alerta['descricao']); ?></textarea>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-end gap-2 mt-3">
                                                    <button type="button" class="btn btn-outline-danger rounded-pill botao-cancelar" data-alerta-id="<?php echo $alerta['id_alerta']; ?>">
                                                        <i class="fas fa-times me-2"></i>Cancelar
                                                    </button>
                                                    <button type="submit" class="btn btn-success rounded-pill">
                                                        <i class="fas fa-save me-2"></i>Guardar Alterações
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="text-center">
                    <div class="mb-4">
                        <i class="fas fa-bell-slash text-light" style="font-size: 4rem; opacity: 0.5;"></i>
                    </div>
                    <?php if (!empty($pesquisa)): ?>
                        <p class="text-center text-white fs-5 mb-3">Nenhum alerta encontrado para "<?php echo htmlspecialchars($pesquisa); ?>"</p>
                        <p class="text-center text-light mb-4">Tente pesquisar por outros termos ou <a href="?" class="text-info">limpar os filtros</a>.</p>
                    <?php else: ?>
                        <p class="text-center text-white fs-5 mb-4">Nenhum alerta encontrado.</p>
                    <?php endif; ?>
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

    <?php if (isset($_SESSION['tipo_utilizador']) && $_SESSION['tipo_utilizador'] == 1): ?>
        <script>
            $(document).ready(function() {
                // Botão de edição - para cada alerta
                $('.botao-edicao').click(function() {
                    var alertaId = $(this).data('alerta-id');
                    $('#formulario-edicao-' + alertaId).slideDown();
                    $(this).parent().hide(); // Esconde os botões de ação
                    
                    // Mostrar inputs de edição e esconder view mode
                    $('#formulario-edicao-' + alertaId + ' .edit-mode').show();
                    $('#formulario-edicao-' + alertaId + ' .view-mode').hide();
                });

                // Botão de cancelar - para cada alerta
                $(document).on('click', '.botao-cancelar', function() {
                    var alertaId = $(this).data('alerta-id');
                    $('#formulario-edicao-' + alertaId).slideUp();
                    $('.botao-edicao[data-alerta-id="' + alertaId + '"]').parent().show(); // Mostra os botões de ação
                    
                    // Mostrar view mode e esconder inputs de edição
                    $('#formulario-edicao-' + alertaId + ' .view-mode').show();
                    $('#formulario-edicao-' + alertaId + ' .edit-mode').hide();
                });
            });

            // Botão para eliminar alerta
            $(document).on('click', '.botao-eliminar', function() {
                var alertaId = $(this).data('alerta-id');
                if(confirm('Tem certeza que deseja eliminar este alerta?')) {
                    $.ajax({
                        url: 'anular_alerta.php',
                        method: 'POST',
                        data: { id_alerta: alertaId },
                        success: function(response) {
                            location.reload();
                        },
                        error: function() {
                            alert('Erro ao eliminar alerta');
                        }
                    });
                }
            });

            // Submissão automática do formulário ao alterar ordenação
            $('select[name="ordenacao"]').change(function() {
                $(this).closest('form').submit();
            });

            // Enter para pesquisar
            $('input[name="pesquisa"]').keypress(function(e) {
                if (e.which == 13) {
                    $(this).closest('form').submit();
                }
            });
        </script>
    <?php endif; ?>

</body>

</html>