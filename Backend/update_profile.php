<?php // N1ntendo
session_start();
include 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Пожалуйста, войдите в систему']);
    exit;
}

$user_id = $_SESSION['user_id'];
$response = ['success' => false, 'message' => ''];

try {
    $nickname = isset($_POST['nickname']) ? trim($_POST['nickname']) : $_SESSION['nickname'];
    $avatar_path = $_SESSION['avatar'] ?? 'uploads/default_avatar.png';

    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        if (!is_writable($upload_dir)) {
            throw new Exception('Директория uploads/ не доступна для записи');
        }

        $file = $_FILES['avatar'];
        $file_name = $file['name'];
        $file_tmp = $file['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($file_ext, $allowed_ext)) {
            throw new Exception('Недопустимый формат файла. Разрешены: jpg, jpeg, png, gif');
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception('Файл слишком большой. Максимальный размер: 5MB');
        }

        $new_file_name = 'avatar_' . $user_id . '_' . time() . '.' . $file_ext;
        $destination = $upload_dir . $new_file_name;

        if (!move_uploaded_file($file_tmp, $destination)) {
            throw new Exception('Не удалось загрузить файл');
        }

        $avatar_path = $destination;
    }

    $stmt = $pdo->prepare("UPDATE users SET nickname = ?, avatar = ? WHERE id = ?");
    $stmt->execute([$nickname, $avatar_path, $user_id]);

    $_SESSION['nickname'] = $nickname;
    $_SESSION['avatar'] = $avatar_path;

    $response = ['success' => true, 'message' => 'Профиль успешно обновлен', 'avatar' => $avatar_path];
} catch (Exception $e) {
    $response = ['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()];
}

echo json_encode($response);
?>