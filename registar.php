<?php
    // Include
    include 'C:\xampp\htdocs\lpi\Projeto_LPI\basedados\basedados.h';
    include 'constUtilizadores.php';

    // Iniciar a sessão
    session_start();

    // Verificar se algum dos campos está vazio
    if(isset($_POST['nome']) && isset($_POST['password']) && $_POST['password'] === $_POST['confirm-pass']) {
        
        // Dados recebidos do formulário
        $user = $_POST['nome'];
        $pass = $_POST['password'];
        $tipo_user = CLIENTE;

        $sql = "INSERT INTO user (nome, password, tipo_user) VALUES ('$user', '$pass', '$tipo_user')";
        $res = mysqli_query($conn, $sql);

        if ($res) {
            echo "<script>alert('Utilizador registado com sucesso!');</script>";
            header("refresh:0; url=entrar.php");
        } else {
            // Erro na query
            echo "<script>alert('Erro ao registar utilizador: " . mysqli_error($conn) . "');</script>";
        }
    
    } else {
        // Erro na validação do formulário
        echo
        "<script>
            alert('Erro: Os campos estão vazios ou as passwords não coincidem.');
            window.location.href = 'registar.php'; // ou o nome da sua página de registo
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

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="lib/tempusdominus/css/tempusdominus-bootstrap-4.min.css" rel="stylesheet" />

    <!-- Customized Bootstrap Stylesheet -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="css/style.css" rel="stylesheet">
</head>

<body>
    <!-- Spinner Start -->
    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
    <!-- Spinner End -->

<!-- Start Login Section -->
<div class="container-fluid hero-header text-light min-vh-100 d-flex align-items-center justify-content-center">
    <div class="p-5 rounded shadow" style="max-width: 700px; width: 100%;">
        <h3 class="text-center text-white mb-4">Registar</h3>
        <form action="registar.php" method="POST">
            <div class="mb-3">
                <label for="user" class="form-label">Nome de Utilizador:</label>
                <input name="nome" id="user" type="text" class="form-control text-dark" required />
            </div>
            <div class="mb-3">
                <label for="pass" class="form-label">Palavra-Passe:</label>
                <input name="password" id="pass" type="password" class="form-control text-dark" required />
            </div>
            <div class="mb-3">
                <label for="confirm-pass" class="form-label">Confirmar Palavra-Passe:</label>
                <input name="confirm-pass" id="confirm-pass" type="password" class="form-control text-dark" required />
            </div>
            <div class="d-flex mt-5 justify-content-center">
                <input type="submit" value="Registar" class="btn btn-primary rounded-pill py-2 px-5">
            </div>
        </form>
    </div>
</div>
<!-- End Login Section -->

    <!-- JavaScript Libraries -->
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