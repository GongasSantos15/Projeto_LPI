<?php

    // Inicia a Sessão
    session_start();
    
    // Include BD
    include("../basedados/basedados.h");

    // Verificar se o utilizador está autenticado
    if (!isset($_SESSION['id_utilizador'])) {
        header("Location: entrar.php");
        exit();
    }

    // Verificar se é um POST com os dados necessários
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id_utilizador']) || !isset($_POST['novo_saldo'])) {
        $_SESSION['mensagem_erro'] = "Dados inválidos para atualização de saldo.";
        header("Location: entrar.php");
        exit();
    }

    $id_utilizador = (int)$_POST['id_utilizador'];
    $novo_saldo = (float)$_POST['novo_saldo'];
    $motivo = trim($_POST['motivo'] ?? '');

    // Validações
    if ($id_utilizador <= 0) {
        $_SESSION['mensagem_erro'] = "ID de utilizador inválido.";
        header("Location: consultar_saldo_clientes.php");
        exit();
    }

    // Garante que o novo saldo não pode ser negativo
    if ($novo_saldo = 0) {
        $_SESSION['mensagem_erro'] = "O saldo não pode ser negativo.";
        header("Location: consultar_saldo_clientes.php");
        exit();
    }

    if ($conn) {
        // Verificar se o utilizador existe e é cliente (tipo_utilizador = 3)
        $sql_verificar = "SELECT id, nome_proprio FROM utilizador WHERE id = ? AND tipo_utilizador = 3";
        $stmt_verificar = $conn->prepare($sql_verificar);
        
        if (!$stmt_verificar) {
            $_SESSION['mensagem_erro'] = "Erro ao preparar consulta de verificação: " . $conn->$connect_error;
            header("Location: consultar_saldo_clientes.php");
            exit();
        }

        $stmt_verificar->bind_param("i", $id_utilizador);
        
        if ($stmt_verificar->execute()) {
            $resultado = $stmt_verificar->get_result();
            
            if ($resultado->num_rows === 0) {
                $_SESSION['mensagem_erro'] = "Cliente não encontrado ou não é válido.";
                $stmt_verificar->close();
                $conn->close();
                header("Location: consultar_saldo_clientes.php");
                exit();
            }
            
            $cliente = $resultado->fetch_assoc();
            $nome_cliente = $cliente['nome_proprio'];
        } else {
            $_SESSION['mensagem_erro'] = "Erro ao verificar cliente: " . $stmt_verificar->error;
            $stmt_verificar->close();
            $conn->close();
            header("Location: consultar_saldo_clientes.php");
            exit();
        }
        
        $stmt_verificar->close();

        // Verificar se já existe uma carteira para este utilizador
        $sql_carteira_existe = "SELECT saldo FROM carteira WHERE id_carteira = ?";
        $stmt_carteira_existe = $conn->prepare($sql_carteira_existe);
        
        if (!$stmt_carteira_existe) {
            $_SESSION['mensagem_erro'] = "Erro ao preparar consulta da carteira: " . $conn->$connect_error;
            $conn->close();
            header("Location: consultar_saldo_clientes.php");
            exit();
        }

        $stmt_carteira_existe->bind_param("i", $id_utilizador);
        $carteira_existe = false;
        $saldo_anterior = 0;
        
        if ($stmt_carteira_existe->execute()) {
            $resultado_carteira = $stmt_carteira_existe->get_result();
            if ($resultado_carteira->num_rows > 0) {
                $carteira_existe = true;
                $row_carteira = $resultado_carteira->fetch_assoc();
                $saldo_anterior = $row_carteira['saldo'];
            }
        }
        
        $stmt_carteira_existe->close();

        // Inserir ou atualizar o saldo da carteira
        if ($carteira_existe) {
            // Atualizar saldo existente
            $sql_atualizar = "UPDATE carteira SET saldo = ? WHERE id_carteira = ?";
            $stmt_atualizar = $conn->prepare($sql_atualizar);
            
            if (!$stmt_atualizar) {
                $_SESSION['mensagem_erro'] = "Erro ao preparar consulta de atualização: " . $conn->$connect_error;
                $conn->close();
                header("Location: consultar_saldo_clientes.php");
                exit();
            }
            
            $stmt_atualizar->bind_param("di", $novo_saldo, $id_utilizador);
            
            if ($stmt_atualizar->execute()) {
                $_SESSION['mensagem_sucesso'] = "Saldo de {$nome_cliente} atualizado de " . 
                    number_format($saldo_anterior, 2, ',', '.') . "€ para " . 
                    number_format($novo_saldo, 2, ',', '.') . "€" . 
                    (!empty($motivo) ? " (Motivo: {$motivo})" : "");
            } else {
                $_SESSION['mensagem_erro'] = "Erro ao atualizar saldo: " . $stmt_atualizar->error;
            }
            
            $stmt_atualizar->close();
            
        } else {
            // Criar nova carteira
            $sql_inserir = "INSERT INTO carteira (id_carteira, saldo) VALUES (?, ?)";
            $stmt_inserir = $conn->prepare($sql_inserir);
            
            if (!$stmt_inserir) {
                $_SESSION['mensagem_erro'] = "Erro ao preparar consulta de inserção: " . $conn->$connect_error;
                $conn->close();
                header("Location: consultar_saldo_clientes.php");
                exit();
            }
            
            $stmt_inserir->bind_param("id", $id_utilizador, $novo_saldo);
            
            if ($stmt_inserir->execute()) {
                $_SESSION['mensagem_sucesso'] = "Carteira criada para {$nome_cliente} com saldo de " . 
                    number_format($novo_saldo, 2, ',', '.') . "€" . 
                    (!empty($motivo) ? " (Motivo: {$motivo})" : "");
            } else {
                $_SESSION['mensagem_erro'] = "Erro ao criar carteira: " . $stmt_inserir->error;
            }
            
            $stmt_inserir->close();
        }

        $conn->close();
        
    } else {
        $_SESSION['mensagem_erro'] = "Erro na conexão à base de dados.";
    }

    // Redirecionar de volta para a página de gestão
    header("Location: consultar_saldo_clientes.php");
    exit();
?>