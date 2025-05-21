<?php
    // Inicia a sessão
    session_start();

    // Include da base de dados
    include 'C:\xampp\htdocs\lpi\Projeto_LPI\basedados\basedados.h';

    // Variáveis para armazenar os dados do utilizador, inicializadas como vazias
    $nome_utilizador_atual = '';
    $nome_atual = '';
    $id_utilizador = null;

    // Verifica se o utilizador não efetuou o login, se não redireciona-o para a página de login
    if (!isset($_SESSION['id_utilizador'])) {
        header('Location: entrar.php');
        exit();
    }

    // Se chegou até aqui, o utilizador tem sessão iniciada
    $id_utilizador = $_SESSION['id_utilizador'];

    // Variável para armazenar mensagens de erro PHP (conexão, query, etc.)
    $mensagem_erro = '';

    // Verifica se a conexão com a base de dados é válida
    if ($conn) {
        // SQL para procurar o nome e nome_proprio do utilizador
        $sql = "SELECT nome, nome_proprio FROM user WHERE id = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt) { // Verifica se a preparação da query foi bem-sucedida e liga o pârametro à query ("i" indica que o parâmetro é um inteiro)
            $stmt->bind_param("i", $id_utilizador);

            // Executa a query
            $stmt->execute();

            // Obtém o resultado da query
            $result = $stmt->get_result();

            // Verifica se encontrou o utilizador, se sim obtém a linha de resultado como um array associativo e atribui o resultado às variáveis corretas
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();

                // htmlspecialchars serve para evitar problemas de segurança ao ir buscar os dados à BD, senão exibe uma mensagem de erro
                $nome_utilizador_atual = htmlspecialchars($row['nome']);
                $nome_atual = htmlspecialchars($row['nome_proprio']);
            } else {
                $mensagem_erro = "Os dados do utilizador não foram encontrados na base de dados.";
            }

            // Fecha o statement
            $stmt->close();
        } else {
            // Lida com o erro na preparação da query
            $mensagem_erro = "Ocorreu um erro ao preparar a consulta de dados.";
        }
    }
    // Fecha a conexão com a BD
    $conn->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>FelixBus - Consultar e Editar Dados</title>
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

    <style>
        /* Esconde o botão inicialmente */
        #botaoGuardar {
            display: none;
        }
        /* Estilo para o botão de edição */
        #botao-edicao {
            margin-bottom: 15px;
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
        <div class="p-5 rounded shadow" style="max-width: 700px; width: 100%; background-color: rgba(0, 0, 0, 0.6);">
            <h3 class="text-center text-white mb-4">Consultar e Editar Dados</h3>

            <?php
                if (!empty($mensagem_erro)) {
                    echo '<div class="alert alert-danger">' . htmlspecialchars($mensagem_erro) . '</div>';
                }
            ?>

            <button type="button" id="botao-edicao" class="btn btn-primary rounded-pill py-2 px-5 mb-4"><i class="fas fa-edit me-2"></i> Editar Dados</button>


            <form id="profileEditForm" action="editar_dados.php" method="POST">

                <input type="hidden" name="id_utilizador" value="<?php echo htmlspecialchars($id_utilizador); ?>">

                <div class="mb-3">
                    <label for="nomeProprio" class="form-label text-white">Nome Próprio:</label>
                    <div class="input-group">
                         <input name="nome-completo" id="nome-proprio" type="text" class="form-control text-dark" value="<?php echo $nome_atual; ?>" disabled required />
                    </div>
                </div>

                <div class="mb-3">
                    <label for="nome" class="form-label text-white">Nome:</label>
                    <div class="input-group">
                         <input name="nome" id="nome" type="text" class="form-control text-dark" value="<?php echo $nome_utilizador_atual; ?>" disabled required />
                    </div>
                </div>

                <div class="d-flex mt-5 justify-content-center">
                <input type="submit" id="botao-guardar" value="Guardar Alterações" class="btn btn-primary rounded-pill py-2 px-5">
            </div>

                <div id="messageArea" class="mt-3 text-center">
                    <?php
                        // Display messages if redirected back after update
                        //if (isset($_GET['status'])) {
                            //if ($_GET['status'] === 'success') {
                                //echo '<div class="alert alert-success">Dados atualizados com sucesso! Redirecionando...</div>';
                                // Redirect after 2 seconds to index.php as per original logic
                                //header("refresh:2; url = index.php");
                                //exit(); // Stop further execution after setting refresh header
                            //} else if ($_GET['status'] === 'error') {
                                // Use message from GET if available, otherwise a generic one
                                //$errorMessage = isset($_GET['message']) ? htmlspecialchars($_GET['message']) : 'Erro ao atualizar dados. Por favor, tente novamente.';
                                //echo '<div class="alert alert-danger">' . $errorMessage . '</div>';
                            //}
                        //}
                    ?>
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

    <script>
        // Seleciona os botões de edição e de input pelo ID, assim como o botão de guardar os dados e verifica se todos os elementos necessários foram encontrados
        // Adiciona um listener de clique no botão de edilção
        // Habilita os campos de input
        // Mostra o botão guardar
        // Oculta o botão "Editar Dados", depois de clicar nele
        // Foca o primeiro input
        document.addEventListener('DOMContentLoaded', function() {
            const botaoEdicao = document.getElementById('botao-edicao');
            const campoNome = document.getElementById('nome');
            const campoNomeProprio = document.getElementById('nome-proprio');
            const botaoGuardar = document.getElementById('botaoGuardar');

            if (botaoEdicao && campoNome && campoNomeProprio && botaoGuardar) {

                botaoEdicao.addEventListener('click', function() {
                    campoNome.disabled = false;
                    campoNomeProprio.disabled = false;

                    botaoGuardar.style.display = 'block';
                    botaoEdicao.style.display = 'none';

                    campoNome.focus();
                });
            }
        });
    </script>

</body>

</html>