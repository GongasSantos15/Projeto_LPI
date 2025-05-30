<?php
session_start();
include("../basedados/basedados.h");
include("constUtilizadores.php");

$tipo_utilizador = $_SESSION['tipo_utilizador'];

// Verifica se o utilizador tem sessão iniciada
if (!isset($_SESSION['id_utilizador'])) {
    header("Location: entrar.php");
    exit();
}

$preco_viagem = 0;
$estado = 1;

$id_utilizador_pagante = $_SESSION['id_utilizador']; // Quem paga (por padrão, quem está logado)
$id_utilizador_bilhete = $_SESSION['id_utilizador']; // Quem recebe o bilhete (por padrão, quem está logado)
$usaCarteiraFelixbus = false; // Por padrão, não usa a carteira da empresa

// Lógica baseada no tipo de utilizador
if ($tipo_utilizador == ADMINISTRADOR || $tipo_utilizador == FUNCIONARIO) {
    if (isset($_GET['id_utilizador']) && !empty($_GET['id_utilizador'])) {
        $id_utilizador_bilhete = $_GET['id_utilizador']; // Cliente recebe
        $usaCarteiraFelixbus = true; // Admin/função paga com carteira da empresa
    } else {
        $_SESSION['mensagem_erro'] = "É necessário selecionar um utilizador para comprar o bilhete.";
        header('Location: viagens.php');
        exit();
    }
}

if (isset($_GET['id_viagem'])) {
    $id_viagem = $_GET['id_viagem'];

    if ($conn) {
        // 1. Obter preço da viagem
        $sql_preco = "SELECT preco FROM viagem WHERE id = ?";
        $stmt_preco = $conn->prepare($sql_preco);
        if ($stmt_preco) {
            $stmt_preco->bind_param("i", $id_viagem);
            if ($stmt_preco->execute()) {
                $resultado_preco = $stmt_preco->get_result();
                if ($resultado_preco && $resultado_preco->num_rows > 0) {
                    $linha_preco = $resultado_preco->fetch_assoc();
                    $preco_viagem = $linha_preco['preco'];
                }
            }
            $stmt_preco->close();
        }

        // 2. Determinar a carteira correta
        if ($usaCarteiraFelixbus) {
            $id_carteira = 1; // Carteira da empresa
        } else {
            $sql = "SELECT id_carteira FROM utilizador WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if($stmt) {
                $stmt->bind_param("i", $id_utilizador_pagante);
                $stmt->execute();
                $resultado = $stmt->get_result();
                if ($resultado && $resultado->num_rows > 0) {
                    $linha = $resultado->fetch_assoc();
                    $id_carteira = $linha['id_carteira'];
                }
                $stmt->close();
            }
        }

        // 3. Obter saldo atual da carteira
        $sql_carteira = "SELECT saldo FROM carteira WHERE id_carteira = ?";
        $stmt_carteira = $conn->prepare($sql_carteira);
        if ($stmt_carteira) {
            $stmt_carteira->bind_param("i", $id_carteira);
            if ($stmt_carteira->execute()) {
                $resultado_saldo = $stmt_carteira->get_result();
                if ($resultado_saldo && $resultado_saldo->num_rows > 0) {
                    $linha_saldo = $resultado_saldo->fetch_assoc();
                    $saldo_atual = $linha_saldo['saldo'];
                }
            }
            $stmt_carteira->close();
        }

        // 4. Verificar saldo suficiente
        if ($saldo_atual < $preco_viagem) {
            if ($usaCarteiraFelixbus) {
                $_SESSION['mensagem_erro'] = "Saldo insuficiente na carteira da empresa.";
            } else {
                $_SESSION['mensagem_erro'] = "Saldo insuficiente. Saldo atual: €" . 
                                             number_format($saldo_atual, 2) . 
                                             ", Preço da viagem: €" . number_format($preco_viagem, 2);
            }
            header('Location: viagens.php');
            exit();
        }

        // 5. Subtrair o saldo da carteira pagante (apenas se não for a carteira da empresa)
        if (!$usaCarteiraFelixbus) {
            $novo_saldo = $saldo_atual - $preco_viagem;
            $sql_atualizar_saldo = "UPDATE carteira SET saldo = ? WHERE id_carteira = ?";
            $stmt_atualizar_saldo = $conn->prepare($sql_atualizar_saldo);
            if ($stmt_atualizar_saldo) {
                $stmt_atualizar_saldo->bind_param("di", $novo_saldo, $id_carteira);
                $stmt_atualizar_saldo->execute();
                $stmt_atualizar_saldo->close();
            }

            // 6. Adicionar à carteira da Felixbus
            $sql_saldo_felixbus = "SELECT saldo FROM carteira WHERE id_carteira = 1";
            $resultado_saldo_felixbus = $conn->query($sql_saldo_felixbus);
            $saldo_felixbus = $resultado_saldo_felixbus->fetch_assoc();

            $novo_saldo_felixbus = $saldo_felixbus['saldo'] + $preco_viagem;
            $sql_atualizar_felixbus = "UPDATE carteira SET saldo = ? WHERE id_carteira = 1";
            $stmt_atualizar_felixbus = $conn->prepare($sql_atualizar_felixbus);
            if ($stmt_atualizar_felixbus) {
                $stmt_atualizar_felixbus->bind_param("d", $novo_saldo_felixbus);
                $stmt_atualizar_felixbus->execute();
                $stmt_atualizar_felixbus->close();
            }
        }

        // 7. Inserir o bilhete na BD
        $sql_inserir = "INSERT INTO bilhete (id_viagem, id_utilizador, data_compra, estado) VALUES (?, ?, NOW(), ?)";
        $stmt_inserir = $conn->prepare($sql_inserir);
        if ($stmt_inserir) {
            $stmt_inserir->bind_param("iii", $id_viagem, $id_utilizador_bilhete, $estado);
            if ($stmt_inserir->execute()) {
                if ($id_utilizador_pagante == $id_utilizador_bilhete) {
                    $_SESSION['mensagem_sucesso'] = "Bilhete comprado com sucesso.";
                } else {
                    $_SESSION['mensagem_sucesso'] = "Bilhete comprado com sucesso para o utilizador selecionado.";
                }

                // Redirecionamento por tipo de utilizador
                switch ($tipo_utilizador) {
                    case CLIENTE:
                        header("Location: pagina_inicial_cliente.php");
                        break;
                    case FUNCIONARIO:
                        header("Location: pagina_inicial_func.php");
                        break;
                    case ADMINISTRADOR:
                        header("Location: pagina_inicial_admin.php");
                        break;
                }
                exit();
            }
            $stmt_inserir->close();
        }
    }
}
?>
