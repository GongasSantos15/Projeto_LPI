<?php
    // Include BD
    include 'C:\xampp\htdocs\lpi\Projeto_LPI\basedados\basedados.h';

    // Inicia a sessão
    session_start();

    // Verifica o estado de login
    $temLogin = isset($_SESSION['id_utilizador']) && !empty($_SESSION['id_utilizador']);
    $nome_utilizador = $temLogin ? ($_SESSION['nome_utilizador'] ?? 'Utilizador') : '';
    $valor_carteira = 0;
    $id_utilizador = null;

    // Redireciona para login se não tiver sessão
    if (!$temLogin) {
        header('Location: entrar.php');
        exit();
    } else {
        $id_utilizador = $_SESSION['id_utilizador'];

        if ($conn) {
            // 1º passo: obter o id_carteira a partir da tabela utilizador
            $sql = "SELECT id_carteira FROM utilizador WHERE id = ?";
            $stmt = $conn->prepare($sql);

            if ($stmt) {
                $stmt->bind_param("i", $id_utilizador);
                $stmt->execute();
                $resultado = $stmt->get_result();

                if ($resultado && $resultado->num_rows > 0) {
                    $linha = $resultado->fetch_assoc();
                    $id_carteira = $linha['id_carteira'];

                    // 2º passo: obter o saldo da carteira
                    $sql_carteira = "SELECT saldo FROM carteira WHERE id_carteira = ?";
                    $stmt_carteira = $conn->prepare($sql_carteira);

                    if ($stmt_carteira) {
                        $stmt_carteira->bind_param("i", $id_carteira);
                        $stmt_carteira->execute();
                        $resultado_carteira = $stmt_carteira->get_result();

                        if ($resultado_carteira && $resultado_carteira->num_rows > 0) {
                            $linha_carteira = $resultado_carteira->fetch_assoc();
                            $valor_carteira = number_format($linha_carteira['saldo'], 2, ',', '.');
                        }
                        $stmt_carteira->close();
                    }
                }

                $stmt->close();
            }

            $conn->close();
        }
    }
?>
