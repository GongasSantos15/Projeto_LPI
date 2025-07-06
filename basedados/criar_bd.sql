-- criar_bd.sql

-- Criação da base de dados
CREATE DATABASE IF NOT EXISTS projeto_lpi;
USE projeto_lpi;

-- Tabela: carteira
CREATE TABLE carteira (
  id_carteira INT NOT NULL AUTO_INCREMENT,
  saldo INT NOT NULL,
  PRIMARY KEY (id_carteira)
);

INSERT INTO carteira (id_carteira, saldo) VALUES
(1, 120),
(2, 20);

-- Tabela: alerta
CREATE TABLE alerta (
  id_alerta INT NOT NULL AUTO_INCREMENT,
  descricao VARCHAR(200) NOT NULL,
  estado BOOLEAN NOT NULL,
  PRIMARY KEY (id_alerta)
);


-- Tabela: rota
CREATE TABLE rota (
  id INT NOT NULL AUTO_INCREMENT,
  origem VARCHAR(40),
  destino VARCHAR(40),
  estado BOOLEAN NOT NULL,
  PRIMARY KEY (id)
);

INSERT INTO rota (id, origem, destino, estado) VALUES
(1, 'Lisboa', 'Castelo Branco', 1),
(2, 'Castelo Branco', 'Lisboa', 1),
(3, 'Lisboa', 'Porto', 1),
(4, 'Porto', 'Lisboa', 1),
(5, 'Lisboa', 'Faro', 1),
(6, 'Faro', 'Lisboa', 1),
(7, 'Castelo Branco', 'Porto', 1),
(8, 'Porto', 'Castelo Branco', 1),
(9, 'Faro', 'Castelo Branco', 1),
(10, 'Castelo Branco', 'Faro', 1),
(11, 'Porto', 'Faro', 1),
(12, 'Faro', 'Porto', 1);


-- Tabela: viagem
CREATE TABLE viagem (
  id INT NOT NULL,
  data_hora DATETIME NOT NULL,
  id_rota INT NOT NULL,
  preco FLOAT NOT NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (id_rota) REFERENCES rota(id)
);

INSERT INTO viagem (id, data_hora, id_rota, preco) VALUES
(1, '2025-06-01 09:00:00', 1, 10),
(2, '2025-06-01 12:00:00', 2, 10),
(3, '2025-06-01 15:00:00', 1, 10),
(4, '2025-06-01 18:00:00', 2, 10),
(5, '2025-06-01 21:00:00', 1, 10),
(6, '2025-06-01 09:00:00', 3, 10),
(7, '2025-06-01 12:00:00', 4, 10),
(8, '2025-06-01 15:00:00', 3, 10),
(9, '2025-06-01 18:00:00', 4, 10),
(10, '2025-06-01 21:00:00', 3, 10),
(11, '2025-06-01 09:00:00', 5, 10),
(12, '2025-06-01 12:00:00', 6, 10),
(13, '2025-06-01 15:00:00', 5, 10),
(14, '2025-06-01 18:00:00', 6, 10),
(15, '2025-06-01 21:00:00', 5, 10),
(16, '2025-06-01 09:00:00', 7, 10),
(17, '2025-06-01 12:00:00', 8, 10),
(18, '2025-06-01 15:00:00', 7, 10),
(19, '2025-06-01 18:00:00', 8, 10),
(20, '2025-06-01 21:00:00', 7, 10),
(21, '2025-06-01 09:00:00', 9, 10),
(22, '2025-06-01 12:00:00', 10, 10),
(23, '2025-06-01 15:00:00', 9, 10),
(24, '2025-06-01 18:00:00', 10, 10),
(25, '2025-06-01 21:00:00', 9, 10),
(26, '2025-06-01 09:00:00', 11, 10),
(27, '2025-06-01 12:00:00', 12, 10),
(28, '2025-06-01 15:00:00', 11, 10),
(29, '2025-06-01 18:00:00', 12, 10),
(30, '2025-06-01 21:00:00', 11, 10);

-- Tabela: utilizador
CREATE TABLE utilizador (
  id INT NOT NULL AUTO_INCREMENT,
  nome_proprio VARCHAR(40),
  nome_utilizador VARCHAR(40),
  palavra_passe VARCHAR(100),
  tipo_utilizador INT NOT NULL,
  id_carteira INT,
  PRIMARY KEY (id),
  UNIQUE KEY (id),
  FOREIGN KEY (id_carteira) REFERENCES carteira(id_carteira)
);

INSERT INTO utilizador (id, nome_proprio, nome_utilizador, palavra_passe, tipo_utilizador, id_carteira) VALUES
(1, 'Catarina', 'admin', '21232f297a57a5a743894a0e4a801fc3', 1, 1),
(2, 'Carlos', 'cliente', '4983a0ab83ed86e0e7213c8783940193', 3, 2),
(3, 'Gonçalo', 'funcionario', 'cc7a84634199040d54376793842fe035', 2, 1),
(4, NULL, 'visitante', NULL, 4, NULL);


-- Tabela: bilhete
CREATE TABLE bilhete (
  id INT NOT NULL AUTO_INCREMENT,
  id_viagem INT NOT NULL,
  id_utilizador INT NOT NULL,
  data_compra DATETIME NOT NULL,
  estado BOOLEAN NOT NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (id_viagem) REFERENCES viagem(id),
  FOREIGN KEY (id_utilizador) REFERENCES utilizador(id)
);


-- Tabela: utilizador_alerta
CREATE TABLE utilizador_alerta (
  id_alerta INT NOT NULL,
  id_utilizador INT NOT NULL,
  data_hora DATETIME NOT NULL,
  PRIMARY KEY (id_alerta, id_utilizador),
  FOREIGN KEY (id_alerta) REFERENCES alerta(id_alerta),
  FOREIGN KEY (id_utilizador) REFERENCES utilizador(id)
);



COMMIT;
