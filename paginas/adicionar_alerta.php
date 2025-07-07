<?php
    // Inclui os ficheiros de base de dados e navegação
    include '../basedados/basedados.h';
    include 'dados_navbar.php';
    include 'const_utilizadores.php';

    session_start();

    $tipo_utilizador = null;
    $id_utilizador = null;

    try {
        // Converte o tipo de utilizador e o id para obter o da sessão
        if (isset($_SESSION['tipo_utilizador'])) {
            $tipo_utilizador = (int)$_SESSION['tipo_utilizador'];
        }
        if (isset($_SESSION['id_utilizador'])) {
            $id_utilizador = (int)$_SESSION['id_utilizador'];
        }

    } catch (Exception $e) { 
        $_SESSION['mensagem_erro'] = "Acesso não autorizado: Tipo de utilizador ou ID inválido.";
        header("Location: entrar.php"); 
        exit();
    }

    // Verifica se o utilizador é do tipo visitante e redireciona para a página de login
    if ($tipo_utilizador !== null && $tipo_utilizador == VISITANTE) {
        $_SESSION['mensagem_erro'] = "Acesso não autorizado: Por favor, faça login para aceder a esta página.";
        header("Location: entrar.php");
        exit();
    }

    // Verifica se o utilizador é administrador, caso contrário redireciona com erro
    if ($tipo_utilizador === null || $tipo_utilizador != ADMINISTRADOR) {
        $_SESSION['mensagem_erro'] = "Acesso não autorizado!";
        header("Location: consultar_alertas.php");
        exit();
    }

    // Verifica se o utilizador tem o login feito
    $tem_login = isset($_SESSION['id_utilizador']);
    $nome_utilizador_na_sessao = $_SESSION['nome_utilizador'] ?? '';
    $estado_alerta_padrao = 1;

    // Determina a página inicial correta baseada no tipo de utilizador
    $pagina_inicial = "index.php";
    if ($tem_login && $tipo_utilizador !== null) {
        switch ($tipo_utilizador) {
            case ADMINISTRADOR: $pagina_inicial = "pagina_inicial_admin.php"; break;
            case FUNCIONARIO: $pagina_inicial = "pagina_inicial_func.php"; break;
            case CLIENTE: $pagina_inicial = "pagina_inicial_cliente.php"; break;
        }
    }
    // Se uma pagina_inicial específica estiver na sessão, ela pode ter prioridade
    $pagina_inicial_sessao = $_SESSION['pagina_inicial'] ?? null;
    if ($pagina_inicial_sessao !== null && !empty($pagina_inicial_sessao)){
        $pagina_inicial = $pagina_inicial_sessao;
    }

    // Hash Maps para armazenar utilizadores e tipos de alerta
    $utilizadores = [];
    $tipos_alerta = [];

    try {
        if ($conn === null) {
            // Tratar erro de conexão não disponível
            $_SESSION['mensagem_erro'] = "Erro de conexão com a base de dados.";
            header("Location: " . $pagina_inicial);
            exit();
        }

        // Obter lista de utilizadores para o Submenu
        $sql_utilizadores = "SELECT id, nome_utilizador FROM utilizador WHERE id != ?";
        $stmt_utilizadores = $conn->prepare($sql_utilizadores);
        // Bind do parâmetro (ID do administrador logado)
        $stmt_utilizadores->bind_param("i", $id_utilizador);
        $stmt_utilizadores->execute();
        $resultado_utilizadores = $stmt_utilizadores->get_result();

        while($linha = $resultado_utilizadores->fetch_assoc()) {
            $utilizadores[$linha['id']] = $linha['nome_utilizador'];
        }
        $stmt_utilizadores->close();
        
        // Adiciona a opção "Visitante (Alerta Geral)" explicitamente, se o utilizador com esse ID não estiver na lista.
        if (!array_key_exists(VISITANTE, $utilizadores)) {
            $utilizadores[VISITANTE] = "Visitante (Alerta Geral)";
        }
        // Ordenar a lista de utilizadores para o dropdown
        asort($utilizadores); 

        // Obter a lista dos tipos de alerta a partir da tabela tipo_alerta
        $sql_tipos_alerta = "SELECT id, descricao FROM tipo_alerta";
        $stmt_tipos_alerta = $conn->prepare($sql_tipos_alerta);
        $stmt_tipos_alerta->execute();
        $resultado_tipos_alerta = $stmt_tipos_alerta->get_result();
        while ($linha = $resultado_tipos_alerta->fetch_assoc()) {
            $tipos_alerta[$linha['id']] = $linha['descricao'];
        }
        $stmt_tipos_alerta->close();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $descricao = $_POST['descricao'] ?? '';
            $id_utilizador_alvo_str = $_POST['id_utilizador'] ?? '';
            $tipo_alerta_str = $_POST['tipo_alerta'] ?? '';
            $id_admin_logado = $id_utilizador;

            $id_utilizador_alvo = null;
            if ($id_utilizador_alvo_str !== null && !empty($id_utilizador_alvo_str)) {
                try {
                    $id_utilizador_alvo = (int)$id_utilizador_alvo_str;
                } catch (Exception $e) {
                    $_SESSION['mensagem_erro'] = "ID do utilizador alvo inválido.";
                }
            }
            
            $id_tipo_alerta = null; 
            if ($tipo_alerta_str !== null && !empty($tipo_alerta_str)) {
                try {
                    $id_tipo_alerta = (int)$tipo_alerta_str;
                } catch (Exception $e) {
                    $_SESSION['mensagem_erro'] = "Tipo de alerta inválido.";
                }
            }

            if (empty(trim($descricao)) || $id_utilizador_alvo === null || $id_tipo_alerta === null) {
                $_SESSION['mensagem_erro'] = "Por favor, preencha todos os campos corretamente.";
            } else {
                $id_alerta_gerado = -1;
                // Inserir na tabela alerta
                $sql_alerta_inserir = "INSERT INTO alerta (descricao, estado, tipo_alerta) VALUES (?, ?, ?)";
                try {
                    $stmt_inserir_alerta = $conn->prepare($sql_alerta_inserir);
                    $stmt_inserir_alerta->bind_param("sii", $descricao, $estado_alerta_padrao, $id_tipo_alerta);
                    $stmt_inserir_alerta->execute();
                    $id_alerta_gerado = $conn->insert_id;
                    $stmt_inserir_alerta->close();
                } catch (Exception $e) {
                    error_log("Erro ao adicionar alerta na tabela 'alerta': " . $e->getMessage());
                    $_SESSION['mensagem_erro'] = "Erro ao adicionar alerta na tabela 'alerta'!";
                    header("Location: adicionar_alerta.php");
                    exit();
                }

                if ($id_alerta_gerado == -1) {
                    $_SESSION['mensagem_erro'] = "Erro ao obter ID do alerta inserido!";
                    header("Location: adicionar_alerta.php");
                    exit();
                }

                // Inserir na tabela utilizador_alerta
                if ($id_utilizador_alvo == VISITANTE) {
                    // Alerta geral para todos os utilizadores do tipo VISITANTE (ID 4)
                    $sql_selecionar_ids_visitante = "SELECT id FROM utilizador WHERE tipo_utilizador = ?";
                    $sql_inserir_utilizador_alerta = "INSERT INTO utilizador_alerta (id_alerta, id_utilizador, data_hora) VALUES (?, ?, NOW())";
                    
                    try {
                        $stmt_selecionar_visitante = $conn->prepare($sql_selecionar_ids_visitante);
                        $stmt_selecionar_visitante->bind_param("i", $id_utilizador_alvo);
                        $stmt_selecionar_visitante->execute();
                        $resultado_ids_visitante = $stmt_selecionar_visitante->get_result();
                        $stmt_selecionar_visitante->close();

                        if ($resultado_ids_visitante->num_rows > 0) {
                            $stmt_inserir_batch = $conn->prepare($sql_inserir_utilizador_alerta);
                            while ($linha_visitante = $resultado_ids_visitante->fetch_assoc()) {
                                $id_destinatario_visitante = $linha_visitante['id'];
                                $stmt_inserir_batch->bind_param("ii", $id_alerta_gerado, $id_destinatario_visitante);
                                $stmt_inserir_batch->execute();
                            }
                            $stmt_inserir_batch->close();
                            $_SESSION['mensagem_sucesso'] = "Alerta geral adicionado com sucesso para visitantes!";
                        } else {
                            $_SESSION['mensagem_erro'] = "Não foram encontrados utilizadores com tipo_utilizador = " . VISITANTE . " para associar o alerta.";
                        }
                        
                    } catch (Exception $e) {
                        error_log("Erro ao processar alerta geral para visitantes: " . $e->getMessage());
                        $_SESSION['mensagem_erro'] = "Erro ao processar alerta geral: " . $e->getMessage();
                    }
                    header("Location: consultar_alertas.php");
                    exit();

                } else {
                    // Alerta individual
                    $sql_associar_utilizador_alerta = "INSERT INTO utilizador_alerta (id_alerta, id_utilizador, data_hora) VALUES (?, ?, NOW())";
                    try {
                        $stmt_associar_ua = $conn->prepare($sql_associar_utilizador_alerta);
                        $stmt_associar_ua->bind_param("ii", $id_alerta_gerado, $id_utilizador_alvo);
                        $linhas_afetadas = $stmt_associar_ua->execute();
                        $stmt_associar_ua->close();
                        if ($linhas_afetadas > 0) {
                            $_SESSION['mensagem_sucesso'] = "Alerta adicionado com sucesso para o utilizador selecionado!";
                        } else {
                            $_SESSION['mensagem_erro'] = "Erro ao associar alerta ao utilizador (nenhuma linha afetada).";
                        }
                    } catch (Exception $e) {
                        error_log("Erro ao adicionar o alerta na tabela utilizador_alerta: " . $e->getMessage());
                        $_SESSION['mensagem_erro'] = "Erro ao adicionar o alerta na tabela utilizador_alerta: " . $e->getMessage();
                    }
                    header("Location: consultar_alertas.php");
                    exit();
                }
            }
        }
    } catch (Exception $e) {
        // Erro geral na obtenção de dados ou processamento inicial
        error_log($e->getMessage());
        $_SESSION['mensagem_erro'] = "Ocorreu um erro inesperado: " . $e->getMessage();
        // Redireciona para uma página de erro ou a página inicial
        header("Location: " . $pagina_inicial);
        exit();
    }

    // Recuperar mensagens da sessão para exibição (opcional, geralmente exibidas na página de destino)
    $mensagem_erro = $_SESSION['mensagem_erro'] ?? null;
    $mensagem_sucesso = $_SESSION['mensagem_sucesso'] ?? null;
    // Remover mensagens da sessão após a exibição para que não apareçam novamente
    if ($mensagem_erro !== null) unset($_SESSION['mensagem_erro']); 
    if ($mensagem_sucesso !== null) unset($_SESSION['mensagem_sucesso']); 

?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="utf-8">
    <title>FelixBus - Adicionar Alerta</title>
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
    <div class="container-fluid hero-header text-light min-vh-100 d-flex align-items-center justify-content-center">

        <nav class="navbar navbar-expand-lg navbar-light px-5 px-lg-5 py-3 py-lg-3">
            <a href="<?= $pagina_inicial ?>" class="navbar-brand p-0">
                <h1 class="text-primary m-0"><i class="fa fa-map-marker-alt me-3"></i>FelixBus</h1>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                <span class="fa fa-bars"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarCollapse">
                <div class="navbar-nav ms-auto py-0">
                    <a href="consultar_rotas.php" class="nav-item nav-link">Rotas</a>
                    <a href="consultar_alertas.php" class="nav-item nav-link active">Alertas</a>
                    
                    <?php if ($tem_login && $tipo_utilizador !== null) { ?>
                        <?php if ($tipo_utilizador == ADMINISTRADOR || $tipo_utilizador == FUNCIONARIO) { ?>
                            <?php if ($tipo_utilizador == ADMINISTRADOR) { ?>
                                <a href="consultar_utilizadores.php" class="nav-item nav-link">Utilizadores</a>
                            <?php } ?>
                            <a href="consultar_bilhetes.php" class="nav-item nav-link">Bilhetes</a>
                        <?php } ?>
                    <?php } ?>
                </div>

                <?php if ($tem_login) { ?>
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link dropdown-toggle" id="walletDropdownLink" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa fa-wallet me-2"></i> 
                            <?= $_SESSION['valor_carteira'] ?? '0,00'; ?> €
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="walletDropdownLink">
                            <li><a class="dropdown-item" href="adicionar_saldo.php"><i class="fas fa-plus-circle"></i>Adicionar</a></li>
                            <li><a class="dropdown-item" href="remover_saldo.php"><i class="fas fa-minus-circle"></i>Remover</a></li>

                            <?php if($tipo_utilizador !== null && ($tipo_utilizador == ADMINISTRADOR || $tipo_utilizador == FUNCIONARIO)) { ?>
                                <li><a class="dropdown-item" href="consultar_saldo_clientes.php"><i class="fas fa-user"></i>Consulta Clientes</a></li>
                            <?php } ?>
                        </ul>
                    </div>
                    <?php if($tipo_utilizador !== null && $tipo_utilizador == CLIENTE) { ?>
                        <div class="nav-item dropdown">
                            <a href="#" class="nav-link dropdown-toggle" id="ticketsDropdownLink" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa fa-ticket-alt me-2"></i> <?= $_SESSION['numero_bilhetes'] ?? '0'; ?>
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="ticketsDropdownLink">
                                <li><a class="dropdown-item" href="consultar_bilhetes.php"><i class="fas fa-eye"></i>Consultar Bilhetes</a></li>
                            </ul>
                        </div>
                    <?php } ?>
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link d-flex align-items-center text-primary me-3 dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa fa-user-circle fa-2x me-2"></i>
                            <span><?= $nome_utilizador_na_sessao ?></span>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="consultar_dados.php"><i class="fas fa-user-cog me-2"></i> Consultar Dados</a></li>
                            <li><a class="dropdown-item" href="sair.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </div>
                <?php } else { ?>
                    <a href="entrar.php" class="btn btn-primary rounded-pill py-2 px-4">Entrar</a>
                <?php } ?>
            </div>
        </nav>
        
        <div class="container-fluid hero-header text-light min-vh-100 d-flex align-items-center justify-content-center">
            <div class="p-5 rounded shadow" style="max-width: 900px; width: 100%;">
                <div class="d-flex justify-content-center align-items-center mb-4">
                    <h3 class="text-white m-0">Adicionar Alerta</h3>
                </div>
                
                <?php if ($mensagem_erro !== null) { ?>
                    <div class="alert alert-danger" role="alert"><?= $mensagem_erro ?></div>
                <?php } ?>
                <?php if ($mensagem_sucesso !== null) { ?>
                    <div class="alert alert-success" role="alert"><?= $mensagem_sucesso ?></div>
                <?php } ?>

                <div class="bg-gradient position-relative w-75 mx-auto mt-5 animated slideInDown">
                    <form method="POST" action="adicionar_alerta.php" class="d-flex flex-wrap p-4 rounded text-light justify-content-center" style="gap: 2rem 0.5rem;">
                        <div class="w-100">
                            <label class="form-label">Utilizador Destino:</label>
                            <select name="id_utilizador" class="form-control bg-dark text-light border-primary" required>
                                <option value="">Selecione um utilizador</option>
                                <?php foreach($utilizadores as $id_utilizador_dropdown => $nome_utilizador_dropdown) { ?>
                                    <option value="<?= $id_utilizador_dropdown ?>"><?= htmlspecialchars($nome_utilizador_dropdown) ?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="w-100">
                            <label class="form-label">Tipo de Alerta:</label>
                            <select name="tipo_alerta" class="form-control bg-dark text-light border-primary" required>
                                <option value="">Selecione o tipo de alerta</option>
                                <?php foreach($tipos_alerta as $id_tipo_alerta_dropdown => $descricao_tipo_alerta_dropdown) { ?>
                                    <option value="<?= $id_tipo_alerta_dropdown ?>"><?= htmlspecialchars($descricao_tipo_alerta_dropdown) ?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="w-100">
                            <label class="form-label">Descrição:</label>
                            <textarea name="descricao" id="descricao" class="form-control bg-dark text-light border-primary" placeholder="Insira a descrição do alerta" rows="4" required></textarea>
                        </div>

                        <div class="w-100 text-center mt-2">
                            <input type="submit" value="Adicionar" class="btn btn-primary text-light px-5 py-2 rounded-pill" />
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

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