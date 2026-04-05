<?php
session_start();

// Se já estiver logado, manda pro dashboard
if (isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

require_once '../../core/db.php';

$mensagem = '';

// ==========================================
// LÓGICA DE LOGIN & SEGURANÇA
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $senha = $_POST['senha'];
    $lembrar = isset($_POST['lembrar']) ? true : false;
    $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';

    // 1. VALIDAÇÃO DO CAPTCHA (Google reCAPTCHA v2)
    // ATENÇÃO: Substitua 'SUA_CHAVE_SECRETA_AQUI' pela chave real do Google depois
    $secret_key = "SUA_CHAVE_SECRETA_AQUI"; 
    
    // Como estamos em ambiente de testes/local, vamos pular a verificação real do Google 
    // SE a chave secreta ainda for a padrão. Quando for pra produção, você bota sua chave!
    $captcha_valido = true; 
    
    if ($secret_key !== "SUA_CHAVE_SECRETA_AQUI") {
        $verify_response = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . $secret_key . '&response=' . $recaptcha_response);
        $response_data = json_decode($verify_response);
        if (!$response_data->success) {
            $captcha_valido = false;
        }
    }

    if (!$captcha_valido) {
        $mensagem = "<div class='msg erro'><i class='fa-solid fa-robot'></i> Por favor, confirme que você não é um robô.</div>";
    } else {
        // 2. VALIDAÇÃO DE USUÁRIO E SENHA
        $stmt = $pdo->prepare("SELECT id, nome, senha FROM usuarios WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($senha, $usuario['senha'])) {
            // Login de Sucesso!
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];

            // 3. LÓGICA DO "LEMBRAR DE MIM" (Cookies)
            if ($lembrar) {
                // Salva o e-mail no computador do cliente por 30 dias
                setcookie("weblab_email_salvo", $email, time() + (86400 * 30), "/");
            } else {
                // Se ele desmarcou, a gente apaga o cookie
                setcookie("weblab_email_salvo", "", time() - 3600, "/");
            }

            header("Location: index.php");
            exit;
        } else {
            $mensagem = "<div class='msg erro'><i class='fa-solid fa-triangle-exclamation'></i> E-mail ou senha incorretos.</div>";
        }
    }
}

// Puxa o e-mail salvo no cookie (se existir) para preencher o campo sozinho
$email_salvo = isset($_COOKIE['weblab_email_salvo']) ? $_COOKIE['weblab_email_salvo'] : '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - WebLab Signage</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/global.css">
    
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>

    <style>
        /* Estilos Exclusivos da Tela de Login */
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

        .login-header {
            padding: 40px 30px 20px; text-align: center;
        }
        
        .login-logo { max-width: 160px; margin-bottom: 15px; }
        
        .login-header h2 { color: var(--texto-principal); font-size: 24px; font-weight: 700; }
        .login-header p { color: var(--texto-secundario); font-size: 14px; margin-top: 5px; }

        .login-body { padding: 0 30px 40px; }

        .form-group { margin-bottom: 20px; display: flex; flex-direction: column; }
        .form-group label { font-size: 13px; font-weight: 600; color: var(--texto-principal); margin-bottom: 8px; }
        
        .input-with-icon { position: relative; }
        .input-with-icon i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--texto-secundario); font-size: 16px; }
        .input-with-icon input { 
            width: 100%; padding: 14px 15px 14px 45px; border: 1px solid var(--borda-suave); 
            border-radius: 8px; font-size: 15px; outline: none; transition: 0.3s; background: #f8fafc;
        }
        .input-with-icon input:focus { border-color: var(--azul-destaque); background: #ffffff; box-shadow: 0 0 0 4px rgba(37,99,235,0.1); }

        .login-options { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; font-size: 13px; }
        
        /* Checkbox Customizado */
        .checkbox-container { display: flex; align-items: center; gap: 8px; cursor: pointer; color: var(--texto-secundario); font-weight: 500; }
        .checkbox-container input { cursor: pointer; width: 16px; height: 16px; accent-color: var(--azul-destaque); }
        
        .forgot-password { color: var(--azul-destaque); text-decoration: none; font-weight: 600; transition: 0.2s; }
        .forgot-password:hover { text-decoration: underline; color: #1d4ed8; }

        /* Container do Captcha */
        .captcha-container { margin-bottom: 25px; display: flex; justify-content: center; }

        .btn-login { 
            background: var(--azul-destaque); color: white; border: none; padding: 14px; 
            border-radius: 8px; font-weight: 700; font-size: 16px; cursor: pointer; 
            transition: 0.3s; width: 100%; display: flex; justify-content: center; align-items: center; gap: 10px;
        }
        .btn-login:hover { background: #1d4ed8; transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(37,99,235,0.3); }

        /* Mensagens de erro ajustadas para o login */
        .msg { padding: 12px 15px; border-radius: 8px; font-size: 13px; font-weight: 600; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="login-header">
            <img src="../assets/img/logo.png" alt="WebLab Signage" class="login-logo" onerror="this.style.display='none'">
            <h2>Bem-vindo de volta</h2>
            <p>Acesse seu painel de controle</p>
        </div>

        <div class="login-body">
            <?= $mensagem ?>

            <form action="login.php" method="POST">
                <div class="form-group">
                    <label for="email">E-mail</label>
                    <div class="input-with-icon">
                        <i class="fa-regular fa-envelope"></i>
                        <input type="email" name="email" id="email" placeholder="admin@weblab.com" value="<?= htmlspecialchars($email_salvo) ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="senha">Senha</label>
                    <div class="input-with-icon">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" name="senha" id="senha" placeholder="••••••••" required>
                    </div>
                </div>

                <div class="login-options">
                    <label class="checkbox-container">
                        <input type="checkbox" name="lembrar" <?= $email_salvo ? 'checked' : '' ?>>
                        Lembrar de mim
                    </label>
                    <a href="recuperar_senha.php" class="forgot-password">Esqueci minha senha</a>
                </div>

                <div class="captcha-container">
                    <div class="g-recaptcha" data-sitekey="SUA_SITE_KEY_AQUI"></div>
                </div>

                <button type="submit" class="btn-login">
                    Entrar no Sistema <i class="fa-solid fa-arrow-right-to-bracket"></i>
                </button>
            </form>
        </div>
    </div>

</body>
</html>