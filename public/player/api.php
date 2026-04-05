<?php
// Não iniciamos sessão aqui porque a TV não faz "login" com e-mail e senha.
require_once '../../core/db.php';

// O Toque Sênior: Avisamos ao navegador que a resposta NÃO É HTML, é um JSON puro.
header('Content-Type: application/json; charset=utf-8');
// Permite que qualquer TV consiga ler os dados sem ser bloqueada pelo navegador (CORS)
header('Access-Control-Allow-Origin: *');

// Pega o código de 6 letras que a TV vai mandar pela URL (ex: ?token=66EB37)
$token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_STRING);

if (!$token) {
    // Retorna erro 400 (Bad Request) se alguém acessar a API sem mandar o código
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'erro' => 'Token não fornecido.']);
    exit;
}

// ==========================================
// 1. BUSCA A TV E ATUALIZA O "HEARTBEAT"
// ==========================================
$stmt = $pdo->prepare("SELECT id, nome FROM terminais WHERE chave_acesso = ? LIMIT 1");
$stmt->execute([$token]);
$tv = $stmt->fetch();

if (!$tv) {
    // Retorna erro 404 (Not Found) se o código digitado não existir no banco
    http_response_code(404);
    echo json_encode(['sucesso' => false, 'erro' => 'TV não encontrada ou código inválido.']);
    exit;
}

// MÁGICA: A TV chamou a API, então ela está ligada! Atualizamos o painel Admin pra "Online".
$pdo->prepare("UPDATE terminais SET ultima_batida = NOW() WHERE id = ?")->execute([$tv['id']]);


// ==========================================
// 2. BUSCA A PLAYLIST E AS MÍDIAS
// ==========================================
$stmt = $pdo->prepare("SELECT id FROM playlists WHERE terminal_id = ? LIMIT 1");
$stmt->execute([$tv['id']]);
$playlist = $stmt->fetch();

// Se a TV existe mas não tem nenhuma playlist montada
if (!$playlist) {
    echo json_encode([
        'sucesso' => true,
        'tv_nome' => $tv['nome'],
        'midias' => [] // Retorna uma lista vazia
    ]);
    exit;
}

// Busca as fotos e vídeos na ordem exata que o cliente montou no Admin
$sql = "SELECT m.nome_arquivo as arquivo, m.tipo, m.duracao_segundos as duracao 
        FROM playlist_midias pm 
        JOIN midias m ON pm.midia_id = m.id 
        WHERE pm.playlist_id = ? 
        ORDER BY pm.ordem ASC, pm.id ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$playlist['id']]);
$midias = $stmt->fetchAll();

// ==========================================
// 3. ENTREGA O ROTEIRO (JSON)
// ==========================================
echo json_encode([
    'sucesso' => true,
    'tv_nome' => $tv['nome'],
    'total_midias' => count($midias),
    'midias' => $midias
]);