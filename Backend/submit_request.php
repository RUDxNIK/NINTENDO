<?php // N1ntendo
session_start();
include 'db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    $user_id = $_SESSION['user_id'] ?? null;

    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Все поля обязательны']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Неверный формат email']);
        exit;
    }

    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'Пожалуйста, войдите в систему']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO requests (user_id, name, email, subject, message) VALUES (?, ?, ?, ?, ?)");
    try {
        $stmt->execute([$user_id, $name, $email, $subject, $message]);

        $to = 'support@n1ntendo.ru';
        $email_subject = "Новая заявка: " . htmlspecialchars($subject);
        $email_body = "Имя: " . htmlspecialchars($name) . "\n" .
                      "Email: " . htmlspecialchars($email) . "\n" .
                      "Тема: " . htmlspecialchars($subject) . "\n" .
                      "Сообщение: " . htmlspecialchars($message) . "\n" .
                      "Пользователь: " . htmlspecialchars($_SESSION['username']) . " (ID: $user_id)";
        $headers = "From: no-reply@n1ntendo.ru\r\n" .
                   "Reply-To: " . htmlspecialchars($email) . "\n" .
                   "Content-Type: text/plain; charset=UTF-8\r\n";

        if (mail($to, $email_subject, $email_body, $headers)) {
            echo json_encode(['success' => true, 'message' => 'Заявка успешно отправлена']);
        } else {
            echo json_encode(['success' => true, 'message' => 'Заявка сохранена, но не удалось отправить email']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
}
?>