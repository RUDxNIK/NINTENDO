<?php // N1ntendo
session_start();
include 'db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Имя пользователя и пароль обязательны']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['nickname'] = $user['nickname'];
        $_SESSION['avatar'] = $user['avatar'] ?? 'Uploads/default_avatar.png';

        error_log("После входа: user_id=" . $_SESSION['user_id'] . ", avatar=" . $_SESSION['avatar']);

        echo json_encode(['success' => true, 'message' => 'Вход успешен']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Неверное имя пользователя или пароль']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
}
?>