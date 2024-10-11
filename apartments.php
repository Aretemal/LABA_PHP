<?php
session_start();

$dsn = 'mysql:host=localhost;dbname=php_laba;charset=utf8';
$username = 'root';
$password = '';

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

$searchTerm = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
    $searchTerm = $_POST['searchTerm'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_save'])) {
    $id = $_POST['id'];
    $name = trim($_POST['name']);
    $price = $_POST['price'];
    $description = trim($_POST['description']);
    $location = trim($_POST['location']);
    $rooms = $_POST['rooms'];
    $area = $_POST['area'];
    $available = isset($_POST['available']) ? 1 : 0;


    if (!is_numeric($price) || $price < 0) {
        $_SESSION['error'] = "Цена должна быть положительным числом.";
    } elseif (strlen($location) <= 4 
        || strlen($name) <= 4
        || strlen($description) <= 4
        || strlen($description) >= 256
        || strlen($location) >= 256
        || strlen($name) >= 256
    ) {
        $_SESSION['error'] = "Адрес, описание и имя должны содержать более 4 символов и менее 256 (пробелы не считаются).";
    } elseif (!is_numeric($rooms) || $rooms < 0) {
        $_SESSION['error'] = "Количество комнат должно быть положительным числом.";
    } elseif (!is_numeric($area) || $area < 0) {
        $_SESSION['error'] = "Площадь должна быть положительным числом.";
    } else {
        try {
            $stmt = $pdo->prepare("CALL AddApartment(?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $price, $description, $location, $rooms, $area, $available]);
            // $stmt = $pdo->prepare("UPDATE apartments SET name = ?, price = ?, description = ?, location = ?, rooms = ?, area = ?, available = ? WHERE id = ?");
            // $stmt->execute([$name, $price, $description, $location, $rooms, $area, $available, $id]);
            $_SESSION['success'] = "Квартира успешно обновлена.";
        } catch (PDOException $e) {
            $_SESSION['db_error'] = 'Ошибка при выполнении запроса: ' . $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {
    $name = trim($_POST['name']);
    $price = $_POST['price'];
    $description = trim($_POST['description']);
    $location = trim($_POST['location']);
    $rooms = $_POST['rooms'];
    $area = $_POST['area'];
    $available = isset($_POST['available']) ? 1 : 0;

    if (!is_numeric($price) || $price < 0) {
        $_SESSION['db_error'] = "Цена должна быть положительным числом.";
    } elseif (strlen($location) <= 4 
    || strlen($name) <= 4
    || strlen($description) <= 4
    || strlen($description) >= 256
    || strlen($location) >= 256
    || strlen($name) >= 256
) {
    $_SESSION['error'] = "Адрес, описание и имя должны содержать более 4 символов и менее 256 (пробелы не считаются).";
} elseif (!is_numeric($rooms) || $rooms < 0) {
        $_SESSION['db_error'] = "Количество комнат должно быть положительным числом.";
    } elseif (!is_numeric($area) || $area < 0) {
        $_SESSION['db_error'] = "Площадь должна быть положительным числом.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO apartments (name, price, description, location, rooms, area, available) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $price, $description, $location, $rooms, $area, $available]);
            $_SESSION['success'] = "Квартира успешно добавлена.";
        } catch (PDOException $e) {
            $_SESSION['db_error'] = 'Ошибка при выполнении запроса: ' . $e->getMessage();
        }
    }
}
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM apartments WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success'] = "Квартира успешно удалена.";
    } catch (PDOException $e) {
        $_SESSION['db_error'] = 'Ошибка при выполнении запроса: ' . $e->getMessage();
    }
    header("Location: apartments.php");
    exit();
}

$query = "SELECT * FROM apartments";
$params = [];

if ($searchTerm) {
    $query .= " WHERE name LIKE ? OR description LIKE ? OR location LIKE ?";
    $params[] = "%$searchTerm%";
    $params[] = "%$searchTerm%";
    $params[] = "%$searchTerm%";
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$apartments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// $query = "SELECT * FROM apartments";
// $stmt = $pdo->query($query);
// $apartments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php require 'common/header.php'; ?>

<main style="padding: 20px;">
    <h2>Квартиры</h2>

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
    <form method="post" style="margin-bottom: 20px;">
        <input type="text" name="searchTerm" value="<?= htmlspecialchars($searchTerm) ?>" placeholder="Поиск по названию, описанию и локации...">
        <button type="submit" name="search">Поиск</button>
    </form>

    <h3>Список квартир</h3>
    <table>
        <tr>
            <th>ID</th>
            <th>Название</th>
            <th>Цена</th>
            <th>Описание</th>
            <th>Местоположение</th>
            <th>Комнаты</th>
            <th>Площадь</th>
            <th>Доступна</th>
        </tr>
        <?php foreach ($apartments as $apartment): ?>
            <tr>
                <td><?= htmlspecialchars($apartment['id']) ?></td>
                <td><?= htmlspecialchars($apartment['name']) ?></td>
                <td><?= htmlspecialchars($apartment['price']) ?></td>
                <td><?= htmlspecialchars($apartment['description']) ?></td>
                <td><?= htmlspecialchars($apartment['location']) ?></td>
                <td><?= htmlspecialchars($apartment['rooms']) ?></td>
                <td><?= htmlspecialchars($apartment['area']) ?></td>
                <td><?= htmlspecialchars($apartment['available'] ? 'Да' : 'Нет') ?></td>
                <td>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="id" value="<?= $apartment['id'] ?>">
                        <button type="submit" name="edit" formaction="apartments.php?id=<?= $apartment['id'] ?>">Редактировать</button>
                    </form>
                    <form method="get" style="display: inline;">
                        <input type="hidden" name="delete" value="<?= $apartment['id'] ?>">
                        <button type="submit" onclick="return confirm('Вы уверены, что хотите удалить эту запись?');">Удалить</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <?php if (isset($_GET['id'])): ?>
        <?php
        $id = $_GET['id'];
        $stmt = $pdo->prepare("SELECT * FROM apartments WHERE id = ?");
        $stmt->execute([$id]);
        $apartment = $stmt->fetch(PDO::FETCH_ASSOC);
        ?>
        <div>
            <h3>Редактировать квартиру</h3>
            <form method="post">
                <input type="hidden" name="id" value="<?= $apartment['id'] ?>">
                <label for="name">Название:</label>
                <input type="text" name="name" value="<?= htmlspecialchars($apartment['name']) ?>" required>
                
                <label for="price">Цена:</label>
                <input type="number" name="price" step="0.01" value="<?= htmlspecialchars($apartment['price']) ?>" required>
                
                <label for="description">Описание:</label>
                <textarea name="description" required><?= htmlspecialchars($apartment['description']) ?></textarea>
                
                <label for="location">Местоположение:</label>
                <input type="text" name="location" value="<?= htmlspecialchars($apartment['location']) ?>" required>
                
                <label for="rooms">Количество комнат:</label>
                <input type="number" name="rooms" value="<?= htmlspecialchars($apartment['rooms']) ?>" required>
                
                <label for="area">Площадь (кв. м):</label>
                <input type="number" name="area" step="0.01" value="<?= htmlspecialchars($apartment['area']) ?>" required>
                
                <label for="available">Доступна: <input type="checkbox" name="available" <?= $apartment['available'] ? 'checked' : '' ?>> </label>

                <button type="submit" name="edit_save">Сохранить изменения</button>
                <a href="apartments.php">Отмена</a>
            </form>
        </div>
    <?php endif; ?>

    <form method="post">
        <h3>Добавить квартиру</h3>
        <label for="name">Название:</label>
        <input type="text" name="name" required>
        
        <label for="price">Цена:</label>
        <input type="number" name="price" step="0.01" required>
        
        <label for="description">Описание:</label>
        <textarea name="description" required></textarea>
        
        <label for="location">Местоположение:</label>
        <input type="text" name="location" required>
        
        <label for="rooms">Количество комнат:</label>
        <input type="number" name="rooms" required>
        
        <label for="area">Площадь (кв. м):</label>
        <input type="number" name="area" step="0.01" required>
        
        <label for="available">Доступна: <input type="checkbox" name="available" checked> </label>

        <button type="submit" name="create">Добавить квартиру</button>
    </form>
</main>

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

<?php require 'common/footer.php'; ?>