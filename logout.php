<?php
session_start();
session_destroy();

// Удалить cookie Remember Me
if (!empty($_COOKIE['remember_token'])) {
    unlink(__DIR__."/remember_tokens/".$_COOKIE['remember_token']);
    setcookie("remember_token", "", time() - 3600, "/");
}

header("Location: /login.php");
exit;
