<?php

    include 'C:\xampp\htdocs\lpi\Projeto_LPI\basedados\basedados.h';
    
    // Inicia a sessão
    session_start();

    // Verifica se o user já iniciou sessão, senão redireciona para a página de login
    if (!isset($_SESSION['user_id'])) {
        header('Location: entrar.php');
        exit();
    }

    // Obtém o ID do utilizador
    $user_id = $_SESSION['user_id'];

    // Variáveis de mensagens que vão ser apresentadas ao utilizador e o seu tipo (warning, danger, success)
    $message = '';
    $message_type = '';

    // Processa a submissão do formulário para adicionar saldo
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        
        // Obtém o valor que o utilizador quer adicionar através do método POST e valida-o como número decimal
        $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);

        // Verifica se o valor que o utilizador quer adicionar se é um número válido e positivo e exibe uma mensage de erro ao utilizador caso não seja
        if ($amount === false || $amount <= 0) {
            $message = 'Por favor, insira um valor positivo válido.';
            $message_type = 'warning';
        } else {
            // Se o valor introduzido pelo utilizador for válido
            if ($conn) {
                // Preparar a query de SQL para adicionar saldo
                $sql = "UPDATE user SET carteira = carteira + ? WHERE id = ?";
                $stmt = $conn->prepare($sql);

                if ($stmt) {
                    // Associar os parâmetros: 'd' para double (float), 'i' para integer
                    $stmt->bind_param("di", $amount, $user_id);

                    // Executar o statement
                    if ($stmt->execute()) {
                        // Verifica se alguma linha foi afetada, se sim o saldo do utilizador foi atualizado e é exibida uma mensagem de sucesso
                        if ($stmt->affected_rows > 0) {
                            $message = 'Fundos adicionados com sucesso!';
                            $message_type = 'success';
                        } else {
                            // Se o utilizador não existir na base de dados, exibir uma mensagem de erro
                            $message = 'Erro: Utilizador não encontrado ou saldo não atualizado.';
                            $message_type = 'warning';
                        }
                    } else { 
                        // Tratar do caso de erro do statement, exibir uma mensagem de erro
                        $message = 'Erro ao adicionar fundos na base de dados.';
                        $message_type = 'danger';
                    }

                    // Fechar o statement
                    $stmt->close();
                } else {
                    // Tratar do caso de erro do statement (preparar a query), exibir uma mensagem de erro
                    $message = 'Erro interno ao preparar a query.';
                    $message_type = 'danger';
                }

                // Fechar a conexão com a base de dados
                $conn->close();
            } else {
                // Se não possível executar a conexão com a base de dados, exibir uma mensagem de erro
                $message = 'Erro: Falha na conexão com a base de dados.';
                $message_type = 'danger';
            }
        }

        // Quando o saldo tiver atualizado com sucesso, redirecionar para a página principal 
        header('Location: index.php');
        exit();
    }
?>

<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="utf-8">
        <title>FelixBus - Entrar</title>
        <meta content="width=device-width, initial-scale=1.0" name="viewport">
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
                <h3 class="text-center text-white mb-4">Adicionar Saldo</h3>

                <?php
                // Apresenta mensagem se existir
                if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php
                    
                    // Remover as variáveis de sessão depois de apresentadas
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                endif;
                ?>

                <!-- FORMULÁRIO -->
                <form action="adicionar_saldo.php" method="POST">
                    <div class="mb-3">
                        <label for="amount" class="form-label">Quanto dinheiro (€) pretende adicionar?</label>
                        <input name="amount" id="amount" type="number" step="0.01" min="0.01" class="form-control text-dark" required/>
                    </div>
                    <div class="d-flex justify-content-center">
                        <input type="submit" value="Adicionar Saldo" class="btn btn-primary rounded-pill py-2 px-5">
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