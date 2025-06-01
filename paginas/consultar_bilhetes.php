<?php
    session_start();

    include("../basedados/basedados.h");
    include("dados_navbar.php");

    if (!isset($_SESSION['id_utilizador'])) {
        header("Location: entrar.php");
        exit();
    }   

    $mensagem_erro = '';
    $mensagem_sucesso = '';

    if (isset($_SESSION['mensagem_sucesso'])) {
        $mensagem_sucesso = $_SESSION['mensagem_sucesso'];
        unset($_SESSION['mensagem_sucesso']);
    }

    // Verifica se o utilizador tem o login feito   
    $tem_login = isset($_SESSION['id_utilizador']) && !empty($_SESSION['id_utilizador']); 

    $mostrar_alertas = false;
    $numero_alertas_cliente = 0;

    $e_admin = isset($_SESSION['tipo_utilizador']) && $_SESSION['tipo_utilizador'] == 1;
    $e_funcionario = isset($_SESSION['tipo_utilizador']) && $_SESSION['tipo_utilizador'] == 2;
    $eh_cliente = isset($_SESSION['tipo_utilizador']) && $_SESSION['tipo_utilizador'] == 3;

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
                    $mostrar_alertas = $numero_alertas_cliente > 0;
                }
                $stmt_count->close();
            }
        } else if (!$tem_login) {
            // Para visitantes não logados - verifica também o estado do alerta
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

    // Parâmetros de pesquisa e ordenação
    $pesquisa = isset($_GET['pesquisa']) ? trim($_GET['pesquisa']) : '';
    $filtro_cliente = isset($_GET['filtro_cliente']) ? $_GET['filtro_cliente'] : '';
    $ordenacao = isset($_GET['ordenacao']) ? $_GET['ordenacao'] : 'id_asc';

    $bilhetes = [];

    if ($conn) {
        // Constrói a consulta SQL base
        if ($e_admin || $e_funcionario) {
            // Admin/Funcionário vê todos os bilhetes de clientes
            $sql = "SELECT
                        b.id AS id_bilhete,
                        b.data_compra,
                        v.preco,
                        v.data_hora,
                        r.origem,
                        r.destino,
                        v.id AS id_viagem,
                        b.estado,
                        u.nome_utilizador AS nome_utilizador,
                        u.id AS id_cliente
                    FROM
                        bilhete b
                    JOIN
                        viagem v ON b.id_viagem = v.id
                    JOIN
                        rota r ON v.id_rota = r.id
                    JOIN
                        utilizador u ON b.id_utilizador = u.id
                    WHERE
                        u.tipo_utilizador = 3"; // Apenas clientes
        } else {
            // Cliente vê apenas seus bilhetes
            $id_utilizador = $_SESSION['id_utilizador'];
            $sql = "SELECT
                        b.id AS id_bilhete,
                        b.data_compra,
                        v.preco,
                        v.data_hora,
                        r.origem,
                        r.destino,
                        v.id AS id_viagem,
                        b.estado
                    FROM
                        bilhete b
                    JOIN
                        viagem v ON b.id_viagem = v.id
                    JOIN
                        rota r ON v.id_rota = r.id
                    WHERE
                        b.id_utilizador = ?";
        }

        // Adiciona condição de pesquisa se houver termo de pesquisa
        if (!empty($pesquisa)) {
            if ($e_admin || $e_funcionario) {
                $sql .= " AND (u.nome_proprio LIKE ? OR b.id LIKE ? OR r.origem LIKE ? OR r.destino LIKE ?)";
            } else {
                $sql .= " AND (r.origem LIKE ? OR r.destino LIKE ? OR b.id LIKE ?)";
            }
        }
        
        // Adiciona filtro por cliente (apenas para admin/funcionário)
        if (($e_admin || $e_funcionario) && !empty($filtro_cliente)) {
            $sql .= " AND u.id = ?";
        }
        
        // Adiciona condição para bilhetes ativos (apenas para cliente)
        if ($eh_cliente) {
            $sql .= " AND b.estado = 1";
        }

        switch ($ordenacao) {
            case 'id_asc':
                $sql .= " ORDER BY b.id ASC";
                break;
            case 'id_desc':
                $sql .= " ORDER BY b.id DESC";
                break;
            case 'data_asc':
                $sql .= " ORDER BY b.data_compra ASC";
                break;
            case 'data_desc':
                $sql .= " ORDER BY b.data_compra DESC";
                break;
            case 'viagem_asc':
                $sql .= " ORDER BY v.data_hora ASC";
                break;
            case 'viagem_desc':
                $sql .= " ORDER BY v.data_hora DESC";
                break;
            default:
                $sql .= " ORDER BY b.id ASC"; // Alterado para ASC como padrão
        }

        $stmt = $conn->prepare($sql);

        if ($stmt) {
            // Prepara os parâmetros para bind
            $params = [];
            $types = '';
            
            if ($eh_cliente) {
                $params[] = $id_utilizador;
                $types .= 'i';
            }
            
            // Adiciona parâmetros de pesquisa se houver
            if (!empty($pesquisa)) {
                $termo_pesquisa = "%$pesquisa%";
                if ($e_admin || $e_funcionario) {
                    $params[] = $termo_pesquisa;
                    $params[] = $termo_pesquisa;
                    $params[] = $termo_pesquisa;
                    $params[] = $termo_pesquisa;
                    $types .= 'ssss';
                } else {
                    $params[] = $termo_pesquisa;
                    $params[] = $termo_pesquisa;
                    $params[] = $termo_pesquisa;
                    $types .= 'sss';
                }
            }
            
            // Adiciona parâmetro de filtro por cliente
            if (($e_admin || $e_funcionario) && !empty($filtro_cliente)) {
                $params[] = $filtro_cliente;
                $types .= 'i';
            }
            
            // Faz bind dos parâmetros se houver
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            if($stmt->execute()) {
                $resultado = $stmt->get_result();
                $bilhetes = $resultado->fetch_all(MYSQLI_ASSOC); 
            } else {
                $mensagem_erro = "Erro ao executar a consulta: " . $stmt->error;
            }

            $stmt->close();
        } else {
            $mensagem_erro = "Erro ao preparar a consulta: " . $conn->$connect_error;
        }
        
        // Obter lista de clientes para o filtro (apenas para admin/funcionário)
        $clientes = [];
        if ($conn && ($e_admin || $e_funcionario)) {
            $sql_clientes = "SELECT id, nome_utilizador FROM utilizador WHERE tipo_utilizador = 3 ORDER BY nome_proprio ASC";
            $result = $conn->query($sql_clientes);
            if ($result) {
                $clientes = $result->fetch_all(MYSQLI_ASSOC);
            }
        }
        
        $conn->close();
    } else {
        $mensagem_erro = "Erro na conexão à base de dados.";
    }
?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="utf-8">
    <title>FelixBus - Bilhetes</title>
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
        /* CSS personalizado para scroll nos bilhetes */
        .bilhetes-scroll-container {
            max-height: 500px;
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 10px;
        }
        
        /* Personalizar a scrollbar */
        .bilhetes-scroll-container::-webkit-scrollbar {
            width: 8px;
        }
        
        .bilhetes-scroll-container::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }
        
        .bilhetes-scroll-container::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 10px;
        }
        
        .bilhetes-scroll-container::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }
        
        /* Para Firefox */
        .bilhetes-scroll-container {
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

        .badge-estado {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
        }

        .estado-ativo { background-color: #28a745; }
        .estado-anulado { background-color: #dc3545; }

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
                    
                    <!-- Link de Alertas - só aparece se houver alertas -->
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
                            <a href="consultar_bilhetes.php" class="nav-item nav-link active">Bilhetes</a>
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

                    <?php if($_SESSION['tipo_utilizador'] == 3): ?>
                        <!-- Dropdown dos Bilhetes -->
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
                <h3 class="text-white m-0">
                    <?php echo ($e_admin || $e_funcionario) ? 'Gestão de Bilhetes' : 'Os Meus Bilhetes'; ?>
                </h3>
            </div>

            <?php if (!empty($mensagem_erro)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($mensagem_erro); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($mensagem_sucesso)): ?>
                <div class="alert alert-success" id="mensagem-sucesso">
                    <?php echo htmlspecialchars($mensagem_sucesso); ?>
                </div>
                <script>
                    setTimeout(function() {
                        document.getElementById('mensagem-sucesso').style.display = 'none';
                    }, 3000);
                </script>
            <?php endif; ?>

            <!-- Filtros de Pesquisa e Ordenação (apenas para admin/funcionário) -->
            <?php if ($e_admin || $e_funcionario): ?>
            <div class="filtros-container">
                <form method="GET" action="" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label text-white mb-2">
                            <i class="fas fa-search me-2"></i>Pesquisar por Cliente, Origem, Destino ou ID:
                        </label>
                        <input type="text" 
                               name="pesquisa" 
                               class="form-control search-input" 
                               placeholder="Digite o termo de pesquisa..." 
                               value="<?php echo htmlspecialchars($pesquisa); ?>">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label text-white mb-2">
                            <i class="fas fa-user me-2"></i>Filtrar por Cliente:
                        </label>
                        <select name="filtro_cliente" class="form-select filtro-select">
                            <option value="">Todos os clientes</option>
                            <?php foreach ($clientes as $cliente): ?>
                                <option value="<?php echo $cliente['id']; ?>" <?php echo ($filtro_cliente == $cliente['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cliente['nome_utilizador']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label text-white mb-2">
                            <i class="fas fa-sort me-2"></i>Ordenar por:
                        </label>
                        <select name="ordenacao" class="form-select filtro-select">
                            <option value="id_asc" <?php echo ($ordenacao == 'id_asc' || $ordenacao == '') ? 'selected' : ''; ?>>ID (Mais antigo)</option>
                            <option value="id_desc" <?php echo ($ordenacao == 'id_desc') ? 'selected' : ''; ?>>ID (Mais recente)</option>
                            <option value="data_asc" <?php echo ($ordenacao == 'data_asc') ? 'selected' : ''; ?>>Data Compra (Mais antigo)</option>
                            <option value="data_desc" <?php echo ($ordenacao == 'data_desc') ? 'selected' : ''; ?>>Data Compra (Mais recente)</option>
                            <option value="viagem_asc" <?php echo ($ordenacao == 'viagem_asc') ? 'selected' : ''; ?>>Data Viagem (Mais antigo)</option>
                            <option value="viagem_desc" <?php echo ($ordenacao == 'viagem_desc') ? 'selected' : ''; ?>>Data Viagem (Mais recente)</option>
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
                        <i class="fas fa-ticket-alt me-2"></i>
                        <?php echo count($bilhetes); ?> bilhete(s) encontrado(s)
                        <?php if (!empty($pesquisa)): ?>
                            para "<?php echo htmlspecialchars($pesquisa); ?>"
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($pesquisa) || !empty($filtro_cliente)): ?>
                        <small class="text-light">
                            <i class="fas fa-info-circle me-1"></i>
                            Filtros ativos
                        </small>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($bilhetes)): ?>
                <!-- Container com scroll aplicado apenas aos bilhetes -->
                <div class="bilhetes-scroll-container">
                    <div class="row g-3">
                        <?php foreach ($bilhetes as $bilhete): ?>
                            <div class="col-12">
                                <div class="bg-gradient mb-3 position-relative mx-auto mt-3 animated slideInDown">
                                    <div class="card-body p-4">
                                        <div class="row align-items-center">
                                            <div class="col-md-8">
                                                <div class="d-flex align-items-center justify-content-between mb-3">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-ticket-alt text-primary me-2 fa-lg"></i>
                                                        <h5 class="card-title text-primary mb-0">Bilhete #<?php echo htmlspecialchars($bilhete['id_bilhete']); ?></h5>
                                                    </div>
                                                    <span class="badge badge-estado estado-<?php echo ($bilhete['estado'] == 1) ? 'ativo' : 'anulado'; ?>">
                                                        <?php echo ($bilhete['estado'] == 1) ? 'Ativo' : 'Anulado'; ?>
                                                    </span>
                                                </div>
                                                
                                                <?php if ($e_admin || $e_funcionario): ?>
                                                    <p class="card-text mb-3"><strong><i class="fas fa-user me-1"></i>Cliente:</strong> 
                                                        <?php echo htmlspecialchars($bilhete['nome_utilizador']); ?> (ID: <?php echo htmlspecialchars($bilhete['id_cliente']); ?>)
                                                    </p>
                                                <?php endif; ?>
                                                
                                                <div class="row">
                                                    <div class="col-sm-6">
                                                        <p class="card-text mb-1"><strong><i class="fas fa-map-marker-alt text-success me-1"></i>De:</strong> <?php echo htmlspecialchars($bilhete['origem']); ?></p>
                                                        <p class="card-text mb-1"><strong><i class="fas fa-map-marker-alt text-danger me-1"></i>Para:</strong> <?php echo htmlspecialchars($bilhete['destino']); ?></p>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <p class="card-text mb-1"><strong><i class="fas fa-calendar-alt text-info me-1"></i>Viagem:</strong> <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($bilhete['data_hora']))); ?></p>
                                                        <p class="card-text mb-1"><strong><i class="fas fa-shopping-cart text-warning me-1"></i>Compra:</strong> <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($bilhete['data_compra']))); ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-4 text-md-end">
                                                <div class="mb-3">
                                                    <span class="badge bg-info text-dark fs-6 px-3 py-2">
                                                        <i class="fas fa-euro-sign me-1"></i>
                                                        <?php echo number_format($bilhete['preco'], 2, ',', '.'); ?> €
                                                    </span>
                                                </div>
                                                
                                                <?php if ($bilhete['estado'] == 1): ?>
                                                    <div class="d-flex flex-column gap-2">
                                                        <?php if ($tem_login): ?>
                                                            <button type="button" class="btn btn-warning rounded-pill py-2 px-4 botao-edicao" data-bilhete-id="<?php echo $bilhete['id_bilhete']; ?>">
                                                                <i class="fas fa-edit me-2"></i>Editar
                                                            </button>
                                                        <?php endif; ?>
                                                        
                                                        <button type="button" class="btn btn-danger rounded-pill py-2 px-4 botao-anular" data-bilhete-id="<?php echo $bilhete['id_bilhete']; ?>">
                                                            <i class="fas fa-times me-2"></i>Anular
                                                        </button>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <!-- Formulário de edição (inicialmente oculto, apenas para admin/funcionário) -->
                                        <?php if ($tem_login): ?>
                                        <div id="formulario-edicao-<?php echo $bilhete['id_bilhete']; ?>" class="formulario-edicao" style="display: none;">
                                            <hr class="text-white my-4">
                                            <form action="editar_bilhete.php" method="GET">
                                                <input type="hidden" name="id_bilhete" value="<?php echo htmlspecialchars($bilhete['id_bilhete']); ?>">
                                                
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label text-white">
                                                                <i class="fas fa-map-marker-alt text-success me-1"></i>Origem:
                                                            </label>
                                                            <div class="view-mode">
                                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($bilhete['origem']); ?>" readonly>
                                                            </div>
                                                            <div class="edit-mode" style="display: none;">
                                                                <select name="origem" id="origem" class="form-select bg-dark text-light border-primary" required>
                                                                    <option>A carregar...</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label text-white">
                                                                <i class="fas fa-map-marker-alt text-danger me-1"></i>Destino:
                                                            </label>
                                                            <div class="view-mode">
                                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($bilhete['destino']); ?>" readonly>
                                                            </div>
                                                            <div class="edit-mode" style="display: none;">
                                                                <select name="destino"  id="destino" class="form-select bg-dark text-light border-primary" required>
                                                                    <option>A carregar...</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-end gap-2 mt-3">
                                                    <button type="button" class="btn btn-outline-danger rounded-pill botao-cancelar" data-bilhete-id="<?php echo $bilhete['id_bilhete']; ?>">
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
                        <i class="fas fa-ticket-alt text-light" style="font-size: 4rem; opacity: 0.5;"></i>
                    </div>
                    <?php if ($e_admin || $e_funcionario): ?>
                        <?php if (!empty($pesquisa) || !empty($filtro_cliente)): ?>
                            <p class="text-center text-white fs-5 mb-3">Nenhum bilhete encontrado com os filtros aplicados</p>
                            <p class="text-center text-light mb-4">Tente pesquisar por outros termos ou <a href="?" class="text-info">limpar os filtros</a>.</p>
                        <?php else: ?>
                            <p class="text-center text-white fs-5 mb-4">Nenhum bilhete encontrado.</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="text-center text-white fs-5 mb-4">Ainda não possui bilhetes comprados.</p>
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
    
    <script>
        function carregarDistritosBilhete(bilheteId, origemAtual, destinoAtual) {
            fetch('rotas.php')
                .then(response => response.text())
                .then(data => {
                    const origemSelect = document.querySelector(`#formulario-edicao-${bilheteId} #origem`);
                    const destinoSelect = document.querySelector(`#formulario-edicao-${bilheteId} #destino`);
                    
                    origemSelect.innerHTML = data;
                    destinoSelect.innerHTML = data;
                    
                    // Seleciona os valores atuais
                    if (origemAtual) {
                        origemSelect.value = origemAtual;
                    }
                    if (destinoAtual) {
                        destinoSelect.value = destinoAtual;
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar distritos:', error);
                });
        }

        $(document).ready(function() {
            $('.botao-edicao').click(function() {
                var bilheteId = $(this).data('bilhete-id');
                var card = $(this).closest('.card-body');
                var origemAtual = card.find('.view-mode input').first().val();
                var destinoAtual = card.find('.view-mode input').last().val();
                
                $('#formulario-edicao-' + bilheteId).slideDown();
                $(this).hide();
                
                $('#formulario-edicao-' + bilheteId + ' .edit-mode').show();
                $('#formulario-edicao-' + bilheteId + ' .view-mode').hide();
                
                carregarDistritosBilhete(bilheteId, origemAtual, destinoAtual);
            });

            // Botão de cancelar - para cada bilhete
            $(document).on('click', '.botao-cancelar', function() {
                var bilheteId = $(this).data('bilhete-id');
                $('#formulario-edicao-' + bilheteId).slideUp();
                $('.botao-edicao[data-bilhete-id="' + bilheteId + '"]').show();
                
                // Mostrar inputs e esconder selects
                $('#formulario-edicao-' + bilheteId + ' .view-mode').show();
                $('#formulario-edicao-' + bilheteId + ' .edit-mode').hide();
            });
            
            // Botão para anular bilhete
            $(document).on('click', '.botao-anular', function() {
                var bilheteId = $(this).data('bilhete-id');
                if(confirm('Tem certeza que deseja anular este bilhete?')) {
                    $.ajax({
                        url: 'anular_bilhete.php',
                        method: 'POST',
                        data: { id_bilhete: bilheteId },
                        success: function(response) {
                            location.reload();
                        },
                        error: function() {
                            alert('Erro ao anular bilhete');
                        }
                    });
                }
            });
            
            // Submissão automática do formulário ao alterar ordenação ou filtro
            $('select[name="ordenacao"], select[name="filtro_cliente"]').change(function() {
                $(this).closest('form').submit();
            });

            // Enter para pesquisar
            $('input[name="pesquisa"]').keypress(function(e) {
                if (e.which == 13) {
                    $(this).closest('form').submit();
                }
            });
        });
    </script>
</body>
</html>