<?php
    // Inicia a Sessão
    session_start();

    // Include BD e constantes de utilizadores (tipo)
    include("../basedados/basedados.h");
    include("const_utilizadores.php");


    if (!isset($_SESSION['id_utilizador']) || $_SESSION['tipo_utilizador'] != ADMINISTRADOR) {
        header('Location: entrar.php');
        exit();
    }

    // Processa os dados do utilizador (Processa POST)
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'])) {
        $id_utilizador = $_POST['id'];
        
        // Não permitir que um admin se anule a si mesmo
        if ($id_utilizador == $_SESSION['id_utilizador']) {
            exit();
        }

        // Atualiza diretamente para 6 (CLIENTE_APAGADO)
        $sql_atualiza = "UPDATE utilizador SET tipo_utilizador = 6 WHERE id = ?";
        $stmt_atualiza = $conn->prepare($sql_atualiza);
        $stmt_atualiza->bind_param("i", $id_utilizador);
        
        if ($stmt_atualiza->execute()) {
            $_SESSION['mensagem_sucesso'] = "Utilizador anulado com sucesso!";
            echo "success";
        } else {
            error_log("Erro ao anular utilizador ID " . $id_utilizador . ": " . $stmt_atualiza->error);
            echo "error";
        }
        $stmt_atualiza->close();
    } else {
        echo "invalid_request";
    }
?>