<?php
    include 'C:\xampp\htdocs\lpi\Projeto_LPI\basedados\basedados.h';
    include 'constUtilizadores.php';

    session_start();

    $mensagem_erro = '';
    $mensagem_sucesso = '';

    if($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Verifique os nomes dos campos exatamente como estão no formulário HTML
        if(!empty($_POST['nome_utilizador']) && !empty($_POST['nome_proprio']) && !empty($_POST['palavra_passe']) && !empty($_POST['confirmar_palavra_passe'])) {
            
            // Use o mesmo nome que está no formulário (com underscore)
            if($_POST['palavra_passe'] === $_POST['confirmar_palavra_passe']) {
                $nome_utilizador = mysqli_real_escape_string($conn, $_POST['nome_utilizador']);
                $nome_proprio = mysqli_real_escape_string($conn, $_POST['nome_proprio']);
                $palavra_passe_encriptada = md5($_POST['palavra_passe']);
                $tipo_nome_utilizador = CLIENTE;

                $sql = "INSERT INTO utilizador (nome_utilizador, nome_proprio, palavra_passe, tipo_utilizador) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                
                if (!$stmt) {
                    $mensagem_erro = "Erro ao preparar a consulta.";
                } else {
                    mysqli_stmt_bind_param($stmt, "sssi", $nome_utilizador, $nome_proprio, $palavra_passe_encriptada, $tipo_nome_utilizador);

                    if ($stmt->execute()){
                        $mensagem_sucesso = "Utilizador registado com sucesso";
                        header("Location: entrar.php");
                        exit();
                    } else {
                        $mensagem_erro = "Erro ao registar utilizador: " . mysqli_error($conn);
                    }
                }
            } else {
                $mensagem_erro = "As palavras passe não coincidem.";
            }
        }
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
                <label for="nome_proprio" class="form-label">Nome Próprio:</label>
                <input name="nome_proprio" id="nome_proprio" type="text" class="form-control text-dark" required />
            </div>
            <div class="mb-3">
                <label for="nome_utilizador" class="form-label">Nome de Utilizador:</label>
                <input name="nome_utilizador" id="nome_utilizador" type="text" class="form-control text-dark" required />
            </div>
            <div class="mb-3">
                <label for="palavra_passe" class="form-label">Palavra-passe:</label>
                <div class="input-group">
                    <input name="palavra_passe" id="palavra_passe" type="password" class="form-control text-dark" required />
                    <button class="btn btn-outline-light border-start-0" type="button" id="mostraPalavraPasse">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            <div class="mb-3">
                <label for="confirmar_palavra_passe" class="form-label">Palavra-passe:</label>
                <div class="input-group">
                    <input name="confirmar_palavra_passe" id="confirmar_palavra_passe" type="password" class="form-control text-dark" required />
                    <button class="btn btn-outline-light border-start-0" type="button" id="mostraConfirmarPalavraPasse">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            <div class="d-flex mt-5 justify-content-center">
                <input type="submit" value="Registar" class="btn btn-primary rounded-pill py-2 px-5">
            </div>
        </form>
        <div class="text-center mt-5">
            <span>Já tem conta? <a href="entrar.php" class="text-info"> Inicie sessão aqui</a></span>
        </div>
    </div>
</div>
<!-- Fim da Secção de Registo -->~

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ativarMostrarPalavraPasse = document.querySelector('#mostraPalavraPasse');
            const ativarMostrarConfirmarPalavraPasse = document.querySelector("#mostraConfirmarPalavraPasse");
            const palavra_passe = document.querySelector('#palavra_passe');
            const confirmar_palavra_passe = document.querySelector('#confirmar_palavra_passe');
            
            ativarMostrarPalavraPasse.addEventListener('click', function(e) {
                e.preventDefault();
                const tipo = palavra_passe.getAttribute('type') === 'password' ? 'text' : 'password';
                palavra_passe.setAttribute('type', tipo);
                
                // Alterna o ícone
                const icone = this.querySelector('i');
                icone.classList.toggle('fa-eye');
                icone.classList.toggle('fa-eye-slash');
                
                // Mantém o foco no campo de password
                palavra_passe.focus();
            });

            ativarMostrarConfirmarPalavraPasse.addEventListener('click', function(e) {
                e.preventDefault();
                const tipo = confirmar_palavra_passe.getAttribute('type') === 'password' ? 'text' : 'password';
                confirmar_palavra_passe.setAttribute('type', tipo);
                
                // Alterna o ícone
                const icone2 = this.querySelector('i');
                icone2.classList.toggle('fa-eye');
                icone2.classList.toggle('fa-eye-slash');
                
                // Mantém o foco no campo de password
                confirmar_palavra_passe.focus();
            });
        });
    </script>

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