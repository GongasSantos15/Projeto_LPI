<?php
    // Inicia a sessão
    session_start();


    // Include BD
    include 'C:\xampp\htdocs\lpi\Projeto_LPI\basedados\basedados.h';

    // Verifica se o utilizador tem a sessão iniciada, inicia as variáveis de sessão para os alertas e redireciona para a página de login
    if (!isset($_SESSION['id_utilizador'])) {
        $_SESSION['adicionar-mensagem'] = 'Precisa de estar autenticado para remover fundos.';
        $_SESSION['tipo-mensagem-add'] = 'danger';
        header('Location: entrar.php');
        exit();
    }

    // Variáveis para o id de utilizador, mensagem e tipo de mensagem
    $id_utilizador = $_SESSION['id_utilizador'];
    $mensagem = '';
    $tipo_mensagem = '';

    // Processa o formulário para remover o saldo indicado pelo utilizador
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        
        // Validar o valor do input (valor)
        $valor = filter_input(INPUT_POST, 'valor', FILTER_VALIDATE_FLOAT);

        // Verifica se o valor é inválido ou negativo e exibe uma mensagem de erro
        if ($valor === false || $valor <= 0) {
            $mensagem = 'Por favor, insira um valor positivo válido.';
            $tipo_mensagem = 'warning';
        } else {
            
            // Se for efetuada a conexão à base de dados
            if ($conn) {
                $verificar_saldo_sql = "SELECT carteira FROM user WHERE id = ?";
                $stmt = $conn->prepare($verificar_saldo_sql);

                if ($stmt) {
                    $stmt->bind_param("i", $id_utilizador);
                    $stmt->execute();
                    $res = $stmt->get_result();

                    if ($res && $res->num_linhas > 0) {
                        $linhas = $res->fetch_assoc();
                        $valor_carteira = $linhas['carteira'];

                        // Verifica se o valor atual na carteira é suficiente
                        if ($valor_carteira >= $valor) {
                            // Saldo é suficiente, remover o saldo
                            $stmt->close();

                            // SQL para remoção do valor e respetivo statement
                            $remover_sql = "UPDATE user SET carteira = carteira - ? WHERE id = ?";
                            $stmt_remover = $conn->prepare($remover_sql);

                            // Atribuir os valores aos respetivos parâmetros
                            if ($stmt_remover) {
                                $stmt_remover->bind_param("di", $valor, $id_utilizador);

                                // Executar a query SQL
                                if ($stmt_remover->execute()) {
                                    if ($stmt_remover->linhas_afetadas > 0) {
                                        $mensagem = 'Fundos removidos com sucesso!';
                                        $tipo_mensagem = 'success';
                                        header("refresh:2; url = index.php");
                                    } else {
                                        $mensagem = 'Erro: Utilizador não encontrado ou saldo não atualizado.';
                                        $tipo_mensagem = 'warning';
                                    }
                                } else {
                                    $mensagem = 'Erro ao remover fundos na base de dados.';
                                    $tipo_mensagem = 'danger';
                                }
                                $stmt_remover->close();

                            } else {
                                $mensagem = 'Erro interno ao preparar a query de remoção.';
                                $tipo_mensagem = 'danger';
                            }

                        } else {
                            // O saldo é insuficiente, exibir mensagem de erro
                            $mensagem = 'Saldo insuficiente. Não pode remover mais do que tem na carteira.';
                            $tipo_mensagem = 'warning';
                            $stmt->close(); // Close the check statement
                        }

                    } else {
                        $mensagem = 'Erro: Não foi possível verificar o seu saldo atual.';
                        $tipo_mensagem = 'danger';
                        if ($stmt) $stmt->close();
                    }

                } else {
                    $mensagem = 'Erro interno ao preparar a query de verificação de saldo.';
                    $tipo_mensagem = 'danger';
                }

                // Close the database connection if it's still open
                //if ($conn) {
                //     $conn->close();
                //}
            } else {
                $mensagem = 'Erro: Falha na conexão com a base de dados.';
                $tipo_mensagem = 'danger';
            }

            if (!empty($mensagem)) {
                $_SESSION['mensagem-add'] = $mensagem;
                $_SESSION['tipo_mensagem-add'] = $tipo_mensagem;
            }
        }
        //exit();
    }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>FelixBus - Remover Fundos</title><meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <link href="img/favicon.ico" rel="icon">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Nunito:wght@600;700;800&display=swap" rel="stylesheet">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="lib/tempusdominus/css/tempusdominus-bootstrap-4.min.css" rel="stylesheet" />

    <link href="css/bootstrap.min.css" rel="stylesheet">

    <link href="css/style.css" rel="stylesheet">
</head>

<body>
    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
    <div class="container-fluid hero-header text-light min-vh-100 d-flex align-items-center justify-content-center">
    <div class="p-5 rounded shadow" style="max-width: 700px; width: 100%;">
            <h3 class="text-center text-white mb-4">Remover Saldo</h3>

            <?php
            // Mostrar mensagem se a sessão existe
            if (isset($_SESSION['mensagem-add'])): ?>
                <div class="alert alert-<?php echo $_SESSION['tipo_mensagem-add']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['mensagem-add']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php
                // Remover as variáveis de sessão depois de ser exibida
                unset($_SESSION['mensagem-add']);
                unset($_SESSION['tipo_mensagem-add']);
            endif;
            ?>

            <!-- Formulário para remover o saldo -->
            <form action="remover_saldo.php" method="POST">
                <div class="mb-3">
                    <label for="valor" class="form-label">Quanto dinheiro (€) pretende remover?</label>
                    <input name="valor" id="valor" type="number" step="0.01" min="0.01" class="form-control text-dark" required/>
                </div>
                <div class="d-flex justify-content-center">
                    <input type="submit" value="Remover Saldo" class="btn btn-primary rounded-pill py-2 px-5">
                </div>
            </form>
        </div>
</div>
<script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/wow/wow.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="lib/tempusdominus/js/moment.min.js"></script>
    <script src="lib/tempusdominus/js/moment-timezone.min.js"></script>
    <script src="lib/tempusdominus/js/tempusdominus-bootstrap-4.min.js"></script>

    <script src="js/main.js"></script>
</body>

</html>