<?php
// Magia Sênior: Descobre o nome do arquivo atual que o usuário está acessando (ex: 'playlists.php')
$pagina_atual = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar">
    <div class="sidebar-header">
        <img src="../assets/img/logo.png" alt="Logo WebLab" class="sidebar-logo">
    </div>
    <nav class="menu">
        <a href="index.php" class="<?= ($pagina_atual == 'index.php') ? 'ativo' : '' ?>">
            <i class="fa-solid fa-chart-pie"></i> Dashboard
        </a>
        
        <a href="terminais.php" class="<?= ($pagina_atual == 'terminais.php') ? 'ativo' : '' ?>">
            <i class="fa-solid fa-tv"></i> TVs Cadastradas
        </a>
        
        <a href="playlists.php" class="<?= ($pagina_atual == 'playlists.php') ? 'ativo' : '' ?>">
            <i class="fa-solid fa-list-check"></i> Playlists & Upload
        </a>
        
        <div class="menu-footer">
            <a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sair</a>
        </div>
    </nav>
</aside>