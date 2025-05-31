<?php
session_start();
include("../basedados/basedados.h");

// Verificar se é admin
if (!isset($_SESSION['tipo_utilizador']) || $_SESSION['tipo_utilizador'] != 1) {
    $_SESSION['mensagem_erro'] = "Acesso não autorizado!";
    header('Location: consultar_alertas.php');
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_alerta'], $_POST['descricao'])) {
    
    $id_alerta = $_POST['id_alerta'];
    $nova_descricao = trim($_POST['descricao']);

    // Validação
    if (empty($nova_descricao)) {
        $_SESSION['mensagem_erro'] = "A Descrição é obrigatória!";
        header("Location: consultar_alertas.php");
        exit();
    }

    // Atualizar no banco de dados
    $sql = "UPDATE alerta SET descricao = ? WHERE id_alerta = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("si", $nova_descricao, $id_alerta);
        
        if ($stmt->execute()) {
            $_SESSION['mensagem_sucesso'] = "Alerta atualizado com sucesso!";
        } else {
            $_SESSION['mensagem_erro'] = "Erro ao atualizar alerta: " . $conn->$connect_error;
        }
        $stmt->close();
    } else {
        $_SESSION['mensagem_erro'] = "Erro ao preparar consulta: " . $conn->$connect_error;
    }
    
    $conn->close();
    header("Location: consultar_alertas.php");
    exit();
} else {
    $_SESSION['mensagem_erro'] = "Requisição inválida!";
    header("Location: consultar_alertas.php");
    exit();
}
?>