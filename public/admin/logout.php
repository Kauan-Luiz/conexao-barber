<?php
session_start();
// Apaga todas as variáveis da sessão
$_SESSION = array();
// Destrói a sessão no servidor
session_destroy();
// Manda pro login
header("Location: login.php");
exit;