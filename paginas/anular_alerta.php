<?php
session_start();
include("../basedados/basedados.h");

// Verificar se é admin
if (!isset($_SESSION['tipo_utilizador']) || $_SESSION['tipo_utilizador'] != 1) {
    $_SESSION['mensagem_erro'] = "Acesso não autorizado!";
    header('Location: consultar_alertas.php');
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_alerta'])) {
    $id_alerta = $_POST['id_alerta'];
    
    // Verifica se a rota existe e está ativa
    $sql_anular = "SELECT id_alerta FROM alerta WHERE id_alerta = ? AND estado = 1";
    $stmt_anular = $conn->prepare($sql_anular);
    $stmt_anular->bind_param("i", $id_alerta);
    $stmt_anular->execute();
   
    if ($stmt_anular->get_result()->num_rows > 0) {
        // Atualiza o estado para 0 (inativo)
        $sql_atualiza = "UPDATE alerta SET estado = 0 WHERE id_alerta = ?";
        $stmt_atualiza = $conn->prepare($sql_atualiza);
        $stmt_atualiza->bind_param("i", $id_alerta);
       
        if ($stmt_atualiza->execute()) {
            $_SESSION['mensagem_sucesso'] = "Alerta anulado com sucesso!";
            echo "success";
        }
        $stmt_atualiza->close();
    }
    $stmt_anular->close();

    $conn->close();
}
?>