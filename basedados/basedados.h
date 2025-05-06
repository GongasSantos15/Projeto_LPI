<?php
	define("USER_BD",      "root");
	define("PASS_BD",      "");
	define("NOME_BD",      "projeto_lpi");
	$hostname_conn = "localhost";
	
	// Conectamos ao nosso servidor MySQL
	if(!($conn = mysqli_connect($hostname_conn, USER_BD, PASS_BD))) 
	{
	   echo "Erro ao conectar ao MySQL.";
	   exit;
	}
	// Selecionamos a nossa base de dados MySQL
	if(!($con = mysqli_select_db($conn, NOME_BD))) 
	{
	   echo "Erro ao selecionar ao MySQL.";
	   exit;
	}
?>