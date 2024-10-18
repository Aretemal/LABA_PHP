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

if (!isset($_SESSION['user_id'] )) {
    header("Location: login.php");
    exit();
}

$params = [];
$query = "
    SELECT applications.id, apartments.name AS apartment_name, users.name AS user_name, applications.status
    FROM applications
    JOIN apartments ON applications.apartmentID = apartments.id
    JOIN users ON applications.userID = users.id
    WHERE 1=1";

if ($isLandlord || $isAdmin) {
    $landlordID = $_SESSION['user_id'];
    $query .= " AND apartments.landlordID = ?";
    $params[] = $landlordID;
}

if ($isClient) {
    $userID = $_SESSION['user_id'];
    $query .= " AND applications.userID = ?";
    $params[] = $userID;
}

$stmt = $pdo->prepare($query);

$stmt->execute($params);

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
                    <?php if ($isLandlord || $isAdmin): ?>
                        <th>Действия</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($applications as $application): ?>
                    <tr>
                        <td><?= htmlspecialchars($application['apartment_name']) ?></td>
                        <td><?= htmlspecialchars($application['user_name']) ?></td>
                        <td><?= htmlspecialchars($application['status']) ?></td>
                        <td>
                            <a href="chat.php?applicationID=<?php echo $application['id']; ?>" class="btn">Чат</a>
                        </td>
                        <?php if ($isLandlord || $isAdmin): ?>
                            <td>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="application_id" value="<?= $application['id'] ?>">
                                    <select name="status">
                                        <option value="in progress" <?= $application['status'] === 'in progress' ? 'selected' : '' ?>>Ожидает</option>
                                        <option value="agreed" <?= $application['status'] === 'agreed' ? 'selected' : '' ?>>Согласовано</option>
                                        <option value="rejected" <?= $application['status'] === 'rejected' ? 'selected' : '' ?>>Отклонено</option>
                                    </select>
                                    <button type="submit">Обновить статус</button>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>