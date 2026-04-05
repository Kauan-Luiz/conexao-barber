<?php
require_once '../../core/db.php';
require_once '../../core/funcoes.php';

// Só entra se for POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_formatado = filter_input(INPUT_POST, 'nome_exibicao', FILTER_SANITIZE_STRING);
    $duracao = filter_input(INPUT_POST, 'duracao', FILTER_SANITIZE_NUMBER_INT) ?: 10;
    
    $arquivo = $_FILES['midia'];
    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
    
    // Extensões permitidas (O Senior não confia em ninguém)
    $permitidos = ['jpg', 'jpeg', 'png', 'mp4'];
    
    if (!in_array($extensao, $permitidos)) {
        die("Erro: Formato de arquivo não permitido.");
    }

    // Gerar nome único para evitar sobrescrever arquivos
    $novo_nome = md5(uniqid()) . "." . $extensao;
    $destino = UPLOAD_PATH . $novo_nome;

    if (move_uploaded_file($arquivo['tmp_name'], $destino)) {
        // Identificar se é imagem ou vídeo para o banco
        $tipo = (in_array($extensao, ['mp4'])) ? 'video' : 'imagem';

        $sql = "INSERT INTO midias (nome_arquivo, tipo, duracao_segundos) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$novo_nome, $tipo, $duracao]);

        header("Location: index.php?sucesso=1");
    } else {
        echo "Erro ao mover o arquivo.";
    }
}