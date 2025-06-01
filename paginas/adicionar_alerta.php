<?php
    // Includes da BD e dos dados da navbar
    include "../basedados/basedados.h";
    include "dados_navbar.php";

    // Incia a sessão
    session_start();

    // Variável do estado ao adicionar o alerta
    $estado = 1;

    // Verificar se é admin
    if (!isset($_SESSION['tipo_utilizador']) || $_SESSION['tipo_utilizador'] != 1) {
        $_SESSION['mensagem_erro'] = "Acesso não autorizado!";
        header('Location: consultar_alertas.php');
        exit();
    }

    // Verifica se o utilizador tem o login feito   
    $tem_login = isset($_SESSION['id_utilizador']) && !empty($_SESSION['id_utilizador']); 
    $nome_utilizador = $_SESSION['nome_utilizador'];

    // Determina a página inicial correta baseada no tipo de utilizador
    $pagina_inicial = 'index.php';
    if ($tem_login && isset($_SESSION['tipo_utilizador'])) {
        switch ($_SESSION['tipo_utilizador']) {
            case 1: $pagina_inicial = 'pagina_inicial_admin.php'; break;
            case 2: $pagina_inicial = 'pagina_inicial_func.php'; break;
            case 3: $pagina_inicial = 'pagina_inicial_cliente.php'; break;
        }
    }

    // 1. Obter lista de utilizadores para o dropdown
    $utilizadores = [];
    $sql_utilizadores = "SELECT id, nome_utilizador FROM utilizador WHERE tipo_utilizador != 1 AND id != ?";
    $stmt_utilizadores = $conn->prepare($sql_utilizadores);
    $stmt_utilizadores->bind_param("i", $_SESSION['id_utilizador']);
    $stmt_utilizadores->execute();
    $result = $stmt_utilizadores->get_result();
    while($row = $result->fetch_assoc()) {
        $utilizadores[$row['id']] = $row['nome_utilizador'];
    }
    $stmt_utilizadores->close();

    // 2. Inserir os dados na tabela alerta (Processar POST)
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $descricao = filter_input(INPUT_POST, 'descricao', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $id_utilizador_alvo = filter_input(INPUT_POST, 'id_utilizador', FILTER_VALIDATE_INT);
        $id_admin = $_SESSION['id_utilizador'];

        if (empty($descricao) || $id_utilizador_alvo === false) {
            $_SESSION['mensagem_erro'] = 'Por favor, preencha todos os campos corretamente.';
        } else {
            $sql_alerta = "INSERT INTO alerta (descricao, estado) VALUES (?, ?)";
            $stmt = $conn->prepare($sql_alerta);
            if ($stmt) {
                $stmt->bind_param("si", $descricao , $estado);
                $stmt->execute();
                $stmt->close();
            } else {
                $_SESSION['mensagem_erro'] = "Erro ao adicionar alerta!";
                header("Location: adicionar_alerta.php");
                exit();
            }

            // Obter o ID do alerta inserido
            $id_alerta = $conn->insert_id;

            // 3. Inserir o alerta na tabela utilizador_alerta
            if ($id_utilizador_alvo == 4) {
                // Alerta geral para utilizadores do tipo 4 (Visitante)
                $sql = "SELECT id FROM utilizador WHERE tipo_utilizador = 4";
                $result = $conn->query($sql);

                if ($result && $result->num_rows > 0) {
                    $sql_inserir_alerta = "INSERT INTO utilizador_alerta (id_alerta, id_utilizador, data_hora) VALUES (?, ?, NOW())";
                    $stmt = $conn->prepare($sql_inserri_alerta);

                    if ($stmt) {
                        while ($row = $result->fetch_assoc()) {
                            $id_destinatario = $row['id'];
                            $stmt->bind_param("ii", $id_alerta, $id_destinatario);
                            $stmt->execute();
                        }
                        $_SESSION['mensagem_sucesso'] = 'Alerta geral adicionado com sucesso!';
                        header("Location: consultar_alertas.php");
                        exit();
                    } else {
                        $_SESSION["mensagem_erro"] = "Erro ao preparar inserção de alerta geral: " . $conn->$connect_error;
                    }
                } else {
                    $_SESSION["mensagem_erro"] = "Não foram encontrados utilizadores com tipo_utilizador = 4.";
                }
            } else {
                // Alerta individual (Para os Restantes Utilizadores)
                $sql_utilizador_alerta = "INSERT INTO utilizador_alerta (id_alerta, id_utilizador, data_hora) VALUES (?, ?, NOW())";
                $stmt = $conn->prepare($sql_utilizador_alerta);
                if ($stmt) {
                    $stmt->bind_param("ii", $id_alerta, $id_utilizador_alvo);
                    if ($stmt->execute()) {
                        $_SESSION['mensagem_sucesso'] = 'Alerta adicionado com sucesso!';
                        header("Location: consultar_alertas.php");
                        exit();
                    } else {
                        $_SESSION["mensagem_erro"] = "Erro ao adicionar o alerta na tabela utilizador_alerta: " . $conn->$connect_error;
                    }
                    $stmt->close();
                } else {
                    $_SESSION["mensagem_erro"] = "Erro ao preparar a consulta: " . $conn->$connect_error;
                }
            }
        }
    }
?>

<!------------------------------------------------------------------------------ COMEÇO DO HTML ------------------------------------------------------------------------------->
<!DOCTYPE html>
<html lang="en">

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
                        <a href="consultar_rotas.php" class="nav-item nav-link">Rotas</a>
                        <a href="consultar_alertas.php" class="nav-item nav-link active">Alertas</a>

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
                        <!-- Dropdown da Carteira (Contém o valor da carteira e as opções de Adicionar, Remover e Consulta Clientes) -->
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
            <div class="p-5 rounded shadow" style="max-width: 900px; width: 100%;">
                <div class="d-flex justify-content-center align-items-center mb-4">
                    <h3 class="text-white m-0">Adicionar Alerta</h3>
                </div>
                
                <div class="bg-gradient position-relative w-75 mx-auto mt-5 animated slideInDown">

                    <!-- Formulário com método POST que envia os dados para o adicionar_alerta (esta página) -->
                    <form method="POST" action="adicionar_alerta.php" class="d-flex flex-wrap p-4 rounded text-light justify-content-center" style="gap: 2rem 0.5rem;">
                        <!-- Formulário contém um select do tipo de utilizador para o qual é para enviar o alerta e uma descrição do mesmo -->
                        <div class="w-100">
                            <label class="form-label">Utilizador Destino:</label>
                            <select name="id_utilizador" class="form-control bg-dark text-light border-primary" required>
                                <option value="">Selecione um utilizador</option>
                                <?php foreach ($utilizadores as $id => $nome_utilizador): ?>
                                    <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($nome_utilizador); ?></option>
                                <?php endforeach; ?>
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