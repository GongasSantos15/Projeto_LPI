<?php
session_start();

// Include Conexão BD
include("basedados/basedados.h");

// Verifica se o utilizador tem sessão iniciada, senão tiver redireciona para a página de login
if (!isset($_SESSION['id_utilizador'])) {
    header("Location: entrar.php");
    exit();
}

$id_utilizador = $_SESSION['id_utilizador'];

// Verifica se o id_viagem foi passado pela URL
if (isset($_GET['id_viagem'])) {
    $id_viagem = $_GET['id_viagem'];

    if ($conn) {
        // SQL para inserir o bilhete na BD
        $sql_inserir = "INSERT INTO bilhete (id_viagem, id_utilizador) VALUES (?, ?)";
        $stmt_inserir = $conn->prepare($sql_inserir);

        if ($stmt_inserir) {
            $stmt_inserir->bind_param("ii", $id_viagem, $id_utilizador);

            if ($stmt_inserir->execute()) {
                $_SESSION['mensagem_sucesso'] = "Bilhete comprado com sucesso. A redirecionar...";
                header('Location: viagens.php');
                exit();
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