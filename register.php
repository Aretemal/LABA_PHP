<?php
session_start();
require 'common/header.php';
require 'src/db/pdo.php';
require 'src/users/users.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getPDO(); 
        list($errors, $userData) = validateUserData($_POST); 

        if (!empty($errors)) {
            $error = implode(' ', $errors);
            $_SESSION['error'] = $error;
            header("Location: register.php");
        } elseif (isUserExists($pdo, $userData['username'])) {
            $error = "Пользователь с таким логином уже существует.";
            $_SESSION['error'] = $error;
            header("Location: register.php");
        } else {
            $userId = createUser(
                $pdo, $userData['name'], 
                $userData['username'], 
                $userData['password'],
                $userData['age'],
                $userData['gender'], 
                $userData['role']
            );  
            
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $userData['username'];
            $_SESSION['role'] = $userData['role'];

            header("Location: apartments.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = 'База данных недоступна. Пожалуйста, попробуйте позже.' . $e->getMessage();
        header("Location: register.php");
    }
}
?>

<main style="padding: 20px;">
    
    <?php if (isset($error)): ?>
        <p style="color:red;"><?= $error ?></p>
    <?php endif; ?>

    <?php if (isset($error) && $error): ?>
        <div class="notification error">
            <span>
                <?= htmlspecialchars($error) ?>
            </span>
        </div>
        <?php unset($error); ?>
    <?php endif; ?>
    
    <form method="post" style="border: 2px solid black;">
        <h2 style="margin-bottom: 10px">Регистрация</h2>
        <label for="name">Имя:</label>
        <input type="text" name="name" required>
        
        <label for="username">Логин:</label>
        <input type="text" name="username" required>
        
        <label for="password">Пароль:</label>
        <input type="password" name="password" required>
        
        <label for="age">Возраст:</label>
        <input type="number" name="age" required>
        
        <label for="gender">Пол:
            <select name="gender" required>
                <option value="male">Мужской</option>
                <option value="female">Женский</option>
                <option value="other">Другой</option>
            </select>
        </label>

        <label for="role">Роль:
            <select name="role" required>
                <option value="landlord">Арендодатель</option>
                <option value="client">Клиент</option>
                <option value="admin">Администратор</option>
            </select>
        </label>
        
        <label for="submit"></label>
        <button type="submit">Зарегистрироваться</button>
    </form>
</main>

<?php
require 'common/footer.php';