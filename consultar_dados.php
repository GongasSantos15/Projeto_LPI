<?php
// Garante que a sessão é iniciada antes de qualquer output HTML
session_start();

// Inclui os detalhes da conexão com a base de dados
// Certifique-se que este caminho está correto e basedados.h define a variável $conn
// Ex: C:\xampp\htdocs\lpi\Projeto_LPI\basedados\basedados.h
include 'C:\xampp\htdocs\lpi\Projeto_LPI\basedados\basedados.h';

// Variáveis para armazenar os dados do utilizador, inicializadas como vazias
$current_user_name = '';
$current_user_firstname = ''; // Variável para 'nome_proprio'
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

// Variável para armazenar mensagens de erro PHP (conexão, query, etc.)
$error_message = '';

// Verifica se a conexão com a base de dados é válida
if ($conn) {
    // SQL para buscar o nome e nome_proprio do utilizador
    // Assumindo tabela 'user' e colunas 'nome', 'nome_proprio'
    $sql = "SELECT nome, nome_proprio FROM user WHERE id = ?";
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
            // Atribui os nomes às variáveis, sanitizando a saída para evitar problemas de segurança
            $current_user_name = htmlspecialchars($row['nome']);
            $current_user_firstname = htmlspecialchars($row['nome_proprio']);
        } else {
            // Opcional: Lidar com o caso em que o user ID está na sessão, mas não na DB
            error_log("User ID {$user_id} not found in database but in session.");
            // Se o utilizador não for encontrado na DB, pode ser um erro grave.
            // Dependendo da lógica de segurança, pode ser necessário limpar a sessão e redirecionar para login.
            // session_unset();
            // session_destroy();
            // header('Location: entrar.php?error=user_data_missing'); exit();
             $error_message = "Os dados do utilizador não foram encontrados na base de dados.";
        }

        // Fecha o statement
        $stmt->close();
    } else {
        // Lidar com erro na preparação da query
        error_log("Database prepare error: " . $conn->error);
        $error_message = "Ocorreu um erro ao preparar a consulta de dados.";
    }

    // A conexão será fechada no final do script se $conn for válida
    // Não fechar aqui se precisar dela para outras coisas mais abaixo
    // $conn->close();
} else {
    // Lidar com falha na conexão (se não for tratada em basedados.h)
    error_log("Database connection failed.");
    $error_message = "Não foi possível conectar à base de dados.";
}

// Certifica-se que a conexão é fechada se tiver sido aberta e ainda não o foi
if (isset($conn) && $conn) {
    $conn->close();
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
        /* Hide the save button initially */
        #saveButton {
            display: none;
        }
        /* Optional: Style for the edit button */
        #startEditButton {
            margin-bottom: 15px; /* Space below the button */
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
                if (!empty($error_message)) {
                    echo '<div class="alert alert-danger">' . htmlspecialchars($error_message) . '</div>';
                }
            ?>

            <button type="button" id="startEditButton" class="btn btn-primary rounded-pill py-2 px-5 mb-4"><i class="fas fa-edit me-2"></i> Editar Dados</button>


            <form id="profileEditForm" action="editar_dados.php" method="POST">

                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>">

                <div class="mb-3">
                    <label for="nomeProprio" class="form-label text-white">Nome Próprio:</label>
                    <div class="input-group">
                         <input name="nome-completo" id="nomeProprio" type="text" class="form-control text-dark" value="<?php echo $current_user_firstname; ?>" disabled required />
                    </div>
                </div>

                <div class="mb-3">
                    <label for="nome" class="form-label text-white">Nome:</label>
                    <div class="input-group">
                         <input name="nome" id="nome" type="text" class="form-control text-dark" value="<?php echo $current_user_name; ?>" disabled required />
                    </div>
                </div>

                <div class="d-flex mt-5 justify-content-center">
                <input type="submit" id="saveButton" value="Guardar Alterações" class="btn btn-primary rounded-pill py-2 px-5">
            </div>

                <div id="messageArea" class="mt-3 text-center">
                    <?php
                        // Display messages if redirected back after update
                        if (isset($_GET['status'])) {
                            if ($_GET['status'] === 'success') {
                                echo '<div class="alert alert-success">Dados atualizados com sucesso! Redirecionando...</div>';
                                // Redirect after 2 seconds to index.php as per original logic
                                header("refresh:2; url = index.php");
                                exit(); // Stop further execution after setting refresh header
                            } else if ($_GET['status'] === 'error') {
                                // Use message from GET if available, otherwise a generic one
                                $errorMessage = isset($_GET['message']) ? htmlspecialchars($_GET['message']) : 'Erro ao atualizar dados. Por favor, tente novamente.';
                                echo '<div class="alert alert-danger">' . $errorMessage . '</div>';
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

    <script>
        // Espera que o DOM esteja completamente carregado antes de executar o script
        document.addEventListener('DOMContentLoaded', function() {
            // Seleciona o novo botão de edição pelo seu ID
            const startEditButton = document.getElementById('startEditButton');
            // Seleciona os campos de input que queremos tornar editáveis pelos seus IDs
            const nameInput = document.getElementById('nome');
            const firstNameInput = document.getElementById('nomeProprio');
            // Seleciona o botão de guardar pelo seu ID
            const saveButton = document.getElementById('saveButton');

            // Verifica se todos os elementos necessários foram encontrados
            if (startEditButton && nameInput && firstNameInput && saveButton) {

                // Adiciona um listener de clique ao botão de edição
                startEditButton.addEventListener('click', function() {
                    // Habilita AMBOS os campos de input
                    nameInput.disabled = false;
                    firstNameInput.disabled = false;

                    // Mostra o botão de guardar
                    saveButton.style.display = 'block';

                    // Oculta o botão "Editar Dados" depois de clicar nele
                    startEditButton.style.display = 'none';

                    // Opcional: Foca no primeiro campo de input para facilitar a edição
                    nameInput.focus();
                });
            } else {
                // Logar um erro se os elementos não forem encontrados (útil para depuração)
                console.error('Um ou mais elementos JS necessários não foram encontrados (startEditButton, nome, nomeProprio, saveButton).');
            }
        });
    </script>

</body>

</html>