<?php
    // Inicia sessão
    session_start();

    // Inclui os detalhes da conexão com a base de dados
    include 'C:\xampp\htdocs\lpi\Projeto_LPI\basedados\basedados.h';

    // Verifica se o utilizador NÃO está logado
    if (!isset($_SESSION['id_utilizador'])) {
        header('Location: entrar.php');
        exit();
    }

    // Verifica se a requisição é do tipo GET
    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        if (isset($_POST['nome_proprio'], $_POST['nome_utilizador'], $_POST['tipo_utilizador'], $_POST['id_carteira'], $_POST['palavra_passe'])) {

            $id_utilizador = $_POST['id'];

            // Obtém os novos dados do utilizador do POST
            $novo_nome_proprio = trim($_POST['nome_proprio']);
            $novo_nome_utilizador = trim($_POST['nome_utilizador']);
            $novo_tipo_utilizador = trim($_POST['tipo_utilizador']);
            $novo_id_carteira = trim($_POST['id_carteira']);
            $nova_palavra_passe = trim(md5($_POST['palavra_passe']));

            // Validação básica: verifica se os campos não estão vazios após trim
            if (empty($novo_nome_proprio) || empty($novo_nome_utilizador) || empty($novo_tipo_utilizador) || empty($novo_id_carteira) || empty($nova_palavra_passe)) {
                // Redireciona de volta com mensagem de erro se algum campo estiver vazio
                header('Location: consultar_utilizadores.php?status=error&message=Os campos não podem estar vazios.');
                exit();
            }

            // --- Atualizar dados do utilizador na base de dados ---

            // 3. SQL para atualizar o ID da Viagem
            $sql_bilhete = "UPDATE utilizador SET nome_proprio = ?, nome_utilizador = ?, tipo_utilizador = ?, id_carteira = ?, palavra_passe = ? WHERE id = ?";
            $stmt_bilhete = $conn->prepare($sql_bilhete);

            if ($stmt_bilhete) {
                $stmt_bilhete->bind_param("ssiisi", $novo_nome_proprio, $novo_nome_utilizador, $novo_tipo_utilizador, $novo_id_carteira, $nova_palavra_passe, $id_utilizador);

                if ($stmt_bilhete->execute()) {
                    $_SESSION['mensagem_sucesso'] = "Utilizador atualizado com sucesso.";
                    header('Location: consultar_utilizadores.php');
                    exit();
                }
            }
        }
    }
?>