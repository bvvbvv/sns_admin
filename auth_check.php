<?php
// auth_check.php
session_start();

// Если есть активная сессия — OK
if (!empty($_SESSION['auth_user'])) {
    return;
}

// Если есть кука Remember Me
if (!empty($_COOKIE['remember_token'])) {

    $token = $_COOKIE['remember_token'];

    // В реальном проекте токены хранят в базе,
    // но для простоты — в файле
    //if (file_exists(__DIR__ . "/remember_tokens/$token")) {
    if (file_exists("./remember_tokens/$token")) {
        //$username = file_get_contents(__DIR__ . "/remember_tokens/$token");
        $username = file_get_contents("./remember_tokens/$token");
        $_SESSION['auth_user'] = $username;
        return;
    }
}
 
// Если авторизации нет
header("Location:./login.php");
exit;
