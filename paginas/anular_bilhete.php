<?php
// Inicia a Sessão
session_start();
// Include BD
include("../basedados/basedados.h");

// Se não existir sessão redirecionar para a página de login
if (!isset($_SESSION['id_utilizador'])) {
    header("Location: entrar.php");
    exit();
}

// Processa os dados do bilhete (Processa POST)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_bilhete'])) {
    $id_bilhete = $_POST['id_bilhete'];
    $id_utilizador = $_POST['id_utilizador'];

    // Start a transaction
    $conn->begin_transaction();

    try {
        // Verifica se o bilhete pertence ao utilizador
        $sql_anular = "SELECT b.id, b.id_viagem, u.id_carteira FROM bilhete b JOIN utilizador u ON b.id_utilizador = u.id WHERE b.id = ? AND b.estado = 1";
        $stmt_anular = $conn->prepare($sql_anular);
        $stmt_anular->bind_param("i", $id_bilhete);
        $stmt_anular->execute();
        $resultado = $stmt_anular->get_result();

        if ($resultado->num_rows > 0) {
            $linha = $resultado->fetch_assoc();
            $id_viagem = $linha['id_viagem'];
            $id_carteira = $linha['id_carteira'];

            // Obtém o preço da viagem
            $sql_preco = "SELECT preco FROM viagem WHERE id = ?";
            $stmt_preco = $conn->prepare($sql_preco);
            $stmt_preco->bind_param("i", $id_viagem);
            $stmt_preco->execute();
            $resultado_preco = $stmt_preco->get_result();
            $linha_preco = $resultado_preco->fetch_assoc();
            $valor_bilhete = $linha_preco['preco'];

            // Atualiza o estado para 0 (inativo)
            $sql_atualiza = "UPDATE bilhete SET estado = 0 WHERE id = ?";
            $stmt_atualiza = $conn->prepare($sql_atualiza);
            $stmt_atualiza->bind_param("i", $id_bilhete);

            if ($stmt_atualiza->execute()) {
                // Processa o reembolso do valor do bilhete
                $sql_reembolso = "UPDATE carteira SET saldo = saldo + ? WHERE id_carteira = ?";
                $stmt_reembolso = $conn->prepare($sql_reembolso);
                $stmt_reembolso->bind_param("di", $valor_bilhete, $id_carteira);

                if ($stmt_reembolso->execute()) {
                    // Commit the transaction if both operations succeed
                    $conn->commit();
                    $_SESSION['mensagem_sucesso'] = "Bilhete anulado com sucesso e reembolso processado!";
                    echo "success";
                } else {
                    throw new Exception("Erro ao processar o reembolso.");
                }
                $stmt_reembolso->close();
            } else {
                throw new Exception("Erro ao anular o bilhete.");
            }
            $stmt_atualiza->close();
        }
        $stmt_anular->close();
    } catch (Exception $e) {
        // Rollback the transaction if any operation fails
        $conn->rollback();
        $_SESSION['mensagem_erro'] = "Erro ao anular o bilhete: " . $e->getMessage();
        echo "error";
    }
    $conn->close();
}
?>
