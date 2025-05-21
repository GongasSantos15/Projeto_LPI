<?php
    // Include
    include 'C:\xampp\htdocs\lpi\Projeto_LPI\basedados\basedados.h';
    include 'constUtilizadores.php';

    // Iniciar a sessão
    session_start();

    // Verificar se algum dos campos está vazio
    // Se sim exibe um alerta ao utilizador
    if(isset($_POST['nome']) && isset($_POST['palavra_passe']) && $_POST['palavra_passe'] === $_POST['confirmar-palavra-passe']) {
        
        // Dados recebidos do formulário
        $nome_utilizador = $_POST['nome'];
        $palavra_passe = $_POST['palavra_passe'];
        $tipo_nome_utilizador = CLIENTE;

        // Query SQL para inserir na BD os dados do utilizador (nome próprio, nome de utilizador e tipo)
        $sql = "INSERT INTO nome_utilizador (nome, palavra_passe, tipo_nome_utilizador) VALUES ('$nome_utilizador', '$palavra_passe', '$tipo_nome_utilizador')";
        $res = mysqli_query($conn, $sql);

        // Se existe resultado, exibe uma alerta ao utilizador e redireciona para a página de login
        // Senão exibe uma mensagem de erro (alerta)
        if ($res) {
            echo "<script>alert('Utilizador registado com sucesso!');</script>";
            header("refresh:0; url=entrar.php");
        } else {
            echo "<script>alert('Erro ao registar utilizador: " . mysqli_error($conn) . "');</script>";
        }
    
    } else {
        echo
        "<script>
            alert('Erro: Os campos estão vazios ou as palavras passe não coincidem.');
            window.location.href = 'registar.php';
        </script>";
    }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>FelixBus - Registar</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <!-- Favicon -->
    <link href="img/favicon.ico" rel="icon">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Nunito:wght@600;700;800&display=swap" rel="stylesheet">

    <!-- Estilos de Fonte -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Bibliotecas -->
    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="lib/tempusdominus/css/tempusdominus-bootstrap-4.min.css" rel="stylesheet" />

    <!-- Bootstrap -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- Template -->
    <link href="css/style.css" rel="stylesheet">
</head>

<body>
    <!-- Roda de Carregamento -->
    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
    <!-- Spinner End -->

<!-- Secção de Registo -->
<div class="container-fluid hero-header text-light min-vh-100 d-flex align-items-center justify-content-center">
    <div class="p-5 rounded shadow" style="max-width: 700px; width: 100%;">
        <h3 class="text-center text-white mb-4">Registar</h3>
        <form action="registar.php" method="POST">
            <div class="mb-3">
                <label for="nome_utilizador" class="form-label">Nome Próprio:</label>
                <input name="nome-completo" id="nome_utilizador" type="text" class="form-control text-dark" required />
            </div>
            <div class="mb-3">
                <label for="nome_utilizador" class="form-label">Nome de Utilizador:</label>
                <input name="nome" id="nome_utilizador" type="text" class="form-control text-dark" required />
            </div>
            <div class="mb-3">
                <label for="palavra-passe" class="form-label">Palavra Passe:</label>
                <input name="palavra-passe" id="palavra-passe" type="password" class="form-control text-dark" required />
            </div>
            <div class="mb-3">
                <label for="confirmar-palavra-passe" class="form-label">Confirmar Palavra Passe:</label>
                <input name="confirmar-palavra-passe" id="confirmar-palavra-passe" type="palavra-password" class="form-control text-dark" required />
            </div>
            <div class="d-flex mt-5 justify-content-center">
                <input type="submit" value="Registar" class="btn btn-primary rounded-pill py-2 px-5">
            </div>
        </form>
    </div>
</div>
<!-- Fim da Secção de Registo -->

    <!-- Bibliotecas JavaScript -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/wow/wow.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="lib/tempusdominus/js/moment.min.js"></script>
    <script src="lib/tempusdominus/js/moment-timezone.min.js"></script>
    <script src="lib/tempusdominus/js/tempusdominus-bootstrap-4.min.js"></script>

    <!-- Template Javascript -->
    <script src="js/main.js"></script>
</body>

</html>