<?php
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Валидация
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        echo json_encode(['success' => false, 'message' => 'Все поля обязательны']);
        exit;
    }
    if (strlen($username) < 3 || strlen($username) > 50) {
        echo json_encode(['success' => false, 'message' => 'Имя пользователя должно быть от 3 до 50 символов']);
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Неверный формат email']);
        exit;
    }
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Пароль должен быть не менее 6 символов']);
        exit;
    }
    if ($password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'Пароли не совпадают']);
        exit;
    }

    // Проверка уникальности
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Имя пользователя уже занято']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Email уже зарегистрирован']);
        exit;
    }

    // Хеширование пароля и получение IP
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $ip_address = $_SERVER['REMOTE_ADDR'];

    // Добавление пользователя
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
    try {
        $stmt->execute([$username, $email, $hashed_password, $ip_address]);
        echo json_encode(['success' => true, 'message' => 'Регистрация успешна']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Ошибка при регистрации: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
}
?>