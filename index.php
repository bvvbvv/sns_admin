<?php //require __DIR__ . "auth_check.php"; 
?>
<?php require "auth_check.php"; ?>

<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Биллинг </title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="./css/sns_pay.css">
</head>
<body>

<!-- <div class="container mt-4" > -->
<div class="container-div" style="flex-direction: column" >
    <h3>Добро пожаловать, <?= htmlspecialchars($_SESSION['auth_user']) ?>!</h3>
    <?php include './get_radius_pay.php'; ?>
    <!-- <a href="/logout.php" class="btn btn-secondary mt-3">Выход</a> -->
</div>

</body>
</html>

