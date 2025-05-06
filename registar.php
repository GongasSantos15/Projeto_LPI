<?php
    // Include
    include 'C:\xampp\htdocs\lpi\Projeto_LPI\basedados\basedados.h';
    include 'constUtilizadores.php';

    // Iniciar a sessão
    session_start();

    // Verificar se algum dos campos está vazio
    if(isset($_POST['nome']) && isset($_POST['password']) && $_POST['password'] === $_POST['confirm-pass']) {
        
        // Dados recebidos do formulário
        $user = $_POST['nome'];
        $pass = $_POST['password'];
        $tipo_user = CLIENTE;

        $sql = "INSERT INTO user (nome, password, tipo_user) VALUES ('$user', '$pass', '$tipo_user')";
        $res = mysqli_query($conn, $sql);

        if ($res) {
            echo "<script>alert('Utilizador registado com sucesso!');</script>";
            header("refresh:0; url=entrar.html");
        } else {
            // Erro na query
            echo "<script>alert('Erro ao registar utilizador: " . mysqli_error($conn) . "');</script>";
        }
    
    } else {
        // Erro na validação do formulário
        echo
        "<script>
            alert('Erro: Os campos estão vazios ou as passwords não coincidem.');
            window.location.href = 'registar.html'; // ou o nome da sua página de registo
        </script>";
    }
?>