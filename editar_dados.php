<?php
// Garante que a sessão é iniciada antes de qualquer output HTML
session_start();

// Inclui os detalhes da conexão com a base de dados
// Certifique-se que este caminho está correto e basedados.h define a variável $conn
include 'C:\xampp\htdocs\lpi\Projeto_LPI\basedados\basedados.h';

// Verifica se o utilizador NÃO está logado
if (!isset($_SESSION['user_id'])) {
    // Redireciona para a página de login se não estiver logado
    header('Location: entrar.php');
    exit(); // Para a execução do script após o redirecionamento
}

// Se chegou aqui, o utilizador ESTÁ logado
$user_id = $_SESSION['user_id']; // Obtém o ID do utilizador da sessão

// Verifica se a requisição é do tipo POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Verifica se o campo 'nome' foi enviado no POST
    if (isset($_POST['nome'])) {
        // Obtém o novo nome do utilizador do POST
        $novo_nome = $_POST['nome'];

        // Validação básica (pode adicionar mais validações conforme necessário)
        if (empty($novo_nome)) {
            // Redireciona de volta com mensagem de erro se o nome estiver vazio
            header('Location: consultar_dados.php?status=error&message=O nome não pode estar vazio.');
            exit();
        }

        // --- Atualizar dados do utilizador na base de dados ---

        // Verifica se a conexão com a base de dados é válida
        if ($conn) {
            // SQL para atualizar o nome do utilizador
            // Usamos prepared statements para prevenir SQL injection
            $sql = "UPDATE user SET nome = ? WHERE id = ?"; // Assumindo tabela 'user' e coluna 'nome'
            $stmt = $conn->prepare($sql);

            if ($stmt) { // Verifica se a preparação da query foi bem-sucedida
                // Liga os parâmetros à query
                // "s" indica que o parâmetro é uma string (nome)
                // "i" indica que o parâmetro é um inteiro (user_id)
                $stmt->bind_param("si", $novo_nome, $user_id);

                // Executa a query
                if ($stmt->execute()) {
                    // Atualização bem-sucedida na base de dados

                    // *** Adicionado: Atualiza também a variável de sessão com o novo nome ***
                    $_SESSION['nome'] = $novo_nome;

                    // Redireciona de volta para a página de consulta com status de sucesso
                    header('Location: consultar_dados.php?status=success');
                    exit();
                } else {
                    // Erro na execução da query
                    error_log("Database execute error: " . $stmt->error);
                    // Redireciona de volta com status de erro
                    header('Location: consultar_dados.php?status=error&message=Erro ao executar a atualização.');
                    exit();
                }

                // Fecha o statement
                $stmt->close();
            } else {
                // Lidar com erro na preparação da query
                error_log("Database prepare error: " . $conn->error);
                // Redireciona de volta com status de erro
                header('Location: consultar_dados.php?status=error&message=Erro na preparação da query.');
                exit();
            }

            // Fecha a conexão com a base de dados no final
            $conn->close();
        } else {
            // Lidar com falha na conexão (se não for tratada em basedados.h)
            error_log("Database connection failed.");
            // Redireciona de volta com status de erro
            header('Location: consultar_dados.php?status=error&message=Falha na conexão com a base de dados.');
            exit();
        }
    } else {
        // Campo 'nome' não foi enviado
        header('Location: consultar_dados.php?status=error&message=Dados de nome ausentes.');
        exit();
    }
} else {
    // Se não for uma requisição POST, redireciona para a página de consulta
    header('refresh:2, Location: consultar_dados.php');
    exit();
}
?>
