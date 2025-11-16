<?php
session_start();
//$users = require __DIR__ . "/users.php";
$users = require "users.php";

$error = "";

if (!empty($_POST['login']) && !empty($_POST['password'])) {

    $login = $_POST['login'];
    $password = $_POST['password'];

    if (isset($users[$login]) && password_verify($password, $users[$login])) {

        // Успешный вход → создаём сессию
        $_SESSION['auth_user'] = $login;

        // Remember Me
        if (!empty($_POST['remember'])) {

            $token = bin2hex(random_bytes(32));
            file_put_contents(__DIR__."/remember_tokens/$token", $login);

            setcookie("remember_token", $token, time() + 86400*30, "/", "", false, true);
        }

        header("Location: ./index.php");
        exit;
    }

    $error = "Неверный логин или пароль";
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<meta charset="UTF-8">
<title>Вход</title>

</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-4">

            <div class="card shadow">
                <div class="card-body">
                    <h4 class="card-title text-center mb-4">Авторизация</h4>

                    <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form method="post">

                        <div class="mb-3">
                            <label class="form-label">Логин</label>
                            <input type="text" name="login" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Пароль</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" name="remember" class="form-check-input" id="remember">
                            <label class="form-check-label" for="remember">Запомнить меня</label>
                        </div>

                        <button class="btn btn-primary w-100">Войти</button>
                    </form>

                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>
