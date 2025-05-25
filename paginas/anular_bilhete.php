<?php
session_start();
include("../basedados/basedados.h");

if (!isset($_SESSION['id_utilizador'])) {
    header("Location: entrar.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_bilhete'])) {
    $id_bilhete = $_POST['id_bilhete'];
    $id_utilizador = $_SESSION['id_utilizador'];

    // Verifica se o bilhete pertence ao usuário
    $sql_anular = "SELECT id FROM bilhete WHERE id = ? AND id_utilizador = ?";
    $stmt_anular = $conn->prepare($sql_anular);
    $stmt_anular->bind_param("ii", $id_bilhete, $id_utilizador);
    $stmt_anular->execute();
    
    if ($stmt_anular->get_result()->num_rows > 0) {
        // Atualiza o estado para 0 (inativo)
        $sql_atualiza = "UPDATE bilhete SET estado = 0 WHERE id = ?";
        $stmt_atualiza = $conn->prepare($sql_atualiza);
        $stmt_atualiza->bind_param("i", $id_bilhete);
        
        if ($stmt_atualiza->execute()) {
            $_SESSION['mensagem_sucesso'] = "Bilhete anulado com sucesso!";
            echo "success";
        }
    }
}
?>