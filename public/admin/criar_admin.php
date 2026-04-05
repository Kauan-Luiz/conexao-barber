<?php
// Caminho para o seu arquivo de conexão com o banco
require_once '../../core/db.php';

echo "<div style='font-family: sans-serif; padding: 20px;'>";
echo "<h2>Instalador de Usuário - WebLab Signage</h2>";

$nome = 'Kauan Admin';
$email = 'admin@weblab.com';
$senha_limpa = '123456';

// Verifica se a tabela usuarios existe
try {
    // 1. Verifica se o e-mail já existe para não duplicar
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        echo "<p style='color: #d97706; font-weight: bold;'>⚠️ O usuário <b>$email</b> já existe no banco de dados!</p>";
        echo "<p>Se a senha não está funcionando, vá no seu banco de dados, apague a linha desse usuário e rode esta página novamente.</p>";
    } else {
        // 2. A MÁGICA: Pede para o próprio PHP gerar o Hash perfeito
        $senha_criptografada = password_hash($senha_limpa, PASSWORD_DEFAULT);
        
        // 3. Insere no banco
        $sql = "INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$nome, $email, $senha_criptografada])) {
            echo "<p style='color: #16a34a; font-weight: bold;'>✅ Sucesso! O usuário foi criado perfeitamente.</p>";
            echo "<ul>";
            echo "<li><b>E-mail:</b> $email</li>";
            echo "<li><b>Senha:</b> $senha_limpa</li>";
            echo "</ul>";
            echo "<br><a href='login.php' style='background: #2563eb; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ir para o Login</a>";
        } else {
            echo "<p style='color: #dc2626;'>❌ Erro ao tentar inserir no banco de dados.</p>";
        }
    }
} catch (PDOException $e) {
    echo "<p style='color: #dc2626; font-weight: bold;'>❌ Erro de Banco de Dados:</p>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p>Verifique se as tabelas já foram criadas e se o seu <b>core/db.php</b> está com a senha certa do MySQL do seu notebook.</p>";
}

echo "</div>";
?>