<?php
    session_start();

    // Include Conexão BD
    include("../basedados/basedados.h");
    include("constUtilizadores.php");

    $tipo_utilizador = $_SESSION['tipo_utilizador'];

    // Verifica se o utilizador tem sessão iniciada, senão tiver redireciona para a página de login
    if (!isset($_SESSION['id_utilizador'])) {
        header("Location: entrar.php");
        exit();
    }

    $preco = 0;
    $estado = 1;

    $id_utilizador = $_SESSION['id_utilizador'];
    
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
            }

            // 2. Obter o saldo do utilizador
            // 2.1 Selecionar o utilizador com a carteira
            $sql = "SELECT id_carteira FROM utilizador WHERE id = ?";
            $stmt = $conn->prepare($sql);

            if($stmt) {
                $stmt->bind_param("i", $id_utilizador);
                $stmt->execute();
                $resultado = $stmt->get_result();

                if ($resultado && $resultado->num_rows > 0) {
                    $linha = $resultado->fetch_assoc();
                    $id_carteira = $linha['id_carteira'];

                    // 2.2 Selecionar o valor da carteira correspondente ao utilizador
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
            }

            // 2. Verificar se é possível comprar o bilhete (Saldo suficiente)
            if ($saldo_atual < $preco_viagem) {
                $_SESSION['mensagem_erro'] = "Saldo insuficiente. Saldo atual: €" . number_format($saldo_atual, 2) . 
                                                ", Preço da viagem: €" . number_format($preco_viagem, 2);
                header('Location: viagens.php');
                exit();
            }

            // 3. Comprar o bilhete (retirar dinheiro da carteira do utilizador)
            $novo_saldo = $saldo_atual - $preco_viagem;
            $sql_atualizar_saldo = "UPDATE carteira SET saldo = ? WHERE id_carteira = ?";
            $stmt_atualizar_saldo = $conn->prepare($sql_atualizar_saldo);
            
            if ($stmt_atualizar_saldo) {
                $stmt_atualizar_saldo->bind_param("di", $novo_saldo, $id_carteira);
                $stmt_atualizar_saldo->execute();
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
            }

            //6. SQL para inserir o bilhete na BD
            $sql_inserir = "INSERT INTO bilhete (id_viagem, id_utilizador, data_compra, estado) VALUES (?, ?, NOW(), ?)";
                $stmt_inserir = $conn->prepare($sql_inserir);
                
            if ($stmt_inserir) {
                $stmt_inserir->bind_param("iii", $id_viagem, $id_utilizador, $estado);
                
                if ($stmt_inserir->execute()) {
                    $_SESSION['mensagem_sucesso'] = "Bilhete comprado com sucesso. A redirecionar...";

                    if ($tipo_utilizador == CLIENTE) {
                        header("Location: pagina_inicial_cliente.php");
                        exit();
                    } else if ($tipo_utilizador == FUNCIONARIO) {
                        header("Location: pagina_inicial_func.php");
                        exit();
                    } else {
                        header("Location: pagina_inicial_admin.php");
                        exit();
                    }

                } else {
                    $_SESSION['mensagem_erro'] = "Erro ao comprar o bilhete: " . $stmt_inserir->error;
                    header('Location: viagens.php');
                    exit();
                }

            } else {
                $_SESSION['mensagem_erro'] = "Erro ao preparar a query de inserção: " . $conn->$connect_error;
                header('Location: viagens.php');
                exit();
            }
        } else {
            $_SESSION['mensagem_erro'] = "Erro de conexão à base de dados.";
            header('Location: viagens.php');
            exit();
        }
    } else {
        $_SESSION['mensagem_erro'] = "ID da viagem não especificado.";
        header('Location: viagens.php');
        exit();
    }
?>