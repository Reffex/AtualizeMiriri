-- --------------------------------------------------------
-- Servidor:                     127.0.0.1
-- Versão do servidor:           10.4.32-MariaDB - mariadb.org binary distribution
-- OS do Servidor:               Win64
-- HeidiSQL Versão:              12.6.0.6765
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Copiando estrutura do banco de dados para renanprojeto
CREATE DATABASE IF NOT EXISTS `renanprojeto` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;
USE `renanprojeto`;

-- Copiando estrutura para tabela renanprojeto.clientes
CREATE TABLE IF NOT EXISTS `clientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `documento` varchar(20) NOT NULL,
  `data_cadastro` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `documento` (`documento`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Exportação de dados foi desmarcado.

-- Copiando estrutura para tabela renanprojeto.indices
CREATE TABLE IF NOT EXISTS `indices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(20) DEFAULT NULL,
  `data_referencia` date DEFAULT NULL,
  `valor` decimal(20,10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`,`data_referencia`)
) ENGINE=InnoDB AUTO_INCREMENT=27475 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Exportação de dados foi desmarcado.

-- Copiando estrutura para tabela renanprojeto.lancamentos
CREATE TABLE IF NOT EXISTS `lancamentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `operacao_id` int(11) DEFAULT NULL,
  `data` date NOT NULL,
  `descricao` varchar(100) NOT NULL,
  `valor` decimal(19,2) NOT NULL,
  `valor_original` decimal(19,2) DEFAULT NULL,
  `tipo` enum('debito','credito') NOT NULL,
  PRIMARY KEY (`id`),
  KEY `operacao_id` (`operacao_id`),
  CONSTRAINT `lancamentos_ibfk_1` FOREIGN KEY (`operacao_id`) REFERENCES `operacoes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Exportação de dados foi desmarcado.

-- Copiando estrutura para tabela renanprojeto.operacoes
CREATE TABLE IF NOT EXISTS `operacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` int(11) DEFAULT NULL,
  `identificador` varchar(50) NOT NULL,
  `indexador` varchar(50) NOT NULL,
  `periodicidade` varchar(20) NOT NULL,
  `atualizar_ate` date DEFAULT NULL,
  `atualizar_dia_debito` int(11) DEFAULT NULL,
  `atualizar_correcao_monetaria` decimal(19,4) DEFAULT NULL,
  `atualizar_juros_nominais` decimal(19,4) DEFAULT NULL,
  `valor_multa` decimal(19,4) DEFAULT NULL,
  `valor_honorarios` decimal(19,4) DEFAULT NULL,
  `observacao` text DEFAULT NULL,
  `tipo_juros` enum('simples','composto') NOT NULL DEFAULT 'composto',
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `cliente_id` (`cliente_id`),
  CONSTRAINT `operacoes_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Exportação de dados foi desmarcado.

-- Copiando estrutura para tabela renanprojeto.usuarios
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Exportação de dados foi desmarcado.

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
