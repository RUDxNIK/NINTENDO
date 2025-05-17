<?php // N1ntendo
session_start();
include 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Пожалуйста, войдите в систему']);
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$email = $_SESSION['email'];

$stmt = $pdo->prepare("SELECT telegram_code, is_linked, telegram_username FROM telegram_links WHERE user_id = ?");
$stmt->execute([$user_id]);
$existing = $stmt->fetch();

if ($existing) {
    echo json_encode([
        'success' => true,
        'telegram_code' => $existing['telegram_code'],
        'is_linked' => $existing['is_linked'],
        'telegram_username' => $existing['telegram_username']
    ]);
    exit;
}

$telegram_code = bin2hex(random_bytes(11));

$stmt = $pdo->prepare("INSERT INTO telegram_links (user_id, username, email, telegram_code) VALUES (?, ?, ?, ?)");
try {
    $stmt->execute([$user_id, $username, $email, $telegram_code]);
    echo json_encode(['success' => true, 'telegram_code' => $telegram_code, 'is_linked' => false]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()]);
}
?>