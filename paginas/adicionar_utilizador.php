<?php
    include "../basedados/basedados.h";
    include "dados_navbar.php";

    session_start();

    // Verificar se é admin
    if (!isset($_SESSION['tipo_utilizador']) || $_SESSION['tipo_utilizador'] != 1) {
        $_SESSION['mensagem_erro'] = "Acesso não autorizado!";
        header('Location: consultar_utilizadores.php');
        exit();
    }

    // Verifica se o utilizador tem o login feito   
    $tem_login = isset($_SESSION['id_utilizador']) && !empty($_SESSION['id_utilizador']); 
    $nome_utilizador = $_SESSION['nome_utilizador'];

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

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $nome_proprio = filter_input(INPUT_POST, 'nome_proprio', FILTER_SANITIZE_STRING);
        $nome_utilizador = filter_input(INPUT_POST, 'nome_utilizador', FILTER_SANITIZE_STRING);
        $palavra_passe = filter_input(INPUT_POST, 'palavra_passe', FILTER_SANITIZE_STRING);
        $tipo_utilizador = filter_input(INPUT_POST, 'tipo_utilizador', FILTER_VALIDATE_INT);
        $id_carteira = filter_input(INPUT_POST, 'id_carteira', FILTER_VALIDATE_INT);

        // Verificar se todos os campos estão preenchidos
        if (empty($id) || empty($nome_proprio) || empty($nome_utilizador) || empty($palavra_passe) || empty($tipo_utilizador) || empty($id_carteira)) {
            $_SESSION['mensagem_erro'] = 'Por favor, preencha todos os campos.';
        } else {
            // Encriptar a palavra-passe DEPOIS da validação
            $palavra_passe_encriptada = md5($palavra_passe);
            
            // Verificar se o ID ou nome de utilizador já existem
            $sql_check = "SELECT id FROM utilizador WHERE id = ? OR nome_utilizador = ?";
            $stmt_check = $conn->prepare($sql_check);
            
            if ($stmt_check) {
                $stmt_check->bind_param("is", $id, $nome_utilizador);
                $stmt_check->execute();
                $result = $stmt_check->get_result();
                
                if ($result->num_rows > 0) {
                    $_SESSION['mensagem_erro'] = 'ID ou nome de utilizador já existem!';
                } else {
                    // Inserir o utilizador na base de dados
                    $sql = "INSERT INTO utilizador (id, nome_proprio, nome_utilizador, palavra_passe, tipo_utilizador, id_carteira) VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);

                    if ($stmt) {
                        $stmt->bind_param("isssii", $id, $nome_proprio, $nome_utilizador, $palavra_passe_encriptada, $tipo_utilizador, $id_carteira);
                        if ($stmt->execute()) {
                            $_SESSION['mensagem_sucesso'] = 'Utilizador adicionado com sucesso!';
                            header("Location: consultar_utilizadores.php");
                            exit(); // Importante: sempre usar exit() após header redirect
                        } else {
                            $_SESSION['mensagem_erro'] = 'Erro ao adicionar o utilizador: ' . $stmt->error; // Corrigido: $stmt->error em vez de $stmt->$connect_error
                        }
                        $stmt->close();
                    } else {
                        $_SESSION['mensagem_erro'] = 'Erro ao preparar a consulta: ' . $conn->$connect_error; // Corrigido: $conn->error em vez de $conn->$connect_error
                    }
                }
                $stmt_check->close();
            } else {
                $_SESSION['mensagem_erro'] = 'Erro ao verificar utilizador: ' . $conn->$connect_error;
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="utf-8">
    <title>FelixBus - Adicionar Utilizador</title>
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
                    <a href="consultar_alertas.php" class="nav-item nav-link">Alertas</a>

                    <?php if ($tem_login && isset($_SESSION['tipo_utilizador'])) : ?>
                        <?php if (in_array($_SESSION['tipo_utilizador'], [1, 2])): ?>
                            <?php if ($_SESSION['tipo_utilizador'] == 1): ?>
                                <a href="consultar_utilizadores.php" class="nav-item nav-link active">Utilizadores</a>
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

        <div class="container-fluid hero-header text-light min-vh-100 d-flex align-items-center justify-content-center">
            <div class="p-5 rounded shadow" style="max-width: 900px; width: 100%;">
                <div class="d-flex justify-content-center align-items-center mb-4">
                    <h3 class="text-white m-0">Adicionar Utilizadores</h3>
                </div>

                <!-- Mostrar mensagens de erro ou sucesso -->
                <?php if (isset($_SESSION['mensagem_erro'])): ?>
                    <div class="alert alert-danger text-center">
                        <?php echo $_SESSION['mensagem_erro']; unset($_SESSION['mensagem_erro']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['mensagem_sucesso'])): ?>
                    <div class="alert alert-success text-center">
                        <?php echo $_SESSION['mensagem_sucesso']; unset($_SESSION['mensagem_sucesso']); ?>
                    </div>
                <?php endif; ?>

                <div class="bg-gradient position-relative w-100 mx-auto mt-5 animated slideInDown">
                    <form method="POST" action="adicionar_utilizador.php" class="d-flex flex-wrap p-4 rounded text-light justify-content-center" style="gap: 2rem 0.5rem;">
                        <div class="me-4">
                            <label class="form-label">ID do Utilizador:</label>
                            <input type="number" name="id" id="id" class="form-control bg-dark text-light border-primary" placeholder="Insira o ID do utilizador" required />
                        </div>

                        <div class="me-4">
                            <label class="form-label">Nome Próprio:</label>
                            <input type="text" name="nome_proprio" id="nome_proprio" class="form-control bg-dark text-light border-primary" placeholder="Insira o nome próprio" required />
                        </div>

                        <div class="me-4">
                            <label class="form-label">Nome Utilizador:</label>
                            <input type="text" name="nome_utilizador" id="nome_utilizador" class="form-control bg-dark text-light border-primary" placeholder="Insira o nome de utilizador" required />
                        </div>

                        <div class="me-4">
                            <label class="form-label">Palavra Passe:</label>
                            <input type="password" name="palavra_passe" id="palavra_passe" class="form-control bg-dark text-light border-primary" placeholder="Insira a palavra passe" required />
                        </div>

                        <div class="me-4">
                            <label class="form-label">Tipo Utilizador:</label>
                            <select name="tipo_utilizador" id="tipo_utilizador" class="form-control bg-dark text-light border-primary" required>
                                <option value="">Selecione o tipo</option>
                                <option value="1">Administrador</option>
                                <option value="2">Utilizador Normal</option>
                            </select>
                        </div>

                        <div class="me-4">
                            <label class="form-label">ID Carteira:</label>
                            <input type="number" name="id_carteira" id="id_carteira" class="form-control bg-dark text-light border-primary" placeholder="Insira o ID da carteira" required />
                        </div>

                        <div class="w-100 text-center mt-2">
                            <input type="submit" value="Adicionar" class="btn btn-primary text-light px-5 py-2 rounded-pill" />
                        </div>
                    </form>
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