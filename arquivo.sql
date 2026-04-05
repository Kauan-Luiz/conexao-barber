CREATE DATABASE IF NOT EXISTS sistema_tv;
USE sistema_tv;

-- Tabela para cadastrar as TVs (Terminais)
CREATE TABLE terminais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL, -- Ex: TV Recepção, TV Academia
    chave_acesso VARCHAR(50) UNIQUE NOT NULL, -- Um código que a TV usa para se conectar
    ultima_batida TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de Mídias (Fotos e Vídeos)
CREATE TABLE midias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_arquivo VARCHAR(255) NOT NULL,
    tipo ENUM('imagem', 'video') NOT NULL,
    duracao_segundos INT DEFAULT 10, -- O tempo que a gente conversou!
    ordem INT DEFAULT 0,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);