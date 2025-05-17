<?php
// Garante que a sessão é iniciada antes de qualquer output HTML
session_start();

// Inclui os detalhes da conexão com a base de dados
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

    // Verifica se os campos 'user_id', 'nome' e 'nome-completo' foram enviados no POST
    // Also check if the posted user_id matches the session user_id for security
    if (isset($_POST['user_id'], $_POST['nome'], $_POST['nome-completo']) && $_POST['user_id'] == $user_id) {

        // Obtém os novos dados do utilizador do POST
        $novo_nome = trim($_POST['nome']); // Trim whitespace
        $novo_nome_completo = trim($_POST['nome-completo']); // Trim whitespace

        // Validação básica: verifica se os campos não estão vazios após trim
        if (empty($novo_nome) || empty($novo_nome_completo)) {
            // Redireciona de volta com mensagem de erro se algum campo estiver vazio
            header('Location: consultar_dados.php?status=error&message=Os campos Nome e Nome Próprio não podem estar vazios.');
            exit();
        }

        // --- Atualizar dados do utilizador na base de dados ---

        // Verifica se a conexão com a base de dados é válida
        if ($conn) {
            // SQL para atualizar o nome e nome_proprio do utilizador
            // Usamos prepared statements para prevenir SQL injection
            $sql = "UPDATE user SET nome = ?, nome_proprio = ? WHERE id = ?"; // Assumindo tabela 'user' e colunas 'nome', 'nome_proprio'
            $stmt = $conn->prepare($sql);

            if ($stmt) { // Verifica se a preparação da query foi bem-sucedida
                // Liga os parâmetros à query
                // "s" indica que o parâmetro é uma string (nome)
                // "s" indica que o parâmetro é uma string (nome_proprio)
                // "i" indica que o parâmetro é um inteiro (user_id)
                $stmt->bind_param("ssi", $novo_nome, $novo_nome_completo, $user_id);

                // Executa a query
                if ($stmt->execute()) {

                    $_SESSION['nome'] = $novo_nome;

                    // Atualização bem-sucedida na base de dados

                    // Opcional: Atualiza as variáveis de sessão se você as usa para exibir
                    // o nome em outras partes do site sem ir à DB a cada vez.
                    // Isso evita a necessidade de recarregar a página de consulta para ver os novos dados
                    // se você tivesse a intenção de exibir os dados da sessão lá.
                    // No entanto, como a página consultar_dados busca da DB, isso não é estritamente necessário
                    // para a lógica atual, mas é boa prática se a sessão for a fonte primária noutros locais.
                    // $_SESSION['user_name'] = $novo_nome; // Se usar um nome na sessão
                    // $_SESSION['user_firstname'] = $novo_nome_completo; // Se usar um nome_proprio na sessão

                    // Redireciona de volta para a página de consulta com status de sucesso
                    header('Location: consultar_dados.php?status=success');
                    exit();
                } else {
                    // Erro na execução da query
                    error_log("Database execute error: " . $stmt->error);
                    // Redireciona de volta com status de erro
                    header('Location: consultar_dados.php?status=error&message=Erro ao executar a atualização na base de dados.');
                    exit();
                }

                // Fecha o statement
                $stmt->close();
            } else {
                // Lidar com erro na preparação da query
                error_log("Database prepare error: " . $conn->error);
                // Redireciona de volta com status de erro
                header('Location: consultar_dados.php?status=error&message=Erro na preparação da query de atualização.');
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
        // Dados POST ausentes ou user_id da sessão não corresponde ao POST
        error_log("Invalid POST data or user_id mismatch during profile update.");
        header('Location: consultar_dados.php?status=error&message=Dados inválidos ou sessão expirada.');
        exit();
    }
} else {
    // Se não for uma requisição POST válida
    error_log("Attempted to access editar_dados.php with non-POST method.");
    header('Location: consultar_dados.php?status=error&message=Método de requisição inválido.');
    exit();
}
?>