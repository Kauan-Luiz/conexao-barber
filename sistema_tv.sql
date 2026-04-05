-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 04-Abr-2026 às 21:54
-- Versão do servidor: 10.4.32-MariaDB
-- versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `sistema_tv`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `logs_exibicao`
--

CREATE TABLE `logs_exibicao` (
  `id` int(11) NOT NULL,
  `terminal_id` int(11) NOT NULL,
  `midia_id` int(11) NOT NULL,
  `exibido_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `midias`
--

CREATE TABLE `midias` (
  `id` int(11) NOT NULL,
  `nome_arquivo` varchar(255) NOT NULL,
  `tipo` enum('imagem','video') NOT NULL,
  `duracao_segundos` int(11) DEFAULT 10,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `playlists`
--

CREATE TABLE `playlists` (
  `id` int(11) NOT NULL,
  `terminal_id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `data_inicio` datetime DEFAULT NULL,
  `data_fim` datetime DEFAULT NULL,
  `status` enum('ativa','inativa') DEFAULT 'ativa',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `playlists`
--

INSERT INTO `playlists` (`id`, `terminal_id`, `nome`, `data_inicio`, `data_fim`, `status`, `criado_em`) VALUES
(1, 1, 'Roteiro Principal', NULL, NULL, 'ativa', '2026-04-02 22:59:49');

-- --------------------------------------------------------

--
-- Estrutura da tabela `playlist_midias`
--

CREATE TABLE `playlist_midias` (
  `id` int(11) NOT NULL,
  `playlist_id` int(11) NOT NULL,
  `midia_id` int(11) NOT NULL,
  `ordem` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `terminais`
--

CREATE TABLE `terminais` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `chave_acesso` varchar(10) NOT NULL,
  `ultima_batida` datetime DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `terminais`
--

INSERT INTO `terminais` (`id`, `nome`, `chave_acesso`, `ultima_batida`, `criado_em`) VALUES
(1, 'academia', 'F43D95', NULL, '2026-04-02 22:59:45');

-- --------------------------------------------------------

--
-- Estrutura da tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expira` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `criado_em`, `reset_token`, `reset_expira`) VALUES
(2, 'Kauan Admin', 'admin@weblab.com', '$2y$10$vEEvbC5xU7sTWKM4609r2.0rc1QS2ewTDKjkSscXuaqBxuwiCHmqC', '2026-04-02 22:45:53', NULL, NULL);

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `logs_exibicao`
--
ALTER TABLE `logs_exibicao`
  ADD PRIMARY KEY (`id`),
  ADD KEY `terminal_id` (`terminal_id`),
  ADD KEY `midia_id` (`midia_id`);

--
-- Índices para tabela `midias`
--
ALTER TABLE `midias`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `playlists`
--
ALTER TABLE `playlists`
  ADD PRIMARY KEY (`id`),
  ADD KEY `terminal_id` (`terminal_id`);

--
-- Índices para tabela `playlist_midias`
--
ALTER TABLE `playlist_midias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `playlist_id` (`playlist_id`),
  ADD KEY `midia_id` (`midia_id`);

--
-- Índices para tabela `terminais`
--
ALTER TABLE `terminais`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `chave_acesso` (`chave_acesso`);

--
-- Índices para tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `logs_exibicao`
--
ALTER TABLE `logs_exibicao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `midias`
--
ALTER TABLE `midias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `playlists`
--
ALTER TABLE `playlists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `playlist_midias`
--
ALTER TABLE `playlist_midias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `terminais`
--
ALTER TABLE `terminais`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `logs_exibicao`
--
ALTER TABLE `logs_exibicao`
  ADD CONSTRAINT `logs_exibicao_ibfk_1` FOREIGN KEY (`terminal_id`) REFERENCES `terminais` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `logs_exibicao_ibfk_2` FOREIGN KEY (`midia_id`) REFERENCES `midias` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `playlists`
--
ALTER TABLE `playlists`
  ADD CONSTRAINT `playlists_ibfk_1` FOREIGN KEY (`terminal_id`) REFERENCES `terminais` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `playlist_midias`
--
ALTER TABLE `playlist_midias`
  ADD CONSTRAINT `playlist_midias_ibfk_1` FOREIGN KEY (`playlist_id`) REFERENCES `playlists` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `playlist_midias_ibfk_2` FOREIGN KEY (`midia_id`) REFERENCES `midias` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
