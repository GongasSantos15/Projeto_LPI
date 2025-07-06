<?php
    // Inicia sessão
    session_start();

    // Include BD
    include '../basedados/basedados.h';

    // Verifica se o utilizador NÃO está logado
    if (!isset($_SESSION['id_utilizador'])) {
        header('Location: entrar.php');
        exit();
    }

    // Se chegou aqui, o utilizador ESTÁ logado
    $id_utilizador = $_SESSION['id_utilizador'];

    // Verifica se a requisição é do tipo POST
    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        // Verifica se os campos 'id_utilizador', 'nome' e 'nome-completo' foram enviados no POST
        if (isset($_POST['id_utilizador'], $_POST['nome'], $_POST['nome-proprio']) && $_POST['id_utilizador'] == $id_utilizador) {

            // Obtém os novos dados do utilizador do POST
            $novo_nome = trim($_POST['nome']);
            $novo_nome_proprio = trim($_POST['nome-proprio']); 
            $nova_palavra_passe = trim(md5($_POST['palavra_passe']));

            // Validação básica: verifica se os campos não estão vazios após trim
            if (empty($novo_nome) || empty($novo_nome_proprio)) {
                // Redireciona de volta com mensagem de erro se algum campo estiver vazio
                header('Location: consultar_dados.php?status=error&message=Os campos não podem estar vazios.');
                exit();
            }

            // Verifica se a conexão com a base de dados é válida
            if ($conn) {
                // Verifica se a password foi fornecida
                $password_fornecida = !empty(trim($_POST['palavra_passe']));
                
                // Apenas modifica a password se ela foi fornecida
                if ($password_fornecida) {
                    $nova_palavra_passe = md5(trim($_POST['palavra_passe']));
                    $sql = "UPDATE utilizador SET nome_utilizador = ?, nome_proprio = ?, palavra_passe = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sssi", $novo_nome, $novo_nome_proprio, $nova_palavra_passe, $id_utilizador);
                } else {
                    $sql = "UPDATE utilizador SET nome_utilizador = ?, nome_proprio = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssi", $novo_nome, $novo_nome_proprio, $id_utilizador);
                }

                if ($stmt && $stmt->execute()) {
                    $_SESSION['nome'] = $novo_nome;
                    $_SESSION['nome_proprio'] = $novo_nome_proprio;
                    
                    // Atualiza a password na sessão apenas se foi alterada
                    if ($password_fornecida) {
                        $_SESSION['palavra_passe'] = $nova_palavra_passe;
                    }
                    
                    $_SESSION['mensagem_sucesso'] = "Dados atualizados com sucesso.";
                    header('Location: consultar_dados.php');
                    exit();
                } else {
                    header('Location: consultar_dados.php?status=error&message=Erro ao atualizar dados.');
                    exit();
                }
                
                if ($stmt) $stmt->close();
                $conn->close();
            }
        }
    }
?>