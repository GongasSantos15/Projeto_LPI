<?php
session_start();
include("../basedados/basedados.h");

// Verificar se é admin
if (!isset($_SESSION['tipo_utilizador']) || $_SESSION['tipo_utilizador'] != 1) {
    $_SESSION['mensagem_erro'] = "Acesso não autorizado!";
    header('Location: consultar_rotas.php');
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'])) {
    $id_rota = $_POST['id'];
    $id_utilizador = $_SESSION['id_utilizador'];
    
    // Verifica se a rota existe e está ativa
    $sql_anular = "SELECT id FROM rota WHERE id = ? AND estado = 1";
    $stmt_anular = $conn->prepare($sql_anular);
    $stmt_anular->bind_param("i", $id_rota);
    $stmt_anular->execute();
   
    if ($stmt_anular->get_result()->num_rows > 0) {
        // Atualiza o estado para 0 (inativo)
        $sql_atualiza = "UPDATE rota SET estado = 0 WHERE id = ?";
        $stmt_atualiza = $conn->prepare($sql_atualiza);
        $stmt_atualiza->bind_param("i", $id_rota);
       
        if ($stmt_atualiza->execute()) {
            $_SESSION['mensagem_sucesso'] = "Rota anulada com sucesso!";
            echo "success";
        }
        $stmt_atualiza->close();
    }
    $stmt_anular->close();

    $conn->close();
}
?>