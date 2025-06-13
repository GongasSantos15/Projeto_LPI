<?php
    // Inicia a Sessão
    session_start();

    // Include conexão à BD
    include("../basedados/basedados.h"); 
    include("const_utilizadores.php");
    include("dados_navbar.php");

    // Variável para armazenar mensagens de erro PHP (conexão, query, etc.)
    $mensagem_erro = '';
    $mensagem_sucesso = '';

    // Verifica se o utilizador tem o login feito e é admin
    $tem_login = isset($_SESSION['id_utilizador']) && !empty($_SESSION['id_utilizador']);
    $e_admin = isset($_SESSION['tipo_utilizador']) && $_SESSION['tipo_utilizador'] == 1;
    
    // Redireciona se não for admin
    if (!$tem_login || !$e_admin) {
        header("Location: index.php");
        exit();
    }
    
    $pagina_inicial = 'pagina_inicial_admin.php';

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
    $filtro_tipo = isset($_GET['filtro_tipo']) ? $_GET['filtro_tipo'] : '';
    $ordenacao = isset($_GET['ordenacao']) ? $_GET['ordenacao'] : 'id_asc';

    // Inicializa a variável utilizadores
    $utilizadores = [];

    $numero_alertas = 0;
    $mostrar_alertas = false;
    
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

        // Constrói a consulta SQL base - mostra todos os utilizadores incluindo anulados
        $sql = "SELECT id, nome_proprio, nome_utilizador, tipo_utilizador, id_carteira FROM utilizador WHERE tipo_utilizador != 4";
        
        // Adiciona condição de pesquisa se houver termo de pesquisa
        if (!empty($pesquisa)) {
            $sql .= " AND (nome_proprio LIKE ? OR nome_utilizador LIKE ? OR id LIKE ?)";
        }
        
        // Adiciona filtro por tipo de utilizador
        if (!empty($filtro_tipo)) {
            $sql .= " AND tipo_utilizador = ?";
        }
        
        // Adiciona ordenação
        switch ($ordenacao) {
            case 'id_asc':
                $sql .= " ORDER BY id ASC";
                break;
            case 'id_desc':
                $sql .= " ORDER BY id DESC";
                break;
            case 'nome_asc':
                $sql .= " ORDER BY nome_proprio ASC";
                break;
            case 'nome_desc':
                $sql .= " ORDER BY nome_proprio DESC";
                break;
            case 'utilizador_asc':
                $sql .= " ORDER BY nome_utilizador ASC";
                break;
            case 'utilizador_desc':
                $sql .= " ORDER BY nome_utilizador DESC";
                break;
            case 'tipo_asc':
                $sql .= " ORDER BY tipo_utilizador ASC";
                break;
            case 'tipo_desc':
                $sql .= " ORDER BY tipo_utilizador DESC";
                break;
            default:
                $sql .= " ORDER BY id ASC";
        }

        $stmt = $conn->prepare($sql);

        if ($stmt) {
            // Prepara os parâmetros para bind
            $parametros = [];
            $tipos = '';
            
            // Adiciona parâmetros de pesquisa se houver
            if (!empty($pesquisa)) {
                $termo_pesquisa = "$pesquisa%";
                $parametros[] = $termo_pesquisa;
                $parametros[] = $termo_pesquisa;
                $parametros[] = $pesquisa;
                $tipos .= 'sss';
            }
            
            // Adiciona parâmetro de filtro por tipo
            if (!empty($filtro_tipo)) {
                $parametros[] = $filtro_tipo;
                $tipos .= 'i';
            }
            
            // Faz bind dos parâmetros se houver
            if (!empty($parametros)) {
                $stmt->bind_param($tipos, ...$parametros);
            }
            // Inicia a consulta
            if($stmt->execute()) {
                $resultado = $stmt->get_result();
                $utilizadores = $resultado->fetch_all(MYSQLI_ASSOC);
            } else {
                $mensagem_erro = "Erro ao executar a consulta dos utilizadores.";
            }
            $stmt->close();
        } else {
            $mensagem_erro = "Erro ao preparar a consulta dos utilizadores.";
        }
    }
    
    // Função auxiliar para obter nome do tipo de utilizador
    function obterTipoUtilizador($tipo) {
        switch ($tipo) {
            case 1:
                return 'Admin';
            case 2:
                return 'Funcionário';
            case 3:
                return 'Cliente';
            case 5:
                 return 'Não Validado';
            case 6:
                return 'Anulado';
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
    <title>FelixBus - Utilizadores</title>
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
        /* CSS personalizado para scroll nos utilizadores */
        .utilizadores-scroll-container {
            max-height: 500px;
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 10px;
        }
        
        /* Personalizar a scrollbar */
        .utilizadores-scroll-container::-webkit-scrollbar {
            width: 8px;
        }
        
        .utilizadores-scroll-container::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }
        
        .utilizadores-scroll-container::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 10px;
        }
        
        .utilizadores-scroll-container::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }
        
        /* Para Firefox */
        .utilizadores-scroll-container {
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

        .badge-tipo {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
        }

        .tipo-admin { background-color: #dc3545; }
        .tipo-funcionario { background-color: #ffc107; color: #000; }
        .tipo-cliente { background-color: #28a745; }
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
                    <a href="consultar_alertas.php" class="nav-item nav-link">Alertas</a>

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

        <div class="rounded shadow" style="max-width: 1200px; width: 100%; margin-top: 150px;">
            <div class="d-flex justify-content-between align-items-center mb-5">
                <h3 class="text-white m-0">Gestão de Utilizadores</h3>
            </div>
            
            <div class="text-center my-5">
                <h5 class="text-white">Pretende adicionar um novo utilizador? <a href="adicionar_utilizador.php" class="text-info">Clique aqui</a></h5>
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

            <!-- Filtros de Pesquisa e Ordenação -->
            <div class="filtros-container">
                <form method="GET" action="" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label text-white mb-2">
                            <i class="fas fa-search me-2"></i>Pesquisar por Nome, Utilizador ou ID:
                        </label>
                        <input type="text" 
                               name="pesquisa" 
                               class="form-control search-input" 
                               placeholder="Digite o termo de pesquisa..." 
                               value="<?php echo htmlspecialchars($pesquisa); ?>">
                    </div>                  
                        <div class="col-md-3">
                            <label class="form-label text-white mb-2">
                                <i class="fas fa-user-tag me-2"></i>Filtrar por Tipo:
                            </label>
                            <select name="filtro_tipo" class="form-select filtro-select">
                                <option value="">Todos os tipos</option>
                                <?php
                                    $valores_tipos = [1, 2, 3, 5, 6];
                                
                                    foreach ($valores_tipos as $valor) {
                                        // Verifica contra o filtro atual ($filtro_tipo) em vez do tipo do utilizador
                                        $selected = ($filtro_tipo == $valor) ? 'selected' : '';
                                        $nome = obterTipoUtilizador($valor);
                                        echo "<option value='$valor' $selected>$nome</option>";
                                    }
                                ?>
                            </select>
                        </div>
                    
                    <div class="col-md-3">
                        <label class="form-label text-white mb-2">
                            <i class="fas fa-sort me-2"></i>Ordenar por:
                        </label>
                        <select name="ordenacao" class="form-select filtro-select">
                            <option value="id_asc" <?php echo ($ordenacao == 'id_asc') ? 'selected' : ''; ?>>ID (Crescente)</option>
                            <option value="id_desc" <?php echo ($ordenacao == 'id_desc') ? 'selected' : ''; ?>>ID (Decrescente)</option>
                            <option value="nome_asc" <?php echo ($ordenacao == 'nome_asc') ? 'selected' : ''; ?>>Nome (A-Z)</option>
                            <option value="nome_desc" <?php echo ($ordenacao == 'nome_desc') ? 'selected' : ''; ?>>Nome (Z-A)</option>
                            <option value="utilizador_asc" <?php echo ($ordenacao == 'utilizador_asc') ? 'selected' : ''; ?>>Utilizador (A-Z)</option>
                            <option value="utilizador_desc" <?php echo ($ordenacao == 'utilizador_desc') ? 'selected' : ''; ?>>Utilizador (Z-A)</option>
                            <option value="tipo_asc" <?php echo ($ordenacao == 'tipo_asc') ? 'selected' : ''; ?>>Tipo (Crescente)</option>
                            <option value="tipo_desc" <?php echo ($ordenacao == 'tipo_desc') ? 'selected' : ''; ?>>Tipo (Decrescente)</option>
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
                        <i class="fas fa-users me-2"></i>
                        <?php echo count($utilizadores); ?> utilizador(es) encontrado(s)
                        <?php if (!empty($pesquisa)): ?>
                            para "<?php echo htmlspecialchars($pesquisa); ?>"
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($pesquisa) || !empty($filtro_tipo)): ?>
                        <small class="text-light">
                            <i class="fas fa-info-circle me-1"></i>
                            Filtros ativos
                        </small>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($utilizadores)): ?>
                <!-- Container com scroll aplicado apenas aos utilizadores -->
                <div class="utilizadores-scroll-container">
                    <div class="row g-3">
                        <?php foreach ($utilizadores as $utilizador): ?>
                            <div class="col-12">
                                <div class="bg-gradient position-relative mx-auto mt-3 animated slideInDown">
                                    <div class="card-body p-4">
                                        <div class="row align-items-center">
                                            <div class="col-md-8">
                                                <div class="d-flex align-items-center justify-content-between mb-3">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-user text-primary me-2 fa-lg"></i>
                                                        <h5 class="card-title text-primary mb-0">Utilizador #<?php echo htmlspecialchars($utilizador['id']); ?></h5>
                                                    </div>
                                                    <span class="badge badge-tipo tipo-<?php echo strtolower(str_replace('ário', 'ario', obterTipoUtilizador($utilizador['tipo_utilizador']))); ?>">
                                                        <?php echo obterTipoUtilizador($utilizador['tipo_utilizador']); ?>
                                                    </span>
                                                </div>
                                                <div class="row">
                                                    <div class="col-sm-6">
                                                        <p class="card-text mb-1"><strong><i class="fas fa-user text-info me-1"></i>Nome:</strong> <?php echo htmlspecialchars($utilizador['nome_proprio']); ?></p>
                                                        <p class="card-text mb-1"><strong><i class="fas fa-at text-success me-1"></i>Utilizador:</strong> <?php echo htmlspecialchars($utilizador['nome_utilizador']); ?></p>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <p class="card-text mb-1"><strong><i class="fas fa-wallet text-warning me-1"></i>ID Carteira:</strong> <?php echo htmlspecialchars($utilizador['id_carteira']) ?: 'N/A'; ?></p>
                                                        <p class="card-text mb-1"><strong><i class="fas fa-key text-danger me-1"></i>Palavra Passe:</strong></p>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-4 text-md-end">
                                                <div class="d-flex flex-column gap-2">
                                                    <button type="button" class="btn btn-warning rounded-pill py-2 px-4 botao-edicao" data-utilizador-id="<?php echo $utilizador['id']; ?>">
                                                        <i class="fas fa-edit me-2"></i>Editar
                                                    </button>
                                                    <?php if ($utilizador['id'] != $_SESSION['id_utilizador'] && $utilizador['tipo_utilizador'] != CLIENTE_APAGADO): ?>
                                                        <button type="button" class="btn btn-danger rounded-pill py-2 px-4 botao-anular" data-utilizador-id="<?php echo $utilizador['id']; ?>">
                                                            <i class="fas fa-user-times me-2"></i>Anular
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Formulário de edição (inicialmente oculto) -->
                                        <div id="formulario-edicao-<?php echo $utilizador['id']; ?>" class="formulario-edicao" style="display: none;">
                                            <hr class="text-white my-4">
                                            <form action="editar_utilizador.php" method="POST">
                                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($utilizador['id']); ?>">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label text-white">
                                                                <i class="fas fa-user text-info me-1"></i>Nome Próprio:
                                                            </label>
                                                            <input type="text" name="nome_proprio" class="form-control bg-dark text-light border-primary" 
                                                                   value="<?php echo htmlspecialchars($utilizador['nome_proprio']); ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label text-white">
                                                                <i class="fas fa-at text-success me-1"></i>Nome de Utilizador:
                                                            </label>
                                                            <input type="text" name="nome_utilizador" class="form-control bg-dark text-light border-primary" 
                                                                   value="<?php echo htmlspecialchars($utilizador['nome_utilizador']); ?>" required>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label text-white">
                                                                <i class="fas fa-user-tag text-primary me-1"></i>Tipo de Utilizador:
                                                            </label>
                                                            <select name="tipo_utilizador" class="form-select bg-dark text-light border-primary" required>
                                                                <?php
                                                                $valores_tipos = [1, 2, 3, 5, 6];
                                                                
                                                                foreach ($valores_tipos as $valor) {
                                                                    $selected = ($utilizador['tipo_utilizador'] == $valor) ? 'selected' : '';
                                                                    $nome = obterTipoUtilizador($valor);
                                                                    echo "<option value='$valor' $selected>$nome</option>";
                                                                }
                                                                ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label text-white">
                                                                <i class="fas fa-wallet text-warning me-1"></i>ID Carteira:
                                                            </label>
                                                            <input type="number" name="id_carteira" class="form-control bg-dark text-light border-primary" 
                                                                   value="<?php echo htmlspecialchars($utilizador['id_carteira']); ?>" required>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label text-white">
                                                                <i class="fas fa-key text-danger me-1"></i>Palavra Passe:
                                                            </label>
                                                            <input type="text" name="palavra_passe" class="form-control bg-dark text-light border-primary" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-end gap-2 mt-3">
                                                    <button type="button" class="btn btn-outline-danger rounded-pill botao-cancelar" data-utilizador-id="<?php echo $utilizador['id']; ?>">
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
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="text-center">
                    <div class="mb-4">
                        <i class="fas fa-users text-light" style="font-size: 4rem; opacity: 0.5;"></i>
                    </div>
                    <?php if (!empty($pesquisa) || !empty($filtro_tipo)): ?>
                        <p class="text-center text-white fs-5 mb-3">Nenhum utilizador encontrado com os filtros aplicados</p>
                        <p class="text-center text-light mb-4">Tente pesquisar por outros termos ou <a href="?" class="text-info">limpar os filtros</a>.</p>
                    <?php else: ?>
                        <p class="text-center text-white fs-5 mb-4">Nenhum utilizador encontrado.</p>
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

    <script>
        $(document).ready(function() {
            // Botão de edição - para cada utilizador
            $('.botao-edicao').click(function() {
                var utilizadorId = $(this).data('utilizador-id');
                $('#formulario-edicao-' + utilizadorId).slideDown();
                $(this).parent().hide(); // Esconde os botões de ação
            });

            // Botão de cancelar - para cada utilizador
            $(document).on('click', '.botao-cancelar', function() {
                var utilizadorId = $(this).data('utilizador-id');
                $('#formulario-edicao-' + utilizadorId).slideUp();
                $('.botao-edicao[data-utilizador-id="' + utilizadorId + '"]').parent().show(); // Mostra os botões de ação
            });

            // Botão para anular utilizador
            $(document).on('click', '.botao-anular', function() {
                var utilizadorId = $(this).data('utilizador-id');
                if(confirm('Tem certeza que deseja anular este utilizador? Esta ação irá desativar o acesso do utilizador ao sistema.')) {
                    $.ajax({
                        url: 'anular_utilizador.php',
                        method: 'POST',
                        data: { id: utilizadorId },
                        success: function(response) {
                            if (response === "success") {
                                location.reload();
                            } else if (response === "self_delete") {
                                alert("Não pode anular a sua própria conta!");
                            } else {
                                alert("Erro ao anular utilizador");
                            }
                        },
                        error: function() {
                            alert('Erro de comunicação com o servidor');
                        }
                    });
                }
            });

            // Submissão automática do formulário ao alterar ordenação ou filtro
            $('select[name="ordenacao"], select[name="filtro_tipo"]').change(function() {
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