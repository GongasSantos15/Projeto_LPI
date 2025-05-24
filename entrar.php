<?php

    // Include base de dados
    include 'C:\xampp\htdocs\lpi\Projeto_LPI\basedados\basedados.h';
    
    // Include às constantes de utilizadores 
    include 'constUtilizadores.php';

    // Iniciar Sessão
    session_start();

    $mensagem_erro = ''; 

    // Se o método for POST
    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        // Verificar se os campos não estão vazios (redundante com 'required' no HTML, mas seguro)
        if (isset($_POST["nome_utilizador"]) && isset($_POST["palavra_passe"])) {

            // Dados do formulário (obtidos de forma segura)
            $nome_utilizador = $_POST["nome_utilizador"];
            $palavra_passe = $_POST["palavra_passe"];

            // Prepara a query SQL
            $sql = "SELECT id, nome_utilizador, tipo_utilizador FROM utilizador WHERE nome_utilizador = ? AND palavra_passe = ?";
            $stmt = $conn->prepare($sql);

            // Verifica se a preparação da query falhou, se falhar mostra uma mensagem de erro
            // Senão liga os parâmetros (dados do formulário) às variáveis corretas ("ssi" indica que existem 2 strings e 1 inteiro)
            if ($stmt === false) {
                $mensagem_erro = 'Erro interno na base de dados. Tente novamente mais tarde.';
            } else {
                $stmt->bind_param("ss", $nome_utilizador, $palavra_passe);

                // Executa a query
                $stmt->execute();

                // Obtém o resultado da query
                $resultado = $stmt->get_result();

                // Verifica se encontrou exatamente 1 utilizador, obtém os dados do mesmo, guarda nas variáveis de sessão e redireciona para a página inicial se tudo correr bem
                if ($resultado && mysqli_num_rows($resultado) == 1) {
                    // Obtém os dados do utilizador
                    $linha = mysqli_fetch_assoc($resultado);

                    // Autenticação bem-sucedida! Guarda os dados na sessão.
                    $_SESSION['id_utilizador'] = $linha['id'];
                    $_SESSION['nome_utilizador'] = $linha['nome_utilizador'];
                    $_SESSION['tipo_utilizador'] = $linha['tipo_utilizador'];

                    $stmt->close();

                    header("Location: index.php");
                    exit();

                } else {
                    $mensagem_erro = 'Utilizador ou palavra-passe incorretos.';
                }

                $stmt->close();
            }

        }

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
        <h3 class="text-center text-white mb-4">Entrar</h3>

        <?php
        // Exibe a mensagem de erro
        if ($mensagem_erro) {
            echo '<div class="alert alert-danger" role="alert">' . htmlspecialchars($mensagem_erro) . '</div>';
        }
        ?>

        <form action="entrar.php" method="POST">
            <div class="mb-3">
                <label for="nome_utilizador" class="form-label">Nome de Utilizador:</label>
                <input name="nome_utilizador" id="nome_utilizador" type="text" class="form-control text-dark" required />
            </div>
            <div class="mb-3">
                <label for="palavra_passe" class="form-label">Palavra-Passe:</label>
                <input name="palavra_passe" id="palavra_passe" type="password" class="form-control text-dark" required />
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