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

    $preco = 0;
    $estado = 1;

    // IMPORTANTE: Separar quem paga de quem recebe o bilhete
    $id_utilizador_pagante = $_SESSION['id_utilizador']; // Quem paga (sempre quem está logado)
    $id_utilizador_bilhete = $_SESSION['id_utilizador']; // Por padrão, quem recebe é quem está logado

    // Lógica baseada no tipo de utilizador
    if ($tipo_utilizador == ADMINISTRADOR || $tipo_utilizador == FUNCIONARIO) {
        // Admin/Funcionário SEMPRE compra para outros utilizadores
        if (isset($_GET['id_utilizador']) && !empty($_GET['id_utilizador'])) {
            $id_utilizador_pagante = $_SESSION['id_utilizador']; // Admin/funcionário paga
            $id_utilizador_bilhete = $_GET['id_utilizador'];     // Cliente recebe o bilhete
        } else {
            // Se não foi especificado um utilizador, erro
            $_SESSION['mensagem_erro'] = "É necessário selecionar um utilizador para comprar o bilhete.";
            header('Location: viagens.php');
            exit();
        }
    } else if ($tipo_utilizador == CLIENTE) {
        // Cliente SEMPRE compra para si mesmo
        $id_utilizador_pagante = $_SESSION['id_utilizador']; // Cliente paga
        $id_utilizador_bilhete = $_SESSION['id_utilizador']; // Cliente recebe
        // Ignora qualquer id_utilizador que possa ter sido passado via GET
    }

    // Verifica se o id_viagem foi passado pela URL
    if (isset($_GET['id_viagem'])) {
        $id_viagem = $_GET['id_viagem'];
    
    if ($conn) {
        // 1. Obter o preço da viagem
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

        // 2. Obter o saldo do UTILIZADOR PAGANTE (quem vai pagar)
        $sql = "SELECT id_carteira FROM utilizador WHERE id = ?";
        $stmt = $conn->prepare($sql);

        if($stmt) {
            $stmt->bind_param("i", $id_utilizador_pagante); // Usa o ID do pagante
            $stmt->execute();
            $resultado = $stmt->get_result();

            if ($resultado && $resultado->num_rows > 0) {
                $linha = $resultado->fetch_assoc();
                $id_carteira = $linha['id_carteira'];

                // 2.2 Selecionar o valor da carteira correspondente ao utilizador PAGANTE
                $sql_carteira = "SELECT saldo FROM carteira WHERE id_carteira = ?";
                $stmt_carteira = $conn->prepare($sql_carteira);

                if ($stmt_carteira) {
                    $stmt_carteira->bind_param("i", $id_carteira);
                    if($stmt_carteira->execute()) {
                        $resultado_saldo = $stmt_carteira->get_result();

                        if ($resultado_saldo && $resultado_saldo->num_rows > 0) {
                            $linha_saldo = $resultado_saldo->fetch_assoc();
                            $saldo_atual = $linha_saldo['saldo'];
                        }
                    }
                    $stmt_carteira->close();
                }
            }
            $stmt->close();
        }

            // Verificar saldo do pagante
            if ($saldo_atual < $preco_viagem) {
                // Mensagem mais clara sobre quem não tem saldo
                if ($id_utilizador_pagante == $_SESSION['id_utilizador']) {
                    $_SESSION['mensagem_erro'] = "Você não tem saldo suficiente. Saldo atual: €" . 
                                                number_format($saldo_atual, 2) . 
                                                ", Preço da viagem: €" . number_format($preco_viagem, 2);
                } else {
                    $_SESSION['mensagem_erro'] = "Saldo insuficiente do pagante. Saldo atual: €" . 
                                                number_format($saldo_atual, 2) . 
                                                ", Preço da viagem: €" . number_format($preco_viagem, 2);
                }
                header('Location: viagens.php');
                exit();
            }

            // 3. Comprar o bilhete (retirar dinheiro da carteira do UTILIZADOR PAGANTE)
            $novo_saldo = $saldo_atual - $preco_viagem;
            $sql_atualizar_saldo = "UPDATE carteira SET saldo = ? WHERE id_carteira = ?";
            $stmt_atualizar_saldo = $conn->prepare($sql_atualizar_saldo);
            
            if ($stmt_atualizar_saldo) {
                $stmt_atualizar_saldo->bind_param("di", $novo_saldo, $id_carteira);
                $stmt_atualizar_saldo->execute();
                $stmt_atualizar_saldo->close();
            }

            // 4. Obter o saldo da Felixbus
            $sql_saldo_felixbus = "SELECT saldo FROM carteira WHERE id_carteira = 1";
            $resultado_saldo_felixbus = $conn->query($sql_saldo_felixbus);
            $saldo_felixbus = $resultado_saldo_felixbus->fetch_assoc();

            // 5. Adicionar o saldo do cliente à carteira da Felixbus
            $novo_saldo_felixbus = $saldo_felixbus['saldo'] + $preco_viagem;
            $sql_atualizar_felixbus = "UPDATE carteira SET saldo = ? WHERE id_carteira = 1";
            $stmt_atualizar_felixbus = $conn->prepare($sql_atualizar_felixbus);

            if($stmt_atualizar_felixbus) {
                $stmt_atualizar_felixbus->bind_param("d", $novo_saldo_felixbus);
                $stmt_atualizar_felixbus->execute();
                $stmt_atualizar_felixbus->close();
            }

            //6. SQL para inserir o bilhete na BD (bilhete vai para o utilizador correto)
            $sql_inserir = "INSERT INTO bilhete (id_viagem, id_utilizador, data_compra, estado) VALUES (?, ?, NOW(), ?)";
            $stmt_inserir = $conn->prepare($sql_inserir);
                
            if ($stmt_inserir) {
                $stmt_inserir->bind_param("iii", $id_viagem, $id_utilizador_bilhete, $estado);
                
                if ($stmt_inserir->execute()) {
                    // Mensagem mais específica
                    if ($id_utilizador_pagante == $id_utilizador_bilhete) {
                        $_SESSION['mensagem_sucesso'] = "Bilhete comprado com sucesso.";
                    } else {
                        $_SESSION['mensagem_sucesso'] = "Bilhete comprado com sucesso para o utilizador selecionado.";
                    }
                    
                    // Redireciona conforme o tipo de utilizador logado
                    if ($tipo_utilizador == CLIENTE) {
                        header("Location: pagina_inicial_cliente.php");
                    } else if ($tipo_utilizador == FUNCIONARIO) {
                        header("Location: pagina_inicial_func.php");
                    } else {
                        header("Location: pagina_inicial_admin.php");
                    }
                    exit();
                }
                $stmt_inserir->close();
            }
        }
    }
?>