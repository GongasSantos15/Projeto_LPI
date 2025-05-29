<?php

    include 'C:\xampp\htdocs\lpi\Projeto_LPI\basedados\basedados.h';
    include 'constUtilizadores.php';

    // Inicia a sessão
    session_start();

    // Verifica se o user já iniciou sessão, senão redireciona para a página de login
    if (!isset($_SESSION['id_utilizador'])) {
        header('Location: entrar.php');
        exit();
    }

    // Obtém o ID do utilizador
    $id_utilizador = $_SESSION['id_utilizador'];
    $tipo_utilizador = $_SESSION['tipo_utilizador'];
    
    // Variáveis de mensagens que vão ser apresentadas ao utilizador
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

    // Processa a submissão do formulário para adicionar saldo
    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        // Obtém o valor que o utilizador quer adicionar através do método POST e valida-o como número decimal
        $valor = filter_input(INPUT_POST, 'valor', FILTER_VALIDATE_FLOAT);

        // Verifica se o valor que o utilizador quer adicionar se é um número válido e positivo e exibe uma mensage de erro ao utilizador caso não seja
        if ($valor === false || $valor <= 0) {
            $mensagem_erro = 'Por favor, insira um valor positivo válido.';
        } else {
            // Se o valor introduzido pelo utilizador for válido
            if ($conn) {
                // 1º Passo - Selecionar o utilizador com a carteira
                $sql = "SELECT id_carteira FROM utilizador WHERE id = ?";
                $stmt = $conn->prepare($sql);

                if($stmt) {
                    $stmt->bind_param("i", $id_utilizador);
                    $stmt->execute();
                    $resultado = $stmt->get_result();

                    if ($resultado && $resultado->num_rows > 0) {
                        $linha = $resultado->fetch_assoc();
                        $id_carteira = $linha['id_carteira'];

                        // 2º Passo - Selecionar o valor da carteira correspondente ao utilizador
                        $sql_carteira = "UPDATE carteira SET saldo = saldo - ? WHERE id_carteira = ?";
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
                    // Se não possível preparar a query, exibir uma mensagem de erro
                    $mensagem_erro = 'Erro ao preparar a query para obter a carteira.';
                }

                // Fechar a conexão com a base de dados
                $conn->close();

            } else {
                // Se não possível executar a conexão com a base de dados, exibir uma mensagem de erro
                $mensagem_erro = 'Erro: Falha na conexão com a base de dados.';
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="utf-8">
        <title>FelixBus - Remover Saldo</title>
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
    </head>

    <body>
        <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                <span class="sr-only">Loading...</span>
            </div>
        </div>
        <div class="container-fluid hero-header text-light min-vh-100 d-flex align-items-center justify-content-center">
        <div class="p-5 rounded shadow" style="max-width: 700px; width: 100%;">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="text-white m-0">Levantar Saldo</h3>
                <a href="<?php echo htmlspecialchars($pagina_inicial) ?>" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-arrow-left me-2"></i>Voltar ao Início
                </a>
            </div>

                    <?php
                        if (!empty($mensagem_erro)) {
                            echo '<div class="alert alert-danger">' . htmlspecialchars($mensagem_erro) . '</div>';
                        }
                        if (!empty($mensagem_sucesso)) {
                            echo '<div class="alert alert-success">' . htmlspecialchars($mensagem_sucesso) . '</div>';
                            echo '<script>
                                setTimeout(function() {
                                    window.location.href = "index.php";
                                }, 2000);
                            </script>';
                        }
                    ?>

                    <div class="bg-gradient mb-3 p-5 position-relative mx-auto mt-3 animated slideInDown">
                        <form action="remover_saldo.php" method="POST">
                        <div class="mb-3">
                            <label for="valor" class="form-label">Quanto dinheiro (€) pretende levantar?</label>
                            <input name="valor" id="valor" type="number" step="0.01" min="0.01" class="form-control text-dark" required/>
                        </div>
                        <div class="d-flex justify-content-center">
                            <input type="submit" value="Levantar Saldo" class="btn btn-success rounded-pill py-2 px-5">
                        </div>
                    </form>
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