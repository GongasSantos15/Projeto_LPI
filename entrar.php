<?php

    // Inclui a conexão com a base de dados
    // Certifique-se que basedados.h define a variável $conn
    include 'C:\xampp\htdocs\lpi\Projeto_LPI\basedados\basedados.h';
    // Inclui as constantes de utilizadores (se necessário para CLIENTE_APAGADO)
    include 'constUtilizadores.php';

    // Iniciar ou retomar a sessão
    session_start();

    $errorMessage = ''; // Variável para guardar mensagens de erro a serem exibidas na página

    // --- PROCESSAMENTO DO LOGIN (APENAS EM POST) ---
    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        // Verificar se os campos não estão vazios (redundante com 'required' no HTML, mas seguro)
        if (isset($_POST["nome"]) && isset($_POST["password"])) {

            // Dados do formulário (obtidos de forma segura)
            $user = $_POST["nome"];
            $password = $_POST["password"]; // ATENÇÃO: Passwords devem ser hashed, não guardadas em texto simples!

            // --- USANDO PREPARED STATEMENT PARA SEGURANÇA CONTRA SQL INJECTION ---

            // Prepara a query com placeholders (?)
            // A consulta original é boa para evitar login de tipos 'apagados'
            $sql = "SELECT id, nome, tipo_user FROM user WHERE nome = ? AND password = ? AND tipo_user != ?";
            $stmt = mysqli_prepare($conn, $sql);

            // Verifica se a preparação da query falhou
            if ($stmt === false) {
                // Em ambiente de produção, mostre uma mensagem genérica ao utilizador e logue o erro real
                $errorMessage = 'Erro interno na base de dados. Tente novamente mais tarde.';
                error_log('Erro na preparação da query: ' . mysqli_error($conn)); // Loga o erro real
            } else {
                // Liga os parâmetros (dados do formulário) aos placeholders
                // "ssi" indica que são 2 strings (nome, password) e 1 inteiro (tipo_user)
                $cliente_apagado = CLIENTE_APAGADO; // Define a variável para o binding
                mysqli_stmt_bind_param($stmt, "ssi", $user, $password, $cliente_apagado);

                // Executa a query
                mysqli_stmt_execute($stmt);

                // Obtém o resultado da query
                $result = mysqli_stmt_get_result($stmt);

                // Verifica se encontrou exatamente 1 utilizador (login válido)
                if ($result && mysqli_num_rows($result) == 1) {
                    // Obtém os dados do utilizador
                    $row = mysqli_fetch_assoc($result);

                    // Autenticação bem-sucedida! Guarda os dados na sessão.
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['nome'] = $row['nome'];
                    $_SESSION['tipo'] = $row['tipo_user'];

                    mysqli_free_result($result); // Libertar memória do resultado
                    mysqli_stmt_close($stmt); // Fecha o statement
                    // Fechar a conexão com a base de dados (se aberta e não fechada antes)
                    // if (isset($conn)) { mysqli_close($conn); } // Depende de como basedados.h gere a conexão

                    // Redireciona para a página inicial
                    header("Location: index.php");
                    exit(); // Termina a execução do script após o redirecionamento

                } else {
                    // Login falhou (utilizador não encontrado ou password incorreta ou tipo apagado)
                    $errorMessage = 'Utilizador ou password incorretos.';
                    mysqli_free_result($result); // Libertar memória
                }

                mysqli_stmt_close($stmt); // Fecha o statement (se não redirecionou)
            }

        } else {
             // Campos não preenchidos no POST (embora 'required' no HTML minimize isso)
            $errorMessage = 'Erro: Por favor, preencha o nome e a password.';
        }

         // Fechar a conexão com a base de dados se ela foi aberta e não será mais usada nesta requisição
         // if (isset($conn)) { mysqli_close($conn); } // Depende de como basedados.h gere a conexão

    } // --- FIM DO PROCESSAMENTO POST ---

    // Se a requisição não foi POST, ou se o POST falhou, o código continua e exibe o HTML abaixo.
    // A variável $errorMessage conterá a mensagem de erro, se houver.

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
        <h3 class="text-center text-white mb-4">Entrar</h3>

        <?php
        // Exibe a mensagem de erro se ela estiver definida (após um POST falhado)
        if ($errorMessage) {
            echo '<div class="alert alert-danger" role="alert">' . htmlspecialchars($errorMessage) . '</div>';
        }
        ?>

        <form action="entrar.php" method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">Nome de Utilizador:</label>
                <input name="nome" id="username" type="text" class="form-control text-dark" required />
            </div>
            <div class="mb-3">
                <label for="pass" class="form-label">Palavra-Passe:</label>
                <input name="password" id="pass" type="password" class="form-control text-dark" required />
            </div>
            <div class="d-flex justify-content-center">
                <input type="submit" value="Entrar" class="btn btn-primary rounded-pill py-2 px-5">
            </div>
        </form>
        <div class="text-center mt-5">
            <span>Não tem conta? <a href="registar.php" class="text-info">Registe-se aqui</a></span>
        </div>
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