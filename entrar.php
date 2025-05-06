<?php

    // Include
    include 'C:\xampp\htdocs\lpi\Projeto_LPI\basedados\basedados.h';
    include 'constUtilizadores.php';

    // Iniciar sessão
    session_start();

    // Verificar se os campos não estão vazios
    if (isset($_POST["nome"]) && isset($_POST["password"])) {
        // Dados do formulário
        $user = $_POST["nome"];
        $password = $_POST["password"];

        // Selecionar o utilizador correspondente da base de dados
        $sql = "SELECT * FROM user WHERE nome = '$user' AND password = '$password' AND tipo_user != ".CLIENTE_APAGADO.";";
        $result = mysqli_query($conn,$sql);

        if (!$result) {
            die('Could not get data: ' . mysqli_error($conn));
        }
        $row = mysqli_fetch_array($result);

        // Se os dados são válidos, criar as variáveis de sessão e volta à página inicial
        if(strcmp($row["nome"], $user) == 0 && strcmp($row["password"], $password) == 0) {
            $_SESSION["user"] = $row["nome"];
            $_SESSION["tipo"] = $row["tipo_user"];
            header("Location: index.html");
        } else {
            $_SESSION["user"] = -1;
            $_SESSION["tipo"] = -1;
            
            echo "<script>
                    alert('Utilizador não encontrado!');
                    window.location.href = 'entrar.html';
                </script>";
        }
        
        header("refresh:0; url=index.html");
    } else {
        session_destroy();
        echo "<script>
            	alert('Erro: Campos não foram preenchidos corretamente.');
                window.location.href = 'entrar.html';
            </script>";
    }
?>