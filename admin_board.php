<?php
session_start();
require 'func/db.php';

$dsn = 'mysql:host=localhost;dbname=php_laba3;charset=utf8';
$username = 'root';
$password = '';

try {
    $pdo = new PDO($dsn, $username, $password);
} catch (PDOException $e) {
    $_SESSION['db_error'] = 'Ошибка подключения к базе данных: ' . htmlspecialchars($e->getMessage());
    header("Location: logout.php");
    exit();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: logout.php");
    exit();
}

try {
    $stmt = $pdo->query("SELECT is_active FROM smart_search LIMIT 1");
    $smartSearch = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$smartSearch) {
        $pdo->exec("INSERT INTO smart_search (is_active) VALUES (0)");
        $isActive = 0;
    } else {
        $isActive = $smartSearch['is_active'];
    }
} catch (PDOException $e) {
    $_SESSION['db_error'] = 'Ошибка при получении состояния умного поиска: ' . htmlspecialchars($e->getMessage());
    header("Location: logout.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newState = isset($_POST['activate']) ? 1 : 0;

    try {
        $stmt = $pdo->prepare("UPDATE smart_search SET is_active = ? WHERE id = 1");
        $stmt->execute([$newState]);
        $_SESSION['success'] = "Умный поиск " . ($newState ? "включен" : "выключен") . ".";
    } catch (PDOException $e) {
        $_SESSION['db_error'] = 'Ошибка при обновлении состояния умного поиска: ' . htmlspecialchars($e->getMessage());
    }

    header("Location: admin_board.php");
    exit();
}
?>

<?php require 'common/header.php'; ?>

<main style="padding: 20px;">
    <h2>Настройки умного поиска</h2>

    <?php if (isset($_SESSION['db_error'])): ?>
        <div class="notification error">
            <?= htmlspecialchars($_SESSION['db_error']) ?>
        </div>
        <?php unset($_SESSION['db_error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="notification success">
            <?= htmlspecialchars($_SESSION['success']) ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <p>Умный поиск в данный момент <?= $isActive ? "включен" : "выключен" ?>.</p>

    <form method="post">
        <?php if ($isActive): ?>
            <button type="submit" name="deactivate" value="0">Выключить умный поиск</button>
        <?php else: ?>
            <button type="submit" name="activate" value="1">Включить умный поиск</button>
        <?php endif; ?>
    </form>
</main>

<style>
.notification {
    padding: 10px;
    margin-bottom: 20px;
}
.error {
    background-color: red;
    color: white;
}
.success {
    background-color: green;
    color: white;
}
</style>

<?php require 'common/footer.php'; ?>