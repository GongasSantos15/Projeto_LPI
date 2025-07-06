-- apagar_bd.sql

-- Selecionar base de dados, caso ainda exista
USE projeto_lpi;

-- Desativar restrições de chave estrangeira para evitar erros ao eliminar tabelas
SET FOREIGN_KEY_CHECKS = 0;

-- Eliminar tabelas na ordem inversa da sua dependência
DROP TABLE IF EXISTS utilizador_alerta;
DROP TABLE IF EXISTS bilhete;
DROP TABLE IF EXISTS utilizador;
DROP TABLE IF EXISTS viagem;
DROP TABLE IF EXISTS rota;
DROP TABLE IF EXISTS alerta;
DROP TABLE IF EXISTS carteira;

-- Reativar restrições de chave estrangeira
SET FOREIGN_KEY_CHECKS = 1;

-- Eliminar a base de dados por completo
DROP DATABASE IF EXISTS projeto_lpi;
