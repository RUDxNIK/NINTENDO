<?php // N1ntendo
session_start();
include 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Пожалуйста, войдите в систему']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
    exit;
}

$booking_id = $_POST['booking_id'] ?? '';
$user_id = $_SESSION['user_id'];

if (empty($booking_id)) {
    echo json_encode(['success' => false, 'message' => 'ID бронирования не указан']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT booking_id, computer_id, booking_date, booking_time, phone_number, username FROM bookings WHERE booking_id = ? AND user_id = ?");
    $stmt->execute([$booking_id, $user_id]);
    $booking = $stmt->fetch();

    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Бронирование не найдено или не принадлежит вам']);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM bookings WHERE booking_id = ?");
    $stmt->execute([$booking_id]);

    $message = "❌ *Бронирование отменено*\n\n" .
               "ID бронирования: {$booking['booking_id']}\n" .
               "Компьютер: PC{$booking['computer_id']}\n" .
               "Дата: " . date('d.m.Y', strtotime($booking['booking_date'])) . "\n" .
               "Время: {$booking['booking_time']}:00 - " . ($booking['booking_time'] + 1) . ":00\n" .
               "Пользователь: " . htmlspecialchars($booking['username'] ?? '@unknown') . "\n" .
               "Телефон: {$booking['phone_number']}\n" .
               "Отменено пользователем: " . htmlspecialchars($_SESSION['username']);

    $stmt = $pdo->query("SELECT bot_token, admin_chat_id FROM telegram_bot_config LIMIT 1");
    $config = $stmt->fetch();

    if ($config && $config['bot_token'] && $config['admin_chat_id']) {
        $bot_token = $config['bot_token'];
        $chat_id = $config['admin_chat_id'];
        
        $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
        $data = [
            'chat_id' => $chat_id,
            'text' => $message,
            'parse_mode' => 'Markdown'
        ];

        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            ]
        ];
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        if ($result === false) {
            error_log("Ошибка отправки уведомления в Telegram");
        }
    } else {
        error_log("Конфигурация Telegram бота не найдена");
    }

    echo json_encode(['success' => true, 'message' => 'Бронирование успешно отменено']);
} catch (PDOException $e) {
    error_log("Ошибка при отмене бронирования: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()]);
}
?>