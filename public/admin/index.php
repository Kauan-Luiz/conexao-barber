<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}
require_once '../../core/db.php';

// ==========================================
// LÓGICA DE DADOS
// ==========================================
$stmt = $pdo->query("SELECT COUNT(*) as total FROM terminais");
$total_tvs = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM midias");
$total_midias = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT * FROM terminais ORDER BY id DESC LIMIT 3");
$ultimas_tvs = $stmt->fetchAll();

$stmt = $pdo->query("SELECT * FROM midias ORDER BY id DESC LIMIT 3");
$ultimos_anuncios = $stmt->fetchAll();

function calcularTamanhoPasta($dir) {
    $tamanho = 0;
    if (is_dir($dir)) {
        foreach (glob(rtrim($dir, '/').'/*', GLOB_NOSORT) as $cada) {
            $tamanho += is_file($cada) ? filesize($cada) : calcularTamanhoPasta($cada);
        }
    }
    return $tamanho;
}
$tamanho_bytes = calcularTamanhoPasta('../uploads');
$tamanho_mb = round($tamanho_bytes / 1048576, 2); 
$limite_gb = 10; 
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema Mídia Indoor</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>

   <?php include 'components/sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="welcome-text">
                <h1>Visão Geral</h1>
                <p>Monitore o desempenho e gerencie as mídias da sua rede.</p>
            </div>
            <div class="topbar-actions">
                <button class="btn-icon-top" onclick="location.reload()" title="Atualizar Dados">
                    <i class="fa-solid fa-rotate-right"></i>
                </button>
                <button class="btn-icon-top" title="Perfil">
                    <i class="fa-solid fa-circle-user"></i>
                </button>
            </div>
        </header>

        <div class="content-area">
            
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-icon-wrapper">
                        <i class="fa-solid fa-display"></i>
                    </div>
                    <div class="kpi-info">
                        <h3><?= $total_tvs ?></h3>
                        <p>TVs Cadastradas</p>
                    </div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon-wrapper">
                        <i class="fa-solid fa-images"></i>
                    </div>
                    <div class="kpi-info">
                        <h3><?= $total_midias ?></h3>
                        <p>Mídias Ativas</p>
                    </div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon-wrapper">
                        <i class="fa-solid fa-hard-drive"></i>
                    </div>
                    <div class="kpi-info">
                        <h3><?= $tamanho_mb ?> <span style="font-size: 14px; font-weight: normal; color: var(--texto-secundario);">/ <?= $limite_gb ?> GB</span></h3>
                        <p>Armazenamento</p>
                    </div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon-wrapper green">
                        <i class="fa-solid fa-server"></i>
                    </div>
                    <div class="kpi-info">
                        <h3>Servidor</h3>
                        <p class="status-online">Sistema Online</p>
                    </div>
                </div>
            </div>

            <div class="middle-grid">
                
                <div class="panel col-tvs">
                    <div class="panel-header">
                        Status dos Terminais
                        <a href="terminais.php" class="btn-action outline" style="padding: 4px 10px; font-size: 12px; text-decoration: none;"><i class="fa-solid fa-plus"></i> Nova TV</a>
                    </div>
                    <div class="tv-list">
                        <?php if (count($ultimas_tvs) > 0): ?>
                            <?php foreach($ultimas_tvs as $tv): ?>
                                <div class="tv-item">
                                    <div class="tv-info-wrapper">
                                        <div class="tv-thumb">
                                            <i class="fa-solid fa-tv"></i>
                                        </div>
                                        <div class="tv-text">
                                            <h4><?= htmlspecialchars($tv['nome']) ?></h4>
                                            <p><i class="fa-regular fa-clock"></i> Cód: <?= htmlspecialchars($tv['chave_acesso']) ?></p>
                                        </div>
                                    </div>
                                    <div class="tv-actions">
                                        <a href="playlists.php?tv=<?= $tv['id'] ?>" class="btn-action" style="text-decoration: none;"><i class="fa-solid fa-sliders"></i> Gerenciar</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color: var(--texto-secundario); text-align: center; padding: 20px;">Nenhuma TV cadastrada ainda.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="panel col-anuncios">
                    <div class="panel-header">Últimos Uploads</div>
                    <div class="ad-list">
                        <?php if (count($ultimos_anuncios) > 0): ?>
                            <?php foreach($ultimos_anuncios as $ad): ?>
                                <div class="ad-item">
                                    <?php if ($ad['tipo'] === 'imagem'): ?>
                                        <img src="../uploads/<?= htmlspecialchars($ad['nome_arquivo']) ?>" class="ad-thumb" alt="Ad">
                                    <?php else: ?>
                                        <div class="ad-thumb"><i class="fa-solid fa-film"></i></div>
                                    <?php endif; ?>
                                    
                                    <div class="ad-text">
                                        <h4><?= htmlspecialchars($ad['nome_arquivo']) ?></h4>
                                        <p><?= date('d/m/Y', strtotime($ad['criado_em'])) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color: var(--texto-secundario); text-align: center; font-size: 13px;">Nenhum anúncio enviado.</p>
                        <?php endif; ?>
                    </div>
                    <a href="playlists.php" class="btn-add-full"><i class="fa-solid fa-cloud-arrow-up"></i> Fazer Upload de Mídia</a>
                </div>

            </div>

            <div class="panel chart-panel">
                <div class="panel-header">
                    Desempenho de Exibições
                    <span style="font-size: 13px; font-weight: 500; color: var(--texto-secundario); background: var(--bg-body); padding: 5px 12px; border-radius: 20px; border: 1px solid var(--borda-suave);"><i class="fa-regular fa-calendar"></i> Últimos 7 dias</span>
                </div>
                <div class="chart-container">
                    <canvas id="meuGrafico"></canvas>
                </div>
            </div>

        </div>
    </main>

    <script>
        const ctx = document.getElementById('meuGrafico').getContext('2d');
        
        let gradient = ctx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, 'rgba(37, 99, 235, 0.25)'); 
        gradient.addColorStop(1, 'rgba(37, 99, 235, 0.0)'); 

        const meuGrafico = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Dia 5', 'Dia 10', 'Dia 15', 'Dia 20', 'Dia 25', 'Dia 30'],
                datasets: [{
                    label: 'Inserções na Tela',
                    data: [120, 190, 150, 280, 210, 350], 
                    borderColor: '#2563eb', 
                    backgroundColor: gradient,
                    borderWidth: 3,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#2563eb',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: true, 
                    tension: 0.4 
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleFont: { size: 13 },
                        bodyFont: { size: 14, weight: 'bold' },
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: false
                    }
                },
                scales: {
                    y: { 
                        beginAtZero: true, 
                        grid: { color: '#e2e8f0', drawBorder: false }, 
                        ticks: { color: '#64748b', font: { size: 11 } }
                    }, 
                    x: { 
                        grid: { display: false }, 
                        ticks: { color: '#64748b', font: { size: 12 } }
                    } 
                },
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
            }
        });
    </script>
</body>
</html>