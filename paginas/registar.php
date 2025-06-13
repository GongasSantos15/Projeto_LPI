<?php
    include 'C:\xampp\htdocs\lpi\Projeto_LPI\basedados\basedados.h';
    include 'const_utilizadores.php';

    session_start();

    $mensagem_erro = '';
    $mensagem_sucesso = '';
    $saldo = 0;

    if($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Verifique os nomes dos campos exatamente como estão no formulário HTML
        if(!empty($_POST['nome_utilizador']) && !empty($_POST['nome_proprio']) && !empty($_POST['palavra_passe']) && !empty($_POST['confirmar_palavra_passe'])) {
            
            // Use o mesmo nome que está no formulário (com underscore)
            if($_POST['palavra_passe'] === $_POST['confirmar_palavra_passe']) {
                $nome_utilizador = mysqli_real_escape_string($conn, $_POST['nome_utilizador']);
                $nome_proprio = mysqli_real_escape_string($conn, $_POST['nome_proprio']);
                $palavra_passe_encriptada = md5($_POST['palavra_passe']);
                $tipo_utilizador = CLIENTE_NAO_VALIDO;

                // 1. Verificar qual é o maior id_carteira existente
                $sql_max_carteira = "SELECT MAX(id_carteira) AS max_id FROM utilizador";
                $resultado_max_carteira = $conn->query($sql_max_carteira);

                if ($resultado_max_carteira) {
                    $linha_max_carteira = $resultado_max_carteira->fetch_assoc();
                    $novo_id_carteira = $linha_max_carteira['max_id'] + 1;
                } else {
                    $mensagem_erro = "Erro ao obter o ID da carteira: " . $connect_error->error();
                }

                // 2. Query para INSERIR os dados na tabela utilizador
                $sql_inserir = "INSERT INTO utilizador (nome_utilizador, nome_proprio, palavra_passe, tipo_utilizador, id_carteira) VALUES (?, ?, ?, ?, ?)";
                $stmt_inserir = $conn->prepare($sql_inserir);
                
                if (!$stmt_inserir) {
                    $mensagem_erro = "Erro ao preparar a consulta.";
                } else {
                    $stmt_inserir->bind_param("sssii", $nome_utilizador, $nome_proprio, $palavra_passe_encriptada, $tipo_utilizador, $novo_id_carteira);

                    if ($stmt_inserir->execute()){
                        $mensagem_sucesso = "Utilizador registado com sucesso";
                    } else {
                        $mensagem_erro = "Erro ao registar utilizador: " . $connect_error->error();
                    }
                }
            } else {
                $mensagem_erro = "As palavras passe não coincidem.";
            }

            // 3. Inserir o novo ID da Carteira na tabela carteira com o saldo = 0;
            $sql_id_carteira = "INSERT INTO carteira (id_carteira, saldo) VALUES (?, ?)";
            $stmt_id_carteira = $conn->prepare($sql_id_carteira);

            if (!$stmt_id_carteira) {
                $mensagem_erro = "Erro ao preparar a consulta para inserir os dados da carteira";
            } else {
                $stmt_id_carteira->bind_param("id", $novo_id_carteira, $saldo);

                if($stmt_id_carteira->execute()) {
                    $mensagem_sucesso = "Carteira do utilizador criada com sucesso!";
                    header("Location: entrar.php");
                    exit();
                } else {
                    $mensagem_erro = "Erro ao criar a carteira do utilizador: " . $connect_error->error();
                }
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

    <!-- Imagens, Fontes e CSS -->
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
    <!-- Roda de Carregamento -->
    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>

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
    <!-- Fim da Secção de Registo -->

    <!-- Bibliotecas JS -->
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

    <!-- Código JS -->
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
</body>

</html>