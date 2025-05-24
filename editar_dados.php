<?php
    // Inicia sessão
    session_start();

    // Inclui os detalhes da conexão com a base de dados
    include 'C:\xampp\htdocs\lpi\Projeto_LPI\basedados\basedados.h';

    // Verifica se o utilizador NÃO está logado
    if (!isset($_SESSION['id_utilizador'])) {
        // Redireciona para a página de login se não estiver logado
        header('Location: entrar.php');
        exit(); // Para a execução do script após o redirecionamento
    }

    // Se chegou aqui, o utilizador ESTÁ logado
    $id_utilizador = $_SESSION['id_utilizador']; // Obtém o ID do utilizador da sessão

    // Verifica se a requisição é do tipo POST
    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        // Verifica se os campos 'id_utilizador', 'nome' e 'nome-completo' foram enviados no POST
        // Also check if the posted id_utilizador matches the session id_utilizador for security
        if (isset($_POST['id_utilizador'], $_POST['nome'], $_POST['nome-proprio']) && $_POST['id_utilizador'] == $id_utilizador) {

            // Obtém os novos dados do utilizador do POST
            $novo_nome = trim($_POST['nome']); // Trim whitespace
            $novo_nome_proprio = trim($_POST['nome-proprio']); // Trim whitespace

            // Validação básica: verifica se os campos não estão vazios após trim
            if (empty($novo_nome) || empty($novo_nome_proprio)) {
                // Redireciona de volta com mensagem de erro se algum campo estiver vazio
                header('Location: consultar_dados.php?status=error&message=Os campos Nome e Nome Próprio não podem estar vazios.');
                exit();
            }

            // --- Atualizar dados do utilizador na base de dados ---

            // Verifica se a conexão com a base de dados é válida
            if ($conn) {
                // SQL para atualizar o nome e nome_proprio do utilizador
                // Usamos prepared statements para prevenir SQL injection
                $sql = "UPDATE utilizador SET nome_utilizador = ?, nome_proprio = ? WHERE id = ?"; // Assumindo tabela 'user' e colunas 'nome', 'nome_proprio'
                $stmt = $conn->prepare($sql);

                if ($stmt) { // Verifica se a preparação da query foi bem-sucedida
                    // Liga os parâmetros à query
                    // "s" indica que o parâmetro é uma string (nome)
                    // "s" indica que o parâmetro é uma string (nome_proprio)
                    // "i" indica que o parâmetro é um inteiro (id_utilizador)
                    $stmt->bind_param("ssi", $novo_nome, $novo_nome_proprio, $id_utilizador);

                    // Executa a query
                    if ($stmt->execute()) {

                        $_SESSION['nome'] = $novo_nome;
                        $_SESSION['nome_proprio'] = $novo_nome_proprio;
                        $_SESSION['mensagem_sucesso'] = "Dados atualizados com sucesso. A redirecionar...";

                        // Redireciona de volta para a página de consulta
                        header('Location: consultar_dados.php');
                        exit();
                    }

                    // Fecha o statement
                    $stmt->close();
                }

                // Fecha a conexão com a base de dados no final
                $conn->close();
            }
        }
    }
?>