<?php
session_start();

$dsn = 'mysql:host=localhost;dbname=php_laba2;charset=utf8';
$username = 'root';
$password = '';

require 'common/header.php';

$isLandlord = $_SESSION['role'] === 'landlord';
$isClient = $_SESSION['role'] === 'client';
$isAdmin = $_SESSION['role'] === 'admin';

$userID = $_SESSION['user_id'];
try {
    $pdo = new PDO($dsn, $username, $password);
} catch (PDOException $e) {
    $_SESSION['db_error'] = 'Ошибка подключения к базе данных: ' . $e->getMessage();
}

if (!$pdo) {
    $_SESSION['db_error'] = 'База данных недоступна. Пожалуйста, попробуйте позже.'  . $e->getMessage();
    unset($_SESSION['user_id']);
    unset($_SESSION['username']);
    unset($_SESSION['role']);
    header("Location: login.php");
    exit();
}

// Проверка роли пользователя (админ или арендодатель)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php'); // Перенаправление на страницу логина, если пользователь не администратор
    exit();
}

// Получаем все заявки
$stmt = $pdo->prepare("
    SELECT applications.id, apartments.name AS apartment_name, users.name AS user_name, applications.status
    FROM applications
    JOIN apartments ON applications.apartmentID = apartments.id
    JOIN users ON applications.userID = users.id
");
$stmt->execute();
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Обработка изменения статуса заявки
    if (isset($_POST['status'])) {
        $applicationID = $_POST['application_id'];
        $status = $_POST['status'];

        $stmt = $pdo->prepare("UPDATE applications SET status = ? WHERE id = ?");
        $stmt->execute([$status, $applicationID]);

        $_SESSION['success'] = "Статус заявки успешно обновлен.";
        header('Location: manage_applications.php'); // Перенаправление после обновления
        exit();
    }
}
?>

<main>
    <?php if (empty($applications)): ?>
        <p>Нет заявок для обработки.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Название квартиры</th>
                    <th>Имя пользователя</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($applications as $application): ?>
                    <tr>
                        <td><?= htmlspecialchars($application['apartment_name']) ?></td>
                        <td><?= htmlspecialchars($application['user_name']) ?></td>
                        <td><?= htmlspecialchars($application['status']) ?></td>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="application_id" value="<?= $application['id'] ?>">
                                <select name="status">
                                    <option value="pending" <?= $application['status'] === 'pending' ? 'selected' : '' ?>>Ожидает</option>
                                    <option value="agreed" <?= $application['status'] === 'agreed' ? 'selected' : '' ?>>Согласовано</option>
                                    <option value="rejected" <?= $application['status'] === 'rejected' ? 'selected' : '' ?>>Отклонено</option>
                                </select>
                                <button type="submit">Обновить статус</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>