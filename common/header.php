<!-- common/header.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/index.css">
    <title>Квартиры</title>
</head>
<body>
<header>
    <h1>Моя Система Управления Квартирами</h1>
    <nav>
        <ul>
            <li><a href="apartments.php">Квартиры</a></li>
            <li><a href="users.php">Пользователи</a></li>
            <?php if (isset($_SESSION['user_id'])): ?>
                <li><span>Добро пожаловать<?php if (isset($_SESSION['username'])): ?>, <?= htmlspecialchars($_SESSION['username']) ?><?php endif; ?>!</span></li>
                <li><a href="logout.php" class="logout">Выйти</a></li>
            <?php else: ?>
                <li><a href="register.php">Регистрация</a></li>
                <li><a href="login.php">Логин</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <?php if (isset($_SESSION['db_error'])): ?>
        <div class="notification error">
            <?= htmlspecialchars($_SESSION['db_error']) ?>
        </div>
        <?php unset($_SESSION['db_error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="notification error">
            <span>
                <?= htmlspecialchars($_SESSION['error']) ?>
            </span>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="notification success">
            <?= htmlspecialchars($_SESSION['success']) ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
</header>

<style>
.notification {
    position: absolute;
    top: 10px;
    right: 10px;
    padding: 10px;
    border-radius: 5px;
    z-index: 1000;
    animation: fadeInOut 5s forwards;
}

.error {
    background-color: red;
    color: white;
}

.success {
    background-color: green;
    color: white;
}

@keyframes fadeInOut {
    0% {
        opacity: 0;
        transform: translateY(-20px);
    }
    20% {
        opacity: 1;
        transform: translateY(0);
    }
    80% {
        opacity: 1;
    }
    100% {
        opacity: 0;
        transform: translateY(-20px);
    }
}
</style>
