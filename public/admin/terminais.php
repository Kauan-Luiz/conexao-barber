<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}
require_once '../../core/db.php';

$mensagem = '';

// ==========================================
// 1. CADASTRAR NOVA TV
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome_tv'])) {
    $nome_tv = filter_input(INPUT_POST, 'nome_tv', FILTER_SANITIZE_STRING);
    
    if (!empty($nome_tv)) {
        $chave_acesso = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
        try {
            $sql = "INSERT INTO terminais (nome, chave_acesso) VALUES (?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome_tv, $chave_acesso]);
            $mensagem = "<div class='msg sucesso'><i class='fa-solid fa-circle-check'></i> TV cadastrada com sucesso! Código gerado.</div>";
        } catch (PDOException $e) {
            $mensagem = "<div class='msg erro'><i class='fa-solid fa-circle-exclamation'></i> Erro ao cadastrar. Tente novamente.</div>";
        }
    } else {
        $mensagem = "<div class='msg erro'><i class='fa-solid fa-triangle-exclamation'></i> O nome da TV é obrigatório.</div>";
    }
}

// ==========================================
// 2. EXCLUIR TV
// ==========================================
if (isset($_GET['excluir'])) {
    $id_excluir = filter_input(INPUT_GET, 'excluir', FILTER_VALIDATE_INT);
    if ($id_excluir) {
        $stmt = $pdo->prepare("DELETE FROM terminais WHERE id = ?");
        $stmt->execute([$id_excluir]);
        header("Location: terminais.php");
        exit;
    }
}

// ==========================================
// 3. BUSCAR TODAS AS TVS
// ==========================================
$stmt = $pdo->query("SELECT * FROM terminais ORDER BY id DESC");
$terminais = $stmt->fetchAll();

// Função para checar Heartbeat (Online/Offline)
function statusTV($ultima_batida) {
    if (!$ultima_batida) return ['class' => 'offline', 'texto' => 'Nunca conectada'];
    $agora = new DateTime();
    $batida = new DateTime($ultima_batida);
    $diferenca = $agora->getTimestamp() - $batida->getTimestamp();
    
    if ($diferenca <= 300) {
        return ['class' => 'online', 'texto' => 'Online agora'];
    }
    return ['class' => 'offline', 'texto' => 'Offline (' . $batida->format('d/m H:i') . ')'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terminais - Sistema Mídia Indoor</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/terminais.css">
</head>
<body>

    <?php include 'components/sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="welcome-text">
                <h1>Terminais (TVs)</h1>
                <p>Cadastre novos pontos de exibição e pegue o código de pareamento.</p>
            </div>
        </header>

        <div class="content-area">
            <?= $mensagem ?>

            <div class="panel">
                <div class="panel-header"><i class="fa-solid fa-plus"></i> Adicionar Nova TV</div>
                <form action="terminais.php" method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nome_tv">Nome de Identificação (Ex: TV Recepção, Totem Entrada)</label>
                            <input type="text" id="nome_tv" name="nome_tv" placeholder="Digite o nome do local ou da TV..." required autocomplete="off">
                        </div>
                        <button type="submit" class="btn-submit"><i class="fa-solid fa-check"></i> Gerar Código e Salvar</button>
                    </div>
                </form>
            </div>

            <div class="tv-grid">
                <?php if (count($terminais) > 0): ?>
                    <?php foreach($terminais as $tv): ?>
                        <?php $status = statusTV($tv['ultima_batida']); ?>
                        <div class="tv-card">
                            
                            <div class="tv-card-header">
                                <div class="tv-icon-title">
                                    <div class="tv-icon"><i class="fa-solid fa-tv"></i></div>
                                    <div class="tv-title">
                                        <h3><?= htmlspecialchars($tv['nome']) ?></h3>
                                        <div class="tv-status <?= $status['class'] ?>">
                                            <i class="fa-solid fa-circle"></i> <?= $status['texto'] ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="tv-code-box">
                                <p>Código de Pareamento</p>
                                <div class="tv-code"><?= htmlspecialchars($tv['chave_acesso']) ?></div>
                            </div>
                            
                            <div class="tv-card-footer">
                                <a href="playlists.php?tv=<?= $tv['id'] ?>" class="btn-manage"><i class="fa-solid fa-sliders"></i> Playlist</a>
                                
                                <a href="terminais.php?excluir=<?= $tv['id'] ?>" class="btn-delete" onclick="return confirm('Tem certeza? A TV apagará e deixará de exibir os vídeos imediatamente.');" title="Excluir TV">
                                    <i class="fa-solid fa-trash-can"></i>
                                </a>
                            </div>

                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="width: 100%; text-align: center; padding: 40px; color: var(--texto-secundario); background: white; border-radius: 12px; border: 1px dashed var(--borda-suave);">
                        <i class="fa-solid fa-tv" style="font-size: 40px; margin-bottom: 15px; color: #e2e8f0;"></i>
                        <h3>Nenhuma TV encontrada</h3>
                        <p style="margin-top: 5px;">Cadastre sua primeira TV usando o formulário acima.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </main>

</body>
</html>