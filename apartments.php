<?php
session_start();

$dsn = 'mysql:host=localhost;dbname=php_laba3;charset=utf8';
$username = 'root';
$password = '';

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



$searchTerm = '';

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


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
    $searchTerm = $_POST['searchTerm'];

    if (strlen($searchTerm) > 1000) {
        $_SESSION['error'] = "Поисковый запрос не должен превышать 1000 символов.";
        $searchTerm = '';
    }
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
            // $stmt = $pdo->prepare("CALL AddApartment(?, ?, ?, ?, ?, ?, ?)");
            // $stmt->execute([$name, $price, $description, $location, $rooms, $area, $available]);
            $stmt = $pdo->prepare("UPDATE apartments SET name = ?, price = ?, description = ?, location = ?, rooms = ?, area = ?, available = ? WHERE id = ?");
            $stmt->execute([$name, $price, $description, $location, $rooms, $area, $available, $id]);
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
            $stmt = $pdo->prepare("INSERT INTO apartments (name, price, description, location, rooms, area, available, landlordID) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $price, $description, $location, $rooms, $area, $available, $_SESSION["user_id"]]);
            $_SESSION['success'] = "Квартира успешно добавлена.";
        } catch (PDOException $e) {
            $_SESSION['db_error'] = 'Ошибка при выполнении запроса: ' . $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    error_log("POST Data: " . print_r($_POST, true));
    $apartmentID = $_POST['save'];
    $userID = $_SESSION['user_id'];

    if (empty($apartmentID)) {
        $_SESSION['error'] = "ID квартиры не может быть пустым.";
    } else {
        try {
            // Проверка существующей записи
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE userID = ? AND apartmentID = ?");
            $stmt->execute([$userID, $apartmentID]);
            $exists = $stmt->fetchColumn();

            if ($exists) {
                $_SESSION['error'] = "Эта квартира уже в избранном.";
            } else {
                // Если записи нет, добавляем
                $stmt = $pdo->prepare("INSERT INTO favorites (userID, apartmentID) VALUES (?, ?)");
                $stmt->execute([$userID, $apartmentID]);
                $_SESSION['success'] = "Квартира добавлена в избранное.";
            }
        } catch (PDOException $e) {
            $_SESSION['db_error'] = 'Ошибка при выполнении запроса: ' . $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request'])) {
    error_log("POST Data: " . print_r($_POST, true));
    $apartmentID = (int)$_POST['request'];
    $userID = $_SESSION['user_id'];
    if (empty($apartmentID)) {
        $_SESSION['error'] = "ID квартиры не может быть пустым.";
        // Можно сделать редирект или другую обработку
        header("Location: apartments.php");
        exit();
    }
    try {
        $stmt = $pdo->prepare("INSERT INTO applications (userID, apartmentID) VALUES (?, ?)");
        $stmt->execute([+$userID, +$apartmentID]);
        $_SESSION['success'] = "Заявка успешно отправлена.";
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Ошибка при выполнении запроса: ' . $apartmentID . $e->getMessage();
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

$apartments = [];

$favoriteIDs = [];
$stmt = $pdo->prepare("SELECT apartmentID FROM favorites WHERE userID = ?");
$stmt->execute([$userID]);
$favoriteIDs = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

$applicationIDs = [];
$stmt = $pdo->prepare("SELECT apartmentID FROM applications WHERE userID = ?");
$stmt->execute([$userID]);
$applicationIDs = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);


if ($isActive && $isClient) {
    $params = [];
    $query = "SELECT a.*, 
       landlord_stats.favorite_count, 
       landlord_stats.application_count
FROM apartments a
JOIN (
    SELECT a.landlordID, 
                     COUNT(f.apartmentID) AS favorite_count, 
                     COUNT(app.apartmentID) AS application_count 
              FROM apartments a
              LEFT JOIN favorites f ON a.id = f.apartmentID AND f.userID = ?
              LEFT JOIN applications app ON a.id = app.apartmentID AND app.userID = ?
              WHERE 1=1"; 
    
    $params[] = $userID;
    $params[] = $userID;
    
    if ($searchTerm) {
        $query .= " AND (name LIKE ? OR description LIKE ? OR location LIKE ?)";
        $params[] = "%$searchTerm%";
        $params[] = "%$searchTerm%";
        $params[] = "%$searchTerm%";
    }
    
    if ($isActive && $isClient) {
        $query .= " GROUP BY a.landlordID) AS landlord_stats ON a.landlordID = landlord_stats.landlordID
        ORDER BY landlord_stats.favorite_count DESC, landlord_stats.application_count DESC;";
    }
    
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $apartments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $params = [];
    $query = "SELECT * FROM apartments WHERE 1=1";

    if ($isLandlord) {
        $landlordID = $_SESSION['user_id'];
        $query .= " AND landlordID = ?";
        $params[] = $landlordID;
    } 
    
    if ($searchTerm) {
        $query .= " AND (name LIKE ? OR description LIKE ? OR location LIKE ?)";
        $params[] = "%$searchTerm%";
        $params[] = "%$searchTerm%";
        $params[] = "%$searchTerm%";
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $apartments = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// $stmt = $pdo->prepare($query);
// $stmt->execute($params);
// $apartments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$favoriteIDs = [];
$stmt = $pdo->prepare("SELECT apartmentID FROM favorites WHERE userID = ?");
$stmt->execute([$userID]);
$favoriteIDs = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

$applicationIDs = [];
$stmt = $pdo->prepare("SELECT apartmentID FROM applications WHERE userID = ?");
$stmt->execute([$userID]);
$applicationIDs = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
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
    <table style="margin-bottom: 10px;">
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
                <td style="margin-bottom: 10px; margin-top: 10px; display: flex; flex-direction: column; align-items: space-between; justify-content: space-between; height: 100px;">
                    <?php if ($isLandlord || $isAdmin): ?>
                        <div>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="id" value="<?= $apartment['id'] ?>">
                                <button type="submit" name="edit" formaction="apartments.php?id=<?= $apartment['id'] ?>">Редактировать</button>
                            </form>
                            <form method="get" style="display: inline;">
                                <input type="hidden" name="delete" value="<?= $apartment['id'] ?>">
                                <button type="submit" onclick="return confirm('Вы уверены, что хотите удалить эту запись?');">Удалить</button>
                            </form>
                        </div>
                    <?php endif; ?>
                    <?php if ($isClient || $isAdmin): ?>
                        <div>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="save" value="<?= $apartment['id'] ?>">
                            <?php if (!in_array($apartment['id'], $favoriteIDs)): ?>
                                    <button type="submit">Добавить в избранное</button>
                            <?php else: ?>
                                <span>В избранном</span>
                            <?php endif; ?>
                        </form>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="request" value="<?= $apartment['id'] ?>">
                            <?php if (!in_array($apartment['id'], $applicationIDs)): ?>
                                <button type="submit">Оставить заявку</button>
                            <?php else: ?>
                                <span>Заявка отправлена</span>
                            <?php endif; ?>
                        </form>
                        </div>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <?php if (isset($_GET['id']) && ($isLandlord || $isAdmin)): ?>
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

    <?php if ($isLandlord || $isAdmin): ?>
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
    <?php endif; ?>
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