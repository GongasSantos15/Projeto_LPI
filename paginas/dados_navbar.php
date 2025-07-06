<?php

    include("../basedados/basedados.h"); 

    // Verifica o estado de login
    $temLogin = isset($_SESSION['id_utilizador']) && !empty($_SESSION['id_utilizador']);
    $nome_utilizador = $temLogin ? ($_SESSION['nome_utilizador'] ?? 'Utilizador') : '';
    $valor_carteira = 0;
    $numero_bilhetes = 0;
    $id_utilizador = null;
    $tem_login = isset($_SESSION['id_utilizador']) && !empty($_SESSION['id_utilizador']);

    if ($temLogin) {

        // Determina a página inicial correta baseada no tipo de utilizador
        $pagina_inicial = 'index.php';
        if ($tem_login && isset($_SESSION['tipo_utilizador'])) {
            switch ($_SESSION['tipo_utilizador']) {
                case 1: $pagina_inicial = 'pagina_inicial_admin.php'; break;
                case 2: $pagina_inicial = 'pagina_inicial_func.php'; break;
                case 3: $pagina_inicial = 'pagina_inicial_cliente.php'; break;
            }
        }

        $id_utilizador = $_SESSION['id_utilizador'];

        if ($conn) {
            // Consulta para obter dados da carteira
            $sql = "SELECT id_carteira FROM utilizador WHERE id = ?";
            $stmt = $conn->prepare($sql);

            if ($stmt) {
                $stmt->bind_param("i", $id_utilizador);
                $stmt->execute();
                $resultado = $stmt->get_result();  

                if ($resultado && $resultado->num_rows > 0) {
                    $linha = $resultado->fetch_assoc();
                    $id_carteira = $linha['id_carteira'];

                    $sql_carteira = "SELECT saldo FROM carteira WHERE id_carteira = ?";
                    $stmt_carteira = $conn->prepare($sql_carteira);

                    if ($stmt_carteira) {
                        $stmt_carteira->bind_param("i", $id_carteira);
                        $stmt_carteira->execute();
                        $resultado_carteira = $stmt_carteira->get_result();

                        if ($resultado_carteira && $resultado_carteira->num_rows > 0) {
                            $linha_carteira = $resultado_carteira->fetch_assoc();
                            $valor_carteira = number_format($linha_carteira['saldo'], 2, ',', '.');
                            $_SESSION['valor_carteira'] = $valor_carteira;
                        }

                        $stmt_carteira->close();
                    }
                }

                $stmt->close();
            }

            // Consulta para contar os bilhetes do utilizador (se necessário)
            $sql_bilhetes = "SELECT COUNT(*) as total_bilhetes FROM bilhete WHERE id_utilizador = ? AND estado = 1";
            $stmt_bilhetes = $conn->prepare($sql_bilhetes);

            if ($stmt_bilhetes) {
                $stmt_bilhetes->bind_param("i", $id_utilizador);
                $stmt_bilhetes->execute();
                $resultado_bilhetes = $stmt_bilhetes->get_result();

                if ($resultado_bilhetes && $resultado_bilhetes->num_rows > 0) {
                    $linha_bilhetes = $resultado_bilhetes->fetch_assoc();
                    $numero_bilhetes = $linha_bilhetes['total_bilhetes'];
                }

                $stmt_bilhetes->close();
            }
        }
    }
?>