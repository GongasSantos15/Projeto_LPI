<?php

	// Variáveis para usar na conexão à base de dados
	define("USER_BD", "root");
	define("PASS_BD", "");
	define("NOME_BD", "projeto_lpi");
	$hostname_conn = "localhost";

	// Conexão orientada a objetos
	$conn = new mysqli($hostname_conn, USER_BD, PASS_BD, NOME_BD);

	// Verifica conexão
	if ($conn->connect_error) {
		echo "Erro na conexão: " . $conn->connect_error;
		exit();
	}
?>