<?php
session_start();
session_unset();
// Oturumu tamamen kapat
session_destroy();
// Güvenlik açısından çerezleri de temizle (özellikle session çerezi)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
header("Location: /index.php");
exit;
