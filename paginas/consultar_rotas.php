<?php
    // Inicia a Sessão
    session_start();

    // Includes
    include("../basedados/basedados.h"); 
    include("dados_navbar.php");
    include("const_utilizadores.php");

    // Variável para armazenar mensagens de erro PHP (conexão, query, etc.)
    $mensagem_erro = '';
    $mensagem_sucesso = '';

    // Verifica se o utilizador tem o login feito   
    $tem_login = isset($_SESSION['id_utilizador']) && !empty($_SESSION['id_utilizador']);
    $mostrar_alertas = false;
    $numero_alertas = 0;
    
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

    // Parâmetros de pesquisa e ordenação
    $pesquisa = isset($_GET['pesquisa']) ? trim($_GET['pesquisa']) : '';
    $ordenacao = isset($_GET['ordenacao']) ? $_GET['ordenacao'] : 'id_asc';

    // Inicializa a variável rotas
    $rotas = [];

    if ($conn) {
        // Constrói a consulta SQL base para selecionar as rotas ativas
        $sql = "SELECT id, origem, destino FROM rota WHERE estado = 1";
        
        // Adiciona condição de pesquisa se houver termo de pesquisa
        if (!empty($pesquisa)) {
            $sql .= " AND (origem LIKE ? OR destino LIKE ? OR id LIKE ?)";
        }
        
        // Adiciona ordenação
        switch ($ordenacao) {
            case 'id_asc':
                $sql .= " ORDER BY id ASC";
                break;
            case 'id_desc':
                $sql .= " ORDER BY id DESC";
                break;
            case 'origem_asc':
                $sql .= " ORDER BY origem ASC";
                break;
            case 'origem_desc':
                $sql .= " ORDER BY origem DESC";
                break;
            case 'destino_asc':
                $sql .= " ORDER BY destino ASC";
                break;
            case 'destino_desc':
                $sql .= " ORDER BY destino DESC";
                break;
            default:
                $sql .= " ORDER BY id ASC";
        }

        $stmt = $conn->prepare($sql);

        if ($stmt) {
            // Faz bind dos parâmetros se houver pesquisa
            if (!empty($pesquisa)) {
                $termo_pesquisa = "%$pesquisa%";
                $stmt->bind_param("sss", $termo_pesquisa, $termo_pesquisa, $pesquisa);
            }
            
            // Executa a consulta SQL para apresentar as rotas
            if($stmt->execute()) {
                $resultado = $stmt->get_result();
                $rotas = $resultado->fetch_all(MYSQLI_ASSOC);
            } else {
                $mensagem_erro = "Erro ao executar a consulta das rotas.";
            }
            $stmt->close();
        } else {
            $mensagem_erro = "Erro ao preparar a consulta das rotas.";
        }
    }
    
    if ($conn) {
        $conn->close();
    }
?>

<!------------------------------------------------------------------------------ COMEÇO DO HTML ------------------------------------------------------------------------------->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>FelixBus - Rotas</title>
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
        /* CSS personalizado para scroll nas rotas */
        .rotas-scroll-container {
            max-height: 400px;
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 10px;
        }
        
        /* Personalizar a scrollbar */
        .rotas-scroll-container::-webkit-scrollbar {
            width: 8px;
        }
        
        .rotas-scroll-container::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }
        
        .rotas-scroll-container::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 10px;
        }
        
        .rotas-scroll-container::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }
        
        /* Para Firefox */
        .rotas-scroll-container {
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
                    <a href="consultar_rotas.php" class="nav-item nav-link active">Rotas</a>

                    <!-- Link de Alertas - só aparece se houver alertas -->
                    <?php if ($mostrar_alertas || isset($_SESSION['tipo_utilizador']) && $_SESSION['tipo_utilizador'] == 1): ?>
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
                    <h3 class="text-white m-0">Consultar Rotas</h3>
                </div>
            
                <!-- Adicionar Rota -->
                <?php if (isset($_SESSION['tipo_utilizador']) && $_SESSION['tipo_utilizador'] == 1): ?>
                    <div class="text-center my-5">
                        <h5 class="text-white">Pretende adicionar uma nova rota? <a href="adicionar_rota.php" class="text-info"> Clique aqui</a></h5>
                    </div>
                <?php endif; ?>

                <?php if (!empty($mensagem_erro)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($mensagem_erro); ?></div>
                <?php endif; ?>
            
                <!-- Div Mensagens -->
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
                <div class="filtros-container">
                    <form method="GET" action="" class="row g-3 align-items-end" id="filtrosForm">
                        <div class="col-md-4">
                            <label class="form-label text-white mb-2">
                                <i class="fas fa-search me-2"></i>Pesquisar por Origem, Destino ou ID:
                            </label>
                            <input type="text" 
                                name="pesquisa" 
                                class="form-control search-input" 
                                placeholder="Digite o termo de pesquisa..." 
                                value="<?php echo htmlspecialchars($pesquisa); ?>">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label text-white mb-2">
                                <i class="fas fa-sort me-2"></i>Ordenar por:
                            </label>
                            <select name="ordenacao" class="form-select filtro-select" id="ordenacaoSelect" onchange="this.form.submit()">
                                <option value="id_asc" <?php echo ($ordenacao == 'id_asc') ? 'selected' : ''; ?>>ID (Crescente)</option>
                                <option value="id_desc" <?php echo ($ordenacao == 'id_desc') ? 'selected' : ''; ?>>ID (Decrescente)</option>
                                <option value="origem_asc" <?php echo ($ordenacao == 'origem_asc') ? 'selected' : ''; ?>>Origem (A-Z)</option>
                                <option value="origem_desc" <?php echo ($ordenacao == 'origem_desc') ? 'selected' : ''; ?>>Origem (Z-A)</option>
                                <option value="destino_asc" <?php echo ($ordenacao == 'destino_asc') ? 'selected' : ''; ?>>Destino (A-Z)</option>
                                <option value="destino_desc" <?php echo ($ordenacao == 'destino_desc') ? 'selected' : ''; ?>>Destino (Z-A)</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="d-flex justify-content-end">
                                <a href="?" class="btn btn-limpar rounded-pill">
                                    <i class="fas fa-times me-1"></i>Limpar
                                </a>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Contador de Resultados -->
                    <div class="mt-3 d-flex justify-content-between align-items-center">
                        <div class="contador-resultados">
                            <i class="fas fa-list me-2"></i>
                            <?php echo count($rotas); ?> rota(s) encontrada(s)
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

                <?php if (!empty($rotas)): ?>
                    <!-- Container com scroll aplicado apenas às Rotas -->
                    <div class="rotas-scroll-container">
                        <div class="row g-3">
                            <?php foreach ($rotas as $rota): ?>
                                <div class="col-12">
                                    <div class="bg-gradient position-relative mx-auto mt-3 animated slideInDown">
                                        <div class="card-body p-4">
                                            <div class="row align-items-center">
                                                <div class="col-md-8">
                                                    <div class="d-flex align-items-center mb-3">
                                                        <i class="fas fa-route text-primary me-2 fa-lg"></i>
                                                        <h5 class="card-title text-primary mb-0">Rota #<?php echo htmlspecialchars($rota['id']); ?></h5>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-sm-6">
                                                            <p class="card-text mb-1"><strong><i class="fas fa-map-marker-alt text-success me-1"></i>De:</strong> <?php echo htmlspecialchars($rota['origem']); ?></p>
                                                        </div>
                                                        <div class="col-sm-6">
                                                            <p class="card-text mb-1"><strong><i class="fas fa-map-marker-alt text-info me-1"></i>Para:</strong> <?php echo htmlspecialchars($rota['destino']); ?></p>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <?php if (isset($_SESSION['tipo_utilizador']) && $_SESSION['tipo_utilizador'] == 1): ?>
                                                <div class="col-md-4 text-md-end">
                                                    <div class="d-flex flex-column gap-2">
                                                        <button type="button" class="btn btn-warning rounded-pill py-2 px-4 botao-edicao" data-rota-id="<?php echo $rota['id']; ?>">
                                                            <i class="fas fa-edit me-2"></i>Editar
                                                        </button>
                                                        <button type="button" class="btn btn-danger rounded-pill py-2 px-4 botao-anular" data-rota-id="<?php echo $rota['id']; ?>">
                                                            <i class="fas fa-times me-2"></i>Anular
                                                        </button>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <?php if (isset($_SESSION['tipo_utilizador']) && $_SESSION['tipo_utilizador'] == 1): ?>
                                            <!-- Formulário de edição (inicialmente oculto) -->
                                            <div id="formulario-edicao-<?php echo $rota['id']; ?>" class="formulario-edicao" style="display: none;">
                                                <hr class="text-white my-4">
                                                <form action="editar_rota.php" method="POST">
                                                     <!-- Formulário com os dados da rota a Alterar -->
                                                    <input type="hidden" id="id" name="id" value="<?php echo htmlspecialchars($rota['id']); ?>">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label text-white">
                                                                    <i class="fas fa-map-marker-alt text-success me-1"></i>Origem:
                                                                </label>
                                                                <div class="view-mode">
                                                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($rota['origem']); ?>" readonly>
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
                                                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($rota['destino']); ?>" readonly>
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
                                                        <button type="button" class="btn btn-outline-danger rounded-pill botao-cancelar" data-rota-id="<?php echo $rota['id']; ?>">
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

                <!-- Se não houver rota -->
                <?php else: ?>
                    <div class="text-center">
                        <div class="mb-4">
                            <i class="fas fa-route text-light" style="font-size: 4rem; opacity: 0.5;"></i>
                        </div>
                        <?php if (!empty($pesquisa)): ?>
                            <p class="text-center text-white fs-5 mb-3">Nenhuma rota encontrada para "<?php echo htmlspecialchars($pesquisa); ?>"</p>
                            <p class="text-center text-light mb-4">Tente pesquisar por outros termos ou <a href="?" class="text-info">limpar os filtros</a>.</p>
                        <?php else: ?>
                            <p class="text-center text-white fs-5 mb-4">Nenhuma rota encontrada.</p>
                        <?php endif; ?>
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

        <?php if (isset($_SESSION['tipo_utilizador']) && $_SESSION['tipo_utilizador'] == 1): ?>
            <script>
                function carregarDistritosRota(rotaId) {
                    fetch('rotas.php')
                        .then(response => response.text())
                        .then(data => {
                            // Atualiza apenas os selects da rota específica
                            document.querySelector(`#formulario-edicao-${rotaId} #origem`).innerHTML = data;
                            document.querySelector(`#formulario-edicao-${rotaId} #destino`).innerHTML = data;
                        })
                        .catch(error => {
                            console.error('Erro ao carregar distritos:', error);
                        });
                }

                $(document).ready(function() {
                    // Botão de edição - para cada rota
                    $('.botao-edicao').click(function() {
                        var rotaId = $(this).data('rota-id');
                        $('#formulario-edicao-' + rotaId).slideDown();
                        $(this).parent().hide(); // Esconde os botões de ação
                        
                        // Mostrar inputs de edição e esconder view mode
                        $('#formulario-edicao-' + rotaId + ' .edit-mode').show();
                        $('#formulario-edicao-' + rotaId + ' .view-mode').hide();
                        
                        // Carregar distritos para este formulário
                        carregarDistritosRota(rotaId);
                    });

                    // Botão de cancelar - para cada rota
                    $(document).on('click', '.botao-cancelar', function() {
                        var rotaId = $(this).data('rota-id');
                        $('#formulario-edicao-' + rotaId).slideUp();
                        $('.botao-edicao[data-rota-id="' + rotaId + '"]').parent().show(); // Mostra os botões de ação
                        
                        // Mostrar view mode e esconder inputs de edição
                        $('#formulario-edicao-' + rotaId + ' .view-mode').show();
                        $('#formulario-edicao-' + rotaId + ' .edit-mode').hide();
                    });
                });

                // Botão para anular rota
                $(document).on('click', '.botao-anular', function() {
                    var rotaId = $(this).data('rota-id');
                    if(confirm('Tem certeza que deseja anular esta rota? Esta ação pode afetar viagens já agendadas.')) {
                        $.ajax({
                            url: 'anular_rota.php',
                            method: 'POST',
                            data: { id: rotaId },
                            success: function(response) {
                                location.reload();
                            },
                            error: function() {
                                alert('Erro ao anular rota');
                            }
                        });
                    }
                });

                // JavaScript corrigido para os filtros
                    $(document).ready(function() {
                        // Submissão automática do formulário ao alterar ordenação
                        $('#ordenacaoSelect').change(function() {
                            $('#filtrosForm').submit();
                        });

                        // Enter para pesquisar
                        $('input[name="pesquisa"]').keypress(function(e) {
                            if (e.which == 13) { // Enter key
                                e.preventDefault();
                                $('#filtrosForm').submit();
                            }
                        });

                        // Botão de pesquisa
                        $('#filtrosForm').on('submit', function(e) {
                            // Permitir submissão normal do formulário
                            return true;
                        });
                    });
            </script>
        <?php endif; ?>
    </body>
</html>