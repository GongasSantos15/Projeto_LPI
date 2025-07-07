<?php
    session_start();

    // Definir o tipo de conteúdo da resposta para HTML e charset UTF-8
    header('Content-Type: text/html; charset=UTF-8');

    // Incluir o arquivo de conexão com o banco de dados
    include '../basedados/basedados.h';

    // Inicializa a mensagem de erro
    $mensagem_erro = "";
    $mensagem_sucesso = "";

    // Verifica se o utilizador está com sessão iniciada e é admin (tipo_utilizador = 1)
    if (!isset($_SESSION['id_utilizador']) ||
        !isset($_SESSION['tipo_utilizador']) ||
        $_SESSION['tipo_utilizador'] !== 1) {
        $_SESSION['mensagem_erro'] = "Acesso não autorizado para editar alertas.";
        header('Location: consultar_alertas.php');
        exit();
    }

    // Verificar se a requisição é um POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $_SESSION['mensagem_erro'] = "Método de requisição não permitido.";
        header('Location: consultar_alertas.php');
        exit();
    }

    // Obter parâmetros do formulário
    $id_alerta_str = $_POST['id_alerta'] ?? '';
    $tipo_alerta_id_str = $_POST['tipo_alerta'] ?? '';
    $nova_descricao = $_POST['descricao'] ?? '';

    if (empty($id_alerta_str) || empty($tipo_alerta_id_str)) {
        $_SESSION['mensagem_erro'] = "Os campos de ID do alerta e Tipo de Alerta são obrigatórios para editar o alerta.";
        header('Location: consultar_alertas.php');
        exit();
    }

    $id_alerta = 0;
    $tipo_alerta_id = 0;
    $stmt = null;

    try {
        $id_alerta = (int) $id_alerta_str;
        $tipo_alerta_id = (int) $tipo_alerta_id_str;

        // Atualizar a tabela alerta - Adicionada a coluna 'descricao' à query de atualização
        $sql_update_alerta = "UPDATE alerta SET tipo_alerta = ?, descricao = ? WHERE id_alerta = ?";

        // Preparar a declaração
        $stmt = $conn->prepare($sql_update_alerta);

        // Verificar se a preparação da declaração foi bem-sucedida
        if ($stmt === false) {
            throw new Exception("Erro ao preparar a declaração: ");
        }

        // Vincular parâmetros - Adicionado 's' para string da descrição
        $stmt->bind_param("isi", $tipo_alerta_id, $nova_descricao, $id_alerta);

        // Executar a declaração
        $linhas_atualizadas = 0;
        if ($stmt->execute()) {
            $linhas_atualizadas = $stmt->affected_rows;
        } else {
            throw new Exception("Erro ao executar a atualização: " . $stmt->error);
        }

        if ($linhas_atualizadas > 0) {
            $mensagem_sucesso = "Tipo de alerta e descrição atualizados com sucesso!";
            $_SESSION['mensagem_sucesso'] = $mensagem_sucesso;
        } else {
            $mensagem_erro = "Nenhum alerta foi atualizado. Verifique se o ID do alerta está correto.";
            $_SESSION['mensagem_erro'] = $mensagem_erro;
        }

    } catch (Exception $e) {
        $mensagem_erro = "Erro: " . $e->getMessage();
        $_SESSION['mensagem_erro'] = $mensagem_erro;
        error_log($e->getMessage());
    } finally {
        // Fechar recursos
        if ($stmt !== null) {
            $stmt->close();
        }
    }

    // Redireciona de volta para a página de consulta de alertas com a mensagem apropriada
    header('Location: consultar_alertas.php');
    exit();
?>