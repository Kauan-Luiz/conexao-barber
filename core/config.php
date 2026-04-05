<?php
// Configurações do Banco
// IMPORTANTE: Em DB_HOST, coloque o que está escrito no campo "Internal URL" lá no Coolify (provavelmente é sistema_tv ou algo parecido)
define('DB_HOST', 'sistema_tv'); 

// IMPORTANTE: Veja se o campo "Initial Database" no Coolify está como sistema_tv ou default. Tem que ser igual.
define('DB_NAME', 'sistema_tv'); 

define('DB_USER', 'root'); 

// Aqui vai a sua senha gigante do Coolify
define('DB_PASS', 'nxr4iPDx49EE5bv5jKn6FibkrslBGbbBFf5UbhSv47XmqJiyyAHJemYWECnh5VOj'); 

// Caminhos do Sistema (Depois a gente arruma isso quando o Coolify te der um link real)
define('BASE_URL', 'http://sua-vps-ip/public/');
define('UPLOAD_PATH', __DIR__ . '/../public/uploads/');
