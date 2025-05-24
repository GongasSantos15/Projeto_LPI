<?php
    // Inicia sessão
    session_start();

    // Inclui os detalhes da conexão com a base de dados
    include 'C:\xampp\htdocs\lpi\Projeto_LPI\basedados\basedados.h';

    // Verifica se o utilizador NÃO está logado
    if (!isset($_SESSION['id_utilizador'])) {
        header('Location: entrar.php');
        exit();
    }

    $id_utilizador = $_SESSION['id_utilizador'];

    // Verifica se a requisição é do tipo GET
    if ($_SERVER["REQUEST_METHOD"] == "GET") {

        if (isset($_GET['origem'], $_GET['destino'], $_GET['id_bilhete'])) {

            $id_bilhete = $_GET['id_bilhete'];

            // Obtém os novos dados do utilizador do POST
            $nova_origem = trim($_GET['origem']);
            $novo_destino = trim($_GET['destino']);

            // Validação básica: verifica se os campos não estão vazios após trim
            if (empty($nova_origem) || empty($novo_destino)) {
                // Redireciona de volta com mensagem de erro se algum campo estiver vazio
                header('Location: consultar_bilhetes.php?status=error&message=Os campos Origem e Destino não podem estar vazios.');
                exit();
            }

            // --- Atualizar dados do utilizador na base de dados ---

            // 1. SQL para ir buscar o id_rota
            $sql_rota = "SELECT id FROM rota WHERE origem = ? AND destino = ?";
            $stmt_rota = $conn->prepare($sql_rota);

            if ($stmt_rota) {
                $stmt_rota->bind_param("ss", $nova_origem, $novo_destino);

                if ($stmt_rota->execute()) {
                    $resultado_rota = $stmt_rota->get_result();
                    $linha_rota = $resultado_rota->fetch_assoc();
                    $id_rota = $linha_rota['id'];
                }
            }

            // 2. SQL para ir buscar o id_viagem
            $sql_viagem = "SELECT id FROM viagem WHERE id_rota = ?";
            $stmt_viagem = $conn->prepare($sql_viagem);

            if ($stmt_viagem) {
                $stmt_viagem->bind_param("i", $id_rota);

                if ($stmt_viagem->execute()) {
                    $resultado_viagem = $stmt_viagem->get_result();
                    $linha_viagem = $resultado_viagem->fetch_assoc();
                    $id_viagem = $linha_viagem['id'];
                }
            }

            // 3. SQL para atualizar o ID da Viagem
            $sql_bilhete = "UPDATE bilhete SET id_viagem = ? WHERE id_utilizador = ? AND id = ?";
            $stmt_bilhete = $conn->prepare($sql_bilhete);

            if ($stmt_bilhete) {
                $stmt_bilhete->bind_param("iii", $id_viagem, $id_utilizador, $id_bilhete);

                if ($stmt_bilhete->execute()) {
                    $_SESSION['mensagem_sucesso'] = "Dados de Viagem atualizados com sucesso.";
                    header('Location: consultar_bilhetes.php');
                    exit();
                }
            }
        }
    }
?>