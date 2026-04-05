<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}
require_once '../../core/db.php';

$mensagem = '';
$tv_id = filter_input(INPUT_GET, 'tv', FILTER_VALIDATE_INT);
$tv_selecionada = null;
$playlist = null;
$itens_playlist = [];

// ==========================================
// FUNÇÃO DE ARMAZENAMENTO DA VPS
// ==========================================
function calcularTamanhoPasta($dir) {
    $tamanho = 0;
    if (is_dir($dir)) {
        foreach (glob(rtrim($dir, '/').'/*', GLOB_NOSORT) as $cada) {
            $tamanho += is_file($cada) ? filesize($cada) : calcularTamanhoPasta($cada);
        }
    }
    return $tamanho;
}

$espaco_usado_bytes = calcularTamanhoPasta('../uploads');
$limite_bytes = 10 * 1024 * 1024 * 1024; // 10 GB
$limite_upload_bytes = 200 * 1024 * 1024; // 200 MB por arquivo

$espaco_usado_gb = round($espaco_usado_bytes / (1024 * 1024 * 1024), 2);
$porcentagem_uso = round(($espaco_usado_bytes / $limite_bytes) * 100, 1);

// ==========================================
// 1. REORDENAÇÃO SILENCIOSA VIA AJAX
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'reordenar') {
    $nova_ordem = json_decode($_POST['ordem'], true);
    if ($tv_id && is_array($nova_ordem)) {
        $stmt = $pdo->prepare("SELECT id FROM playlists WHERE terminal_id = ? LIMIT 1");
        $stmt->execute([$tv_id]);
        $play_atual = $stmt->fetch();
        if ($play_atual) {
            foreach ($nova_ordem as $index => $item_id) {
                $ordem_real = $index + 1;
                $pdo->prepare("UPDATE playlist_midias SET ordem = ? WHERE id = ? AND playlist_id = ?")->execute([$ordem_real, $item_id, $play_atual['id']]);
            }
        }
    }
    exit;
}

// ==========================================
// 2. LÓGICA DE UPLOAD (Backend Backup)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['arquivo'])) {
    $duracao = filter_input(INPUT_POST, 'duracao', FILTER_VALIDATE_INT) ?: 10;
    $arquivo = $_FILES['arquivo'];
    $tv_atual = filter_input(INPUT_POST, 'tv_id', FILTER_VALIDATE_INT);
    
    if ($arquivo['error'] === UPLOAD_ERR_OK) {
        $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
        
        $permitidos = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm'];
        
        if (!in_array($extensao, $permitidos)) {
            $mensagem = "<div class='msg erro'><i class='fa-solid fa-triangle-exclamation'></i> Formato inválido capturado pelo servidor.</div>";
        } 
        else if ($arquivo['size'] > $limite_upload_bytes) {
            $mensagem = "<div class='msg erro'><i class='fa-solid fa-weight-hanging'></i> O arquivo é muito pesado! Limite de 200MB.</div>";
        } 
        else if (($espaco_usado_bytes + $arquivo['size']) > $limite_bytes) {
            $mensagem = "<div class='msg erro'><i class='fa-solid fa-server'></i> Espaço da VPS esgotado! Limite de 10GB atingido.</div>";
        } 
        else {
            $novo_nome = md5(time() . $arquivo['name']) . '.' . $extensao;
            $caminho_destino = '../uploads/' . $novo_nome;
            
            if (move_uploaded_file($arquivo['tmp_name'], $caminho_destino)) {
                $tipo = in_array($extensao, ['mp4', 'webm']) ? 'video' : 'imagem';
                $duracao_final = ($tipo === 'video') ? 0 : $duracao;
                
                $stmt = $pdo->prepare("INSERT INTO midias (nome_arquivo, tipo, duracao_segundos) VALUES (?, ?, ?)");
                $stmt->execute([$novo_nome, $tipo, $duracao_final]);
                header("Location: playlists.php?tv=" . $tv_atual . "&upload=sucesso");
                exit;
            } else {
                $mensagem = "<div class='msg erro'>Erro ao salvar o arquivo no servidor.</div>";
            }
        }
    } else {
        $mensagem = "<div class='msg erro'>Selecione um arquivo válido para subir.</div>";
    }
}

if (isset($_GET['upload']) && $_GET['upload'] == 'sucesso') {
    $mensagem = "<div class='msg sucesso'><i class='fa-solid fa-circle-check'></i> Mídia enviada e disponível na biblioteca!</div>";
}

// ==========================================
// 3. LÓGICA DA PLAYLIST (Adicionar e Remover)
// ==========================================
if ($tv_id) {
    $stmt = $pdo->prepare("SELECT * FROM terminais WHERE id = ?");
    $stmt->execute([$tv_id]);
    $tv_selecionada = $stmt->fetch();

    if ($tv_selecionada) {
        $stmt = $pdo->prepare("SELECT * FROM playlists WHERE terminal_id = ? LIMIT 1");
        $stmt->execute([$tv_id]);
        $playlist = $stmt->fetch();

        if (!$playlist) {
            $pdo->prepare("INSERT INTO playlists (terminal_id, nome) VALUES (?, 'Roteiro Principal')")->execute([$tv_id]);
            $stmt = $pdo->prepare("SELECT * FROM playlists WHERE terminal_id = ? LIMIT 1");
            $stmt->execute([$tv_id]);
            $playlist = $stmt->fetch();
        }

        if (isset($_GET['add_midia'])) {
            $midia_id = filter_input(INPUT_GET, 'add_midia', FILTER_VALIDATE_INT);
            if ($midia_id) {
                $stmt = $pdo->prepare("SELECT MAX(ordem) FROM playlist_midias WHERE playlist_id = ?");
                $stmt->execute([$playlist['id']]);
                $max_ordem = $stmt->fetchColumn();
                $nova_ordem = $max_ordem ? $max_ordem + 1 : 1;
                $pdo->prepare("INSERT INTO playlist_midias (playlist_id, midia_id, ordem) VALUES (?, ?, ?)")->execute([$playlist['id'], $midia_id, $nova_ordem]);
                header("Location: playlists.php?tv=" . $tv_id);
                exit;
            }
        }

        if (isset($_GET['remover_item'])) {
            $item_id = filter_input(INPUT_GET, 'remover_item', FILTER_VALIDATE_INT);
            if ($item_id) {
                $pdo->prepare("DELETE FROM playlist_midias WHERE id = ? AND playlist_id = ?")->execute([$item_id, $playlist['id']]);
                header("Location: playlists.php?tv=" . $tv_id);
                exit;
            }
        }

        $sql = "SELECT pm.id as item_id, pm.ordem, m.* FROM playlist_midias pm 
                JOIN midias m ON pm.midia_id = m.id 
                WHERE pm.playlist_id = ? ORDER BY pm.ordem ASC, pm.id ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$playlist['id']]);
        $itens_playlist = $stmt->fetchAll();
    }
}

$todas_tvs = $pdo->query("SELECT * FROM terminais ORDER BY nome ASC")->fetchAll();
$todas_midias = $pdo->query("SELECT * FROM midias ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciador de Playlists</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    
    <link rel="stylesheet" href="../assets/css/global.css">
    
    <link rel="stylesheet" href="../assets/css/playlists.css">
</head>

<body>

    <?php include 'components/sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="welcome-text">
                <h1>Painel de Controle da TV</h1>
                <p>Arraste os itens na linha do tempo para reordenar a sua playlist.</p>
            </div>
        </header>

        <div class="content-area">
            <?= $mensagem ?>
            
            <form action="playlists.php" method="GET" class="tv-selector">
                <i class="fa-solid fa-tv" style="color: var(--azul-texto); font-size: 20px;"></i>
                <select name="tv" required onchange="this.form.submit()">
                    <option value="">-- Selecione uma TV para gerenciar --</option>
                    <?php foreach ($todas_tvs as $tv_opcao): ?>
                        <option value="<?= $tv_opcao['id'] ?>" <?= ($tv_id == $tv_opcao['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($tv_opcao['nome']) ?> (Cód: <?= $tv_opcao['chave_acesso'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-selecionar">Carregar Roteiro</button>
            </form>

            <?php if ($tv_selecionada): ?>
                
                <?php 
                    $tempo_total = 0;
                    foreach ($itens_playlist as $item) { 
                        $tempo = ($item['tipo'] == 'video' && $item['duracao_segundos'] == 0) ? 30 : $item['duracao_segundos'];
                        $tempo_total += $tempo; 
                    }
                    $minutos = floor($tempo_total / 60);
                    $segundos = $tempo_total % 60;
                ?>

                <div class="playlist-container">
                    
                    <div class="panel" style="border-top: 4px solid var(--azul-destaque);">
                        <div class="panel-header">
                            <div>
                                <i class="fa-solid fa-play" style="color: var(--azul-texto); margin-right: 8px;"></i> 
                                Rodando na: <?= htmlspecialchars($tv_selecionada['nome']) ?>
                            </div>
                            <span class="badge-tempo" title="Aprox. contando vídeos como 30s"><i class="fa-regular fa-clock"></i> Loop: ~<?= sprintf('%02d:%02d', $minutos, $segundos) ?></span>
                        </div>
                        
                        <div class="timeline-list" id="lista-timeline">
                            <?php if (count($itens_playlist) > 0): ?>
                                <?php $contador = 1; foreach ($itens_playlist as $item): ?>
                                    <div class="media-item" data-id="<?= $item['item_id'] ?>">
                                        <div class="media-info">
                                            <div class="drag-handle" title="Segure e arraste para reordenar"><i class="fa-solid fa-grip-vertical"></i></div>
                                            <div class="ordem-numero"><?= $contador++ ?></div>
                                            
                                            <?php if ($item['tipo'] === 'imagem'): ?>
                                                <img src="../uploads/<?= htmlspecialchars($item['nome_arquivo']) ?>" class="media-thumb" alt="Preview">
                                            <?php else: ?>
                                                <div class="media-thumb"><i class="fa-solid fa-film"></i></div>
                                            <?php endif; ?>
                                            
                                            <div class="media-text">
                                                <h4><?= htmlspecialchars($item['nome_arquivo']) ?></h4>
                                                <?php if ($item['tipo'] === 'imagem'): ?>
                                                    <p><i class="fa-regular fa-clock"></i> <?= $item['duracao_segundos'] ?> Segundos</p>
                                                <?php else: ?>
                                                    <p><i class="fa-solid fa-video"></i> Toca até o final</p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="acoes-container">
                                            <a href="playlists.php?tv=<?= $tv_id ?>&remover_item=<?= $item['item_id'] ?>" class="btn-remove" title="Remover da TV"><i class="fa-solid fa-xmark"></i></a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div style="text-align: center; padding: 40px 20px; color: var(--texto-secundario); position: relative; z-index: 5; background: #fff;">
                                    <i class="fa-solid fa-arrow-right" style="font-size: 30px; margin-bottom: 10px; color: #cbd5e1;"></i>
                                    <p>Esta TV está em branco.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel-header">
                            <div><i class="fa-solid fa-cloud-arrow-up" style="color: var(--texto-secundario); margin-right: 8px;"></i> Subir Nova Mídia</div>
                        </div>
                        
                        <form action="playlists.php" method="POST" enctype="multipart/form-data" class="upload-inline" id="form-upload">
                            <input type="hidden" name="tv_id" value="<?= $tv_id ?>">
                            <div class="upload-row">
                                <input type="file" name="arquivo" id="input-arquivo" required title="Escolher arquivo">
                                <input type="number" name="duracao" id="input-duracao" value="10" min="1" placeholder="Segs" title="Segundos (Ignorado para vídeo)">
                                <button type="submit" class="btn-upload"><i class="fa-solid fa-upload"></i> Subir</button>
                            </div>
                            
                            <div>
                                <div class="storage-info">
                                    <span>Uso da VPS (Máx 10 GB)</span>
                                    <span><?= $espaco_usado_gb ?> GB / 10 GB (<?= $porcentagem_uso ?>%)</span>
                                </div>
                                <div class="storage-bar-container">
                                    <div class="storage-bar" style="background: <?= $porcentagem_uso > 85 ? 'var(--erro)' : 'var(--azul-destaque)' ?>;"></div>
                                </div>
                                <div style="font-size: 10px; color: #94a3b8; margin-top: 4px; text-align: right;">Limite: 200MB por envio</div>
                            </div>
                        </form>

                        <div class="panel-header" style="margin-top: 10px; border-top: 1px solid var(--borda-suave); padding-top: 20px;">
                            <div><i class="fa-solid fa-photo-film" style="color: var(--texto-secundario); margin-right: 8px;"></i> Mídias Disponíveis</div>
                        </div>
                        
                        <div class="media-list-right" style="max-height: 400px; overflow-y: auto; padding-right: 5px;">
                            <?php if (count($todas_midias) > 0): ?>
                                <?php foreach ($todas_midias as $midia): ?>
                                    <div class="media-item">
                                        <div class="media-info">
                                            <?php if ($midia['tipo'] === 'imagem'): ?>
                                                <img src="../uploads/<?= htmlspecialchars($midia['nome_arquivo']) ?>" class="media-thumb" alt="Preview">
                                            <?php else: ?>
                                                <div class="media-thumb"><i class="fa-solid fa-film"></i></div>
                                            <?php endif; ?>
                                            
                                            <div class="media-text">
                                                <h4><?= htmlspecialchars($midia['nome_arquivo']) ?></h4>
                                                <?php if ($midia['tipo'] === 'imagem'): ?>
                                                    <p><i class="fa-regular fa-clock"></i> <?= $midia['duracao_segundos'] ?> Segs</p>
                                                <?php else: ?>
                                                    <p><i class="fa-solid fa-video"></i> Toca até o final</p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <a href="playlists.php?tv=<?= $tv_id ?>&add_midia=<?= $midia['id'] ?>" class="btn-add" title="Adicionar à TV selecionada"><i class="fa-solid fa-plus"></i></a>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="text-align: center; color: var(--texto-secundario); padding: 20px; font-size: 14px;">Use a caixa acima para subir vídeos e fotos.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            <?php endif; ?>
        </div>
    </main>

    <div id="custom-modal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-icon" id="modal-icon"></div>
            <h3 id="modal-title">Aviso</h3>
            <p id="modal-message"></p>
            <button onclick="fecharModal()" class="btn-modal">Entendi</button>
        </div>
    </div>

    <script>
        // Funções de Controle do Modal
        function abrirModal(titulo, mensagem, iconeHtml, classeIcone = 'erro') {
            document.getElementById('modal-title').innerText = titulo;
            document.getElementById('modal-message').innerHTML = mensagem;
            
            const iconeDiv = document.getElementById('modal-icon');
            iconeDiv.innerHTML = iconeHtml;
            
            // Muda a cor do ícone dependendo do tipo (erro vermelho, info amarelo)
            if (classeIcone === 'info') {
                iconeDiv.className = 'modal-icon info';
            } else {
                iconeDiv.className = 'modal-icon';
            }

            document.getElementById('custom-modal').classList.add('active');
        }

        function fecharModal() {
            document.getElementById('custom-modal').classList.remove('active');
        }

        document.addEventListener("DOMContentLoaded", function() {
            // DRAG AND DROP
            const listaTimeline = document.getElementById('lista-timeline');
            if (listaTimeline) {
                new Sortable(listaTimeline, {
                    handle: '.drag-handle',
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    onEnd: function (evt) {
                        const itens = listaTimeline.querySelectorAll('.media-item');
                        let novaOrdem = [];
                        itens.forEach(item => {
                            if(item.getAttribute('data-id')) novaOrdem.push(item.getAttribute('data-id'));
                        });
                        if(novaOrdem.length > 0) {
                            let formData = new FormData();
                            formData.append('acao', 'reordenar');
                            formData.append('ordem', JSON.stringify(novaOrdem));
                            fetch('playlists.php?tv=<?= $tv_id ?? "" ?>', { method: 'POST', body: formData })
                            .then(() => {
                                let contadores = listaTimeline.querySelectorAll('.ordem-numero');
                                contadores.forEach((el, index) => el.innerText = index + 1);
                            });
                        }
                    }
                });
            }

            // MÁGICA FRONTEND: VERIFICAÇÃO DE FORMATO E TAMANHO ANTES DO UPLOAD
            const inputArquivo = document.getElementById('input-arquivo');
            const inputDuracao = document.getElementById('input-duracao');
            
            // A lista sagrada do que o sistema aceita
            const formatosPermitidos = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm'];
            
            if(inputArquivo) {
                inputArquivo.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if(!file) return;

                    // 1. Pega a extensão do arquivo
                    const nomeArquivo = file.name.toLowerCase();
                    const extensao = nomeArquivo.split('.').pop();
                    
                    // 2. VERIFICAÇÃO DE FORMATO
                    if (!formatosPermitidos.includes(extensao)) {
                        abrirModal(
                            "Formato Não Suportado!", 
                            `Você tentou enviar um arquivo <b>.${extensao.toUpperCase()}</b>. Para garantir que a TV funcione perfeitamente sem engasgos, nós aceitamos apenas formatos web-friendly:
                            <ul>
                                <li><strong>Vídeos:</strong> MP4, WebM</li>
                                <li><strong>Imagens:</strong> JPG, PNG, GIF, WebP</li>
                            </ul>
                            Converta o seu arquivo antes de enviá-lo.`, 
                            "<i class='fa-solid fa-file-circle-xmark'></i>",
                            "info" // Passa a classe pra ficar laranjinha e educativo
                        );
                        inputArquivo.value = ''; // Limpa o arquivo inválido
                        inputDuracao.disabled = false;
                        return;
                    }
                    
                    // 3. VERIFICAÇÃO DE TAMANHO (Limite de 200MB)
                    if(file.size > (200 * 1024 * 1024)) {
                        const tamanhoEmMB = (file.size / (1024 * 1024)).toFixed(2);
                        abrirModal(
                            "Arquivo Muito Pesado!", 
                            `Seu arquivo tem <b>${tamanhoEmMB} MB</b>. Para preservar a velocidade da sua TV, o limite máximo por arquivo é de <b>200 MB</b>.<br><br>Recomendamos usar um software de edição para renderizar o seu vídeo num formato mais leve.`, 
                            "<i class='fa-solid fa-weight-hanging'></i>",
                            "erro" // Vermelho de erro
                        );
                        inputArquivo.value = ''; // Limpa o input
                        return;
                    }

                    // 4. MÁGICA DA UX: Desativa duração se for vídeo
                    if(file.type.includes('video') || extensao === 'mp4' || extensao === 'webm') {
                        inputDuracao.disabled = true;
                        inputDuracao.value = '';
                        inputDuracao.placeholder = 'Auto (Fim)';
                        inputDuracao.title = 'Vídeos tocam até o final automaticamente.';
                    } else {
                        inputDuracao.disabled = false;
                        inputDuracao.value = '10';
                        inputDuracao.placeholder = 'Segs';
                    }
                });
            }
        });
    </script>
</body>
</html>