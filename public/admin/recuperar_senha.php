<?php
session_start();
if (isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

require_once '../../core/db.php';

// Para o envio de e-mails funcionar de verdade, usaremos o PHPMailer.
// (Explicarei como baixar ele logo abaixo do código)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    
    if ($email) {
        // 1. Verifica se o e-mail existe no banco
        $stmt = $pdo->prepare("SELECT id, nome FROM usuarios WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if ($usuario) {
            // 2. Gera um Token super seguro de 64 caracteres
            $token = bin2hex(random_bytes(32));
            
            // 3. Define a validade para 1 hora (3600 segundos) a partir de agora
            $expira = date("Y-m-d H:i:s", time() + 3600);

            // 4. Salva o token no banco de dados
            $pdo->prepare("UPDATE usuarios SET reset_token = ?, reset_expira = ? WHERE id = ?")
                ->execute([$token, $expira, $usuario['id']]);

            // 5. Monta o Link de Recuperação (Mude o localhost para o seu domínio na VPS depois)
            $link = "http://localhost/Kauan_barbeiro/public/admin/redefinir_senha.php?token=" . $token;

            // ==========================================
            // LÓGICA DE ENVIO DO E-MAIL (PHPMailer)
            // ==========================================
            
            // Requer os arquivos do PHPMailer (você precisará baixar a pasta deles, explico abaixo)
            require '../../vendor/phpmailer/src/Exception.php';
            require '../../vendor/phpmailer/src/PHPMailer.php';
            require '../../vendor/phpmailer/src/SMTP.php';

            $mail = new PHPMailer(true);

            try {
                // Configurações do seu Servidor SMTP (Ex: HostGator, Locaweb, Gmail)
                $mail->isSMTP();
                $mail->Host       = 'smtp.seudominio.com.br'; // COLOQUE SEU HOST
                $mail->SMTPAuth   = true;
                $mail->Username   = 'nao-responda@seudominio.com.br'; // COLOQUE SEU E-MAIL
                $mail->Password   = 'SuaSenhaSuperForte'; // COLOQUE SUA SENHA
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 465;

                // Remetente e Destinatário
                $mail->setFrom('nao-responda@seudominio.com.br', 'WebLab Signage');
                $mail->addAddress($email, $usuario['nome']);

                // Conteúdo do E-mail
                $mail->isHTML(true);
                $mail->Subject = 'Recuperacao de Senha - WebLab Signage';
                $mail->Body    = "
                    <h2>Olá, {$usuario['nome']}</h2>
                    <p>Recebemos um pedido para redefinir a sua senha no painel da TV.</p>
                    <p>Clique no link abaixo para criar uma nova senha. Este link é válido por apenas 1 hora.</p>
                    <p><a href='{$link}' style='background: #2563eb; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Redefinir Minha Senha</a></p>
                    <p>Se você não pediu isso, apenas ignore este e-mail.</p>
                ";

                $mail->send();
                
                // MENSAGEM DE SUCESSO
                $mensagem = "<div class='msg sucesso'><i class='fa-solid fa-envelope-circle-check'></i> Verifique sua caixa de entrada (e o Spam). Um link de recuperação foi enviado.</div>";
            } catch (Exception $e) {
                // Se der erro no servidor de e-mail, a gente avisa
                $mensagem = "<div class='msg erro'>Erro ao tentar enviar o e-mail: {$mail->ErrorInfo}</div>";
            }

        } else {
            // REGRA SÊNIOR DE SEGURANÇA: Se o e-mail não existir, mostramos a mesma mensagem de sucesso!
            // Isso impede que hackers fiquem testando e-mails para descobrir quem tem conta no seu sistema.
            $mensagem = "<div class='msg sucesso'><i class='fa-solid fa-envelope-circle-check'></i> Verifique sua caixa de entrada (e o Spam). Um link de recuperação foi enviado.</div>";
        }
    } else {
        $mensagem = "<div class='msg erro'>Digite um e-mail válido.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - WebLab Signage</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/global.css">
    
    <style>
        /* Reutilizando a arquitetura visual da tela de login */
        body { 
            display: flex; align-items: center; justify-content: center; 
            background: linear-gradient(135deg, var(--bg-sidebar) 0%, #1e293b 100%);
            min-height: 100vh; margin: 0; padding: 20px;
        }
        .login-container {
            background: #ffffff; width: 100%; max-width: 420px; border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); overflow: hidden;
            display: flex; flex-direction: column;
        }
        .login-header { padding: 40px 30px 20px; text-align: center; }
        .login-logo { max-width: 160px; margin-bottom: 15px; }
        .login-header h2 { color: var(--texto-principal); font-size: 24px; font-weight: 700; }
        .login-header p { color: var(--texto-secundario); font-size: 14px; margin-top: 5px; }
        .login-body { padding: 0 30px 40px; }
        .form-group { margin-bottom: 25px; display: flex; flex-direction: column; }
        .form-group label { font-size: 13px; font-weight: 600; color: var(--texto-principal); margin-bottom: 8px; }
        .input-with-icon { position: relative; }
        .input-with-icon i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--texto-secundario); font-size: 16px; }
        .input-with-icon input { width: 100%; padding: 14px 15px 14px 45px; border: 1px solid var(--borda-suave); border-radius: 8px; font-size: 15px; outline: none; transition: 0.3s; background: #f8fafc; }
        .input-with-icon input:focus { border-color: var(--azul-destaque); background: #ffffff; box-shadow: 0 0 0 4px rgba(37,99,235,0.1); }
        .btn-login { background: var(--azul-destaque); color: white; border: none; padding: 14px; border-radius: 8px; font-weight: 700; font-size: 16px; cursor: pointer; transition: 0.3s; width: 100%; display: flex; justify-content: center; align-items: center; gap: 10px; }
        .btn-login:hover { background: #1d4ed8; transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(37,99,235,0.3); }
        .back-link { display: block; text-align: center; margin-top: 20px; color: var(--texto-secundario); font-size: 14px; text-decoration: none; font-weight: 600; transition: 0.2s; }
        .back-link:hover { color: var(--texto-principal); }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="login-header">
            <img src="../assets/img/logo.png" alt="WebLab Signage" class="login-logo" onerror="this.style.display='none'">
            <h2>Recuperar Senha</h2>
            <p>Enviaremos um link de acesso para o seu e-mail.</p>
        </div>

        <div class="login-body">
            <?= $mensagem ?>

            <form action="recuperar_senha.php" method="POST">
                <div class="form-group">
                    <label for="email">E-mail de Cadastro</label>
                    <div class="input-with-icon">
                        <i class="fa-regular fa-envelope"></i>
                        <input type="email" name="email" id="email" placeholder="Digite seu e-mail..." required>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    Enviar Link <i class="fa-solid fa-paper-plane"></i>
                </button>

                <a href="login.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Voltar para o Login</a>
            </form>
        </div>
    </div>

</body>
</html>