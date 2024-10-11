<?php
session_start();
require 'func/db.php'; 
require 'func/login_user.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    try {
        $pdo = getPDO();
        $user = authenticateUser($username, $password, $pdo);

        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header("Location: apartments.php");
            exit();
        } else {
            $_SESSION['error'] = "Неверный логин или пароль.";
        }
        
    } catch (PDOException $e) {
        $_SESSION['error'] = 'База данных недоступна. Пожалуйста, попробуйте позже.' . $e->getMessage();
    }
}
?>

<?php require 'common/header.php'; ?>

<main style="padding: 20px;">
    <h2>Вход</h2>

    <?php if (isset($error) && $error): ?>
        <div class="notification error">
            <span>
                <?= htmlspecialchars($error) ?>
            </span>
        </div>
        <?php unset($error); ?>
    <?php endif; ?>

    <form method="post">
        <label for="username">Логин:</label>
        <input type="text" name="username" required>
        
        <label for="password">Пароль:</label>
        <input type="password" name="password" required>
        
        <label for="submit"></label>
        <button type="submit" name="login">Войти</button>
    </form>
</main>

<?php require 'common/footer.php'; ?>