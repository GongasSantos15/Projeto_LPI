<?php
    // Garante que a sessão é iniciada antes de qualquer output HTML
    session_start();

    // Inclui os detalhes da conexão com a base de dados
    // Certifique-se que este caminho está correto e basedados.h define a variável $conn
    include 'C:\xampp\htdocs\lpi\Projeto_LPI\basedados\basedados.h';

    // Variável para armazenar o nome do utilizador, inicializada como vazia
    $current_user_name = '';
    $user_id = null; // Inicializa o user_id

    // Verifica se o utilizador NÃO está logado
    if (!isset($_SESSION['user_id'])) {
        // Redireciona para a página de login se não estiver logado
        header('Location: entrar.php');
        exit(); // Para a execução do script após o redirecionamento
    }

    // Se chegou aqui, o utilizador ESTÁ logado
    $user_id = $_SESSION['user_id']; // Obtém o ID do utilizador da sessão

    // --- Buscar dados do utilizador usando o user_id ---

    // Verifica se a conexão com a base de dados é válida
    if ($conn) {
        // SQL para buscar o nome do utilizador
        $sql = "SELECT nome FROM user WHERE id = ?"; // Assumindo tabela 'user' e coluna 'nome'
        $stmt = $conn->prepare($sql);

        if ($stmt) { // Verifica se a preparação da query foi bem-sucedida
            // Liga o parâmetro (id do utilizador) à query
            // "i" indica que o parâmetro é um inteiro
            $stmt->bind_param("i", $user_id);

            // Executa a query
            $stmt->execute();

            // Obtém o resultado da query
            $result = $stmt->get_result();

            // Verifica se encontrou o utilizador
            if ($result && $result->num_rows > 0) {
                // Obtém a linha de resultado como um array associativo
                $row = $result->fetch_assoc();
                // Atribui o nome à variável, sanitizando a saída para evitar problemas de segurança
                $current_user_name = htmlspecialchars($row['nome']);
            } else {
                 // Opcional: Lidar com o caso em que o user ID está na sessão, mas não na DB
                 // Pode ser um erro (utilizador apagado?), pode logar a situação
                 error_log("User ID {$user_id} not found in database but in session.");
                 // Dependendo da lógica de segurança, pode ser necessário redirecionar para login
                 // header('Location: entrar.php?error=user_data_missing'); exit();
            }

            // Fecha o statement
            $stmt->close();
        } else {
            // Lidar com erro na preparação da query
            error_log("Database prepare error: " . $conn->error);
            // Pode exibir uma mensagem de erro amigável ao utilizador
        }

        // Fecha a conexão com a base de dados no final
        $conn->close();
    } else {
         // Lidar com falha na conexão (se não for tratada em basedados.h)
         error_log("Database connection failed.");
         // Pode exibir uma mensagem de erro amigável ao utilizador
    }
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
        /* Custom style to make the edit icon look clickable */
        .edit-icon {
            cursor: pointer;
            margin-left: 5px; /* Add some spacing */
            vertical-align: middle; /* Align vertically with input */
        }
        .edit-icon:hover {
            color: #007bff; /* Change color on hover */
        }
        /* Hide the save button initially */
        #saveButton {
            display: none;
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
    <div class="p-5 rounded shadow" style="max-width: 700px; width: 100%; background-color: rgba(0, 0, 0, 0.6);"> <h3 class="text-center text-white mb-4">Consultar e Editar Dados</h3>
        <form id="profileEditForm" action="editar_dados.php" method="POST">

            <input type="hidden" name="user_id" value="[SERVER_USER_ID]">

            <div class="mb-3">
                <label for="nome" class="form-label text-white">Nome:</label>
                <div class="input-group">
                    <input name="nome" id="nome" type="text" class="form-control text-dark" value="<?php echo $current_user_name; ?>" disabled required />
                    <span class="input-group-text bg-white edit-icon" id="editIcon">
                         <i class="fas fa-edit text-primary"></i>
                    </span>
                </div>
            </div>

            <button type="submit" id="saveButton" class="btn btn-primary w-100 py-3">Guardar Alterações</button>

            <div id="messageArea" class="mt-3 text-center">
                <?php
                    // Display messages if redirected back after update
                    if (isset($_GET['status'])) {
                        if ($_GET['status'] === 'success') {
                            echo '<div class="alert alert-success">Dados atualizados com sucesso!</div>';
                            header("refresh:2; url = index.php");
                        } else if ($_GET['status'] === 'error') {
                            echo '<div class="alert alert-danger">Erro ao atualizar dados. Por favor, tente novamente.</div>';
                        }
                    }
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
</body>

</html>