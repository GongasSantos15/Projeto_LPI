<?php

    // Inicia a Sessão
    session_start();

    // Include BD
    include("../basedados/basedados.h");

    if (!isset($_SESSION['id_utilizador'])) {
        header("Location: entrar.php");
        exit();
    }

    // Processa os dados do formulário para editar o bilhete
    if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['id_bilhete'], $_GET['origem'], $_GET['destino'])) {
        $id_bilhete = $_GET['id_bilhete'];
        $nova_origem = trim($_GET['origem']);
        $novo_destino = trim($_GET['destino']);

        // Validação
        if (empty($nova_origem) || empty($novo_destino)) {
            $_SESSION['mensagem_erro'] = "Os campos Origem e Destino não podem estar vazios.";
            header("Location: consultar_bilhetes.php");
            exit();
        }

        // 1. Buscar id_rota
        $sql_rota = "SELECT id FROM rota WHERE origem = ? AND destino = ?";
        $stmt_rota = $conn->prepare($sql_rota);
        $stmt_rota->bind_param("ss", $nova_origem, $novo_destino);
        
        if ($stmt_rota->execute()) {
            $resultado_rota = $stmt_rota->get_result();
            if ($resultado_rota->num_rows > 0) {
                $linha_rota = $resultado_rota->fetch_assoc();
                $id_rota = $linha_rota['id'];
                
                // 2. Buscar id_viagem para esta rota
                $sql_viagem = "SELECT id FROM viagem WHERE id_rota = ? LIMIT 1";
                $stmt_viagem = $conn->prepare($sql_viagem);
                $stmt_viagem->bind_param("i", $id_rota);
                
                if ($stmt_viagem->execute()) {
                    $resultado_viagem = $stmt_viagem->get_result();
                    if ($resultado_viagem->num_rows > 0) {
                        $linha_viagem = $resultado_viagem->fetch_assoc();
                        $id_viagem = $linha_viagem['id'];
                        
                        // 3. Atualizar bilhete
                        $sql_bilhete = "UPDATE bilhete SET id_viagem = ? WHERE id = ?";
                        $stmt_bilhete = $conn->prepare($sql_bilhete);
                        $stmt_bilhete->bind_param("ii", $id_viagem, $id_bilhete);
                        
                        if ($stmt_bilhete->execute()) {
                            $_SESSION['mensagem_sucesso'] = "Bilhete atualizado com sucesso!";
                        } else {
                            $_SESSION['mensagem_erro'] = "Erro ao atualizar bilhete: " . $conn->$connect_error;
                        }
                    } else {
                        $_SESSION['mensagem_erro'] = "Nenhuma viagem encontrada para esta rota.";
                    }
                } else {
                    $_SESSION['mensagem_erro'] = "Erro ao buscar viagem: " . $conn->$connect_error;
                }
            } else {
                $_SESSION['mensagem_erro'] = "Rota não encontrada para os parâmetros especificados.";
            }
        } else {
            $_SESSION['mensagem_erro'] = "Erro ao buscar rota: " . $conn->$connect_error;
        }
        
        $conn->close();
        header("Location: consultar_bilhetes.php");
        exit();
    } else {
        header("Location: consultar_bilhetes.php");
        exit();
    }
?>