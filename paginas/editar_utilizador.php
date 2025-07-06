<?php
    // Inicia sessão
    session_start();

    // Include BD
    include "../basedados/basedados.h";

    // Verifica se o utilizador está autenticado
    if (!isset($_SESSION['id_utilizador']) || $_SESSION['tipo_utilizador'] == 3) {
        header('Location: entrar.php');
        exit();
    }

    // Verifica se a requisição é do tipo GET
    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        if (isset($_POST['nome_proprio'], $_POST['nome_utilizador'], $_POST['tipo_utilizador'], $_POST['id_carteira'])) {

            $id_utilizador = $_POST['id'];

            // Obtém os novos dados do utilizador do POST
            $novo_nome_proprio = trim($_POST['nome_proprio']);
            $novo_nome_utilizador = trim($_POST['nome_utilizador']);
            $novo_tipo_utilizador = trim($_POST['tipo_utilizador']);
            $novo_id_carteira = trim($_POST['id_carteira']);
            $nova_palavra_passe = trim(md5($_POST['palavra_passe']));

            // Validação básica: verifica se os campos não estão vazios após trim
            if (empty($novo_nome_proprio) || empty($novo_nome_utilizador) || empty($novo_tipo_utilizador) || empty($novo_id_carteira)) {
                header('Location: consultar_utilizadores.php?status=error&message=Os campos não podem estar vazios.');
                exit();
            }

            // Verifica se a password foi fornecida
            $atualizar_password = !empty(trim($_POST['palavra_passe']));
            
            //Atualiza a palavra-passe apenas se foi fornecida
            if ($atualizar_password) {
                $nova_palavra_passe = md5(trim($_POST['palavra_passe']));
                $sql = "UPDATE utilizador SET nome_proprio = ?, nome_utilizador = ?, tipo_utilizador = ?, id_carteira = ?, palavra_passe = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssiisi", $novo_nome_proprio, $novo_nome_utilizador, $novo_tipo_utilizador, $novo_id_carteira, $nova_palavra_passe, $id_utilizador);
            } else {
                $sql = "UPDATE utilizador SET nome_proprio = ?, nome_utilizador = ?, tipo_utilizador = ?, id_carteira = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssiii", $novo_nome_proprio, $novo_nome_utilizador, $novo_tipo_utilizador, $novo_id_carteira, $id_utilizador);
            }

            if ($stmt->execute()) {
                $_SESSION['mensagem_sucesso'] = "Utilizador atualizado com sucesso.";
                header('Location: consultar_utilizadores.php');
                exit();
            }
        }
    }
?>