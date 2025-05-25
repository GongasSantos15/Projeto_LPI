<?php

    // Include base de dados
    include 'C:\xampp\htdocs\lpi\Projeto_LPI\basedados\basedados.h';
    
    // Include às constantes de utilizadores 
    include 'constUtilizadores.php';

    // Iniciar Sessão
    session_start();

    $mensagem_erro = '';

    $cliente_apagado = CLIENTE_APAGADO;
    $cliente_nao_validado = CLIENTE_NAO_VALIDO;

    // Se o método for POST
    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        // Verificar se os campos não estão vazios (redundante com 'required' no HTML, mas seguro)
        if (isset($_POST["nome_utilizador"]) && isset($_POST["palavra_passe"])) {

            // Dados do formulário (obtidos de forma segura)
            $nome_utilizador = $_POST["nome_utilizador"];
            $palavra_passe_encriptada = md5($_POST['palavra_passe']);

            // Prepara a query SQL
            $sql = "SELECT id, nome_utilizador, tipo_utilizador FROM utilizador WHERE nome_utilizador = ? AND palavra_passe = ?";
            $stmt = $conn->prepare($sql);

            // Verifica se a preparação da query falhou, se falhar mostra uma mensagem de erro
            // Senão liga os parâmetros (dados do formulário) às variáveis corretas ("ssi" indica que existem 2 strings e 1 inteiro)
            if ($stmt === false) {
                $mensagem_erro = 'Erro interno na base de dados. Tente novamente mais tarde.';
            } else {
                $stmt->bind_param("ss", $nome_utilizador, $palavra_passe_encriptada);

                // Executa a query
                $stmt->execute();

                // Obtém o resultado da query
                $resultado = $stmt->get_result();

                // Verifica se encontrou exatamente 1 utilizador, obtém os dados do mesmo, guarda nas variáveis de sessão e redireciona para a página inicial se tudo correr bem
                if ($resultado && mysqli_num_rows($resultado) == 1) {
                    // Obtém os dados do utilizador
                    $linha = mysqli_fetch_assoc($resultado);

                    if ($linha['tipo_utilizador'] == $cliente_apagado) {
                        $mensagem_erro = "Cliente não existe ou foi apagado!";
                    } else if ($linha['tipo_utilizador'] == $cliente_nao_validado) {
                        $mensagem_erro = "Cliente não está validado!";
                    } else {
                        // Autenticação bem-sucedida! Guarda os dados na sessão.
                        $_SESSION['id_utilizador'] = $linha['id'];
                        $_SESSION['nome_utilizador'] = $linha['nome_utilizador'];
                        $_SESSION['tipo_utilizador'] = $linha['tipo_utilizador'];

                        $stmt->close();

                        header("Location: index.php");
                        exit();
                    }
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
        /* Remove o outline e ajusta a borda do botão */
        #togglePassword:focus {
            outline: none;
            box-shadow: none;
            border-color: #ced4da;
        }

        .input-group button {
            border-left: none;
        }
        .input-group button:hover {
            background-color: #e9ecef;
        }

        /* Melhora a aparência do input group */
        .input-group > .form-control:not(:first-child),
        .input-group > .form-select:not(:first-child) {
            border-left: 0;
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }

        .input-group > .btn {
            border-top-right-radius: 0.25rem;
            border-bottom-right-radius: 0.25rem;
            transition: none;
        }
    </style>
    
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

        <!-- Div para mensagens de erro -->
        <?php if (!empty($mensagem_erro)): ?>
            <div class="alert alert-danger alert-dismissible fade show" id="mensagem_erro" role="alert">
                <?php echo htmlspecialchars($mensagem_erro); ?>
            </div>
            <script>
                // Esconde a mensagem de sucesso após 2 segundos
                setTimeout(function() {
                        document.getElementById('mensagem_erro').style.display = 'none';
                    }, 2000);
            </script>
        <?php endif; ?>

        <form action="entrar.php" method="POST">
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
            <div class="d-flex mt-5 justify-content-center">
                <input type="submit" value="Entrar" class="btn btn-primary rounded-pill py-2 px-5">
            </div>
        </form>
        <div class="text-center mt-5">
            <span>Não tem conta? <a href="registar.php" class="text-info"> Registe-se aqui</a></span>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ativarMostrarPalavraPasse = document.querySelector('#mostraPalavraPasse');
        const palavra_passe = document.querySelector('#palavra_passe');
        
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
    });
</script>

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