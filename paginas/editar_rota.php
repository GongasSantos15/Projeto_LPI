<?php
    session_start();
    include("../basedados/basedados.h");

    // Verificar se é admin
    if (!isset($_SESSION['tipo_utilizador']) || $_SESSION['tipo_utilizador'] != 1) {
        $_SESSION['mensagem_erro'] = "Acesso não autorizado!";
        header('Location: consultar_rotas.php');
        exit();
    }

    // Processa os dados do formulário para editar a rota
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'], $_POST['origem'], $_POST['destino'])) {
        
        $id = (int)$_POST['id'];
        $origem = trim($_POST['origem']);
        $destino = trim($_POST['destino']);

        // Validação
        if (empty($origem) || empty($destino)) {
            $_SESSION['mensagem_erro'] = "Origem e Destino são obrigatórios!";
            header("Location: consultar_rotas.php");
            exit();
        }

        // Atualizar a tabela rota
        $sql = "UPDATE rota SET origem = ?, destino = ? WHERE id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ssi", $origem, $destino, $id);
            
            if ($stmt->execute()) {
                $_SESSION['mensagem_sucesso'] = "Rota atualizada com sucesso!";
            } else {
                $_SESSION['mensagem_erro'] = "Erro ao atualizar rota: " . $conn->$connect_error;
            }
            $stmt->close();
        } else {
            $_SESSION['mensagem_erro'] = "Erro ao preparar consulta: " . $conn->$connect_error;
        }
        
        $conn->close();
        header("Location: consultar_rotas.php");
        exit();
    } else {
        $_SESSION['mensagem_erro'] = "Requisição inválida!";
        header("Location: consultar_rotas.php");
        exit();
    }
?>