<?php // N1ntendo
session_start();
include 'db_connect.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$email = $_SESSION['email'];
$nickname = $_SESSION['nickname'] ?? $username;
$avatar = $_SESSION['avatar'] ?? 'uploads/default_avatar.png';
$is_logged_in = true;
function cleanup_expired_bookings($pdo) {
    try {
        $now = new DateTime('now', new DateTimeZone('Europe/Moscow'));
        $stmt = $pdo->query("SELECT booking_id, booking_time, booking_date FROM bookings");
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($bookings as $booking) {
            $booking_datetime = new DateTime($booking['booking_date'] . ' ' . $booking['booking_time'] . ':00:00', new DateTimeZone('Europe/Moscow'));
            if ($now >= $booking_datetime->modify('+1 hour')) {
                $stmt = $pdo->prepare("DELETE FROM bookings WHERE booking_id = ?");
                $stmt->execute([$booking['booking_id']]);
                error_log("Deleted expired booking: booking_id={$booking['booking_id']}");
            }
        }
    } catch (PDOException $e) {
        error_log("Ошибка очистки устаревших бронирований: " . $e->getMessage());
    }
}
cleanup_expired_bookings($pdo);
$stmt = $pdo->prepare("SELECT telegram_code, is_linked, telegram_username FROM telegram_links WHERE user_id = ?");
$stmt->execute([$user_id]);
$telegram_data = $stmt->fetch();
$stmt = $pdo->prepare("SELECT booking_id, computer_id, booking_time, booking_date, phone_number, username FROM bookings WHERE user_id = ? ORDER BY booking_date ASC, booking_time ASC");
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
error_log("User ID: $user_id");
error_log("Количество бронирований для user_id $user_id: " . count($bookings));
if (empty($bookings)) {
    error_log("Бронирования не найдены для user_id $user_id");
} else {
    foreach ($bookings as $booking) {
        error_log("Бронирование: ID={$booking['booking_id']}, PC={$booking['computer_id']}, Дата={$booking['booking_date']}, Время={$booking['booking_time']}, Username={$booking['username']}");
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет | N1ntendo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #FF3E00;
            --primary-light: rgba(255, 62, 0, 0.1);
            --dark: #0A0A0A;
            --darker: #111;
            --light: #F5F5F5;
            --gray: #888;
            --light-gray: #222;
            --border-radius: 12px;
            --transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        body {
            background-color: var(--dark);
            color: var(--light);
            overflow-x: hidden;
            line-height: 1.6;
            font-weight: 400;
            -webkit-font-smoothing: antialiased;
            transition: padding-top 0.3s ease;
        }
        a {
            text-decoration: none;
            color: inherit;
            transition: var(--transition);
        }
        button {
            border: none;
            background: none;
            cursor: pointer;
            transition: var(--transition);
        }
        img {
            max-width: 100%;
            height: auto;
        }
        .container {
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }
        header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            padding: 20px 0;
            background: rgba(10, 10, 10, 0.9);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            transition: var(--transition);
        }
        header.scrolled {
            padding: 15px 0;
            background: rgba(10, 10, 10, 0.98);
        }
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            padding: 0 20px;
            margin: 0 auto;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--light);
        }
        .logo img {
            height: 50px;
        }
        .nav-links {
            display: flex;
            gap: 30px;
        }
        .nav-links a {
            color: var(--gray);
            font-weight: 500;
            position: relative;
            padding: 5px 0;
            font-size: 1rem;
        }
        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary);
            transition: width 0.3s ease;
        }
        .nav-links a:hover,
        .nav-links a.active {
            color: var(--light);
        }
        .nav-links a:hover::after {
            width: 100%;
        }
        .auth-buttons {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            border-radius: var(--border-radius);
            font-weight: 500;
            transition: var(--transition);
        }
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        .btn-primary:hover {
            background: #FF5200;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 62, 0, 0.3);
        }
        .btn-outline {
            border: 1px solid var(--light-gray);
            color: var(--light);
        }
        .btn-outline:hover {
            border-color: var(--primary);
            color: var(--primary);
        }
        .btn-telegram {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-telegram i {
            font-size: 1.3rem;
            transition: var(--transition);
        }
        .btn-telegram:hover i {
            color: var(--primary);
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary);
        }
        .user-nickname {
            font-weight: 500;
            color: var(--light);
        }
        .user-nickname span {
            color: var(--primary);
        }
        .menu-toggle {
            display: none;
            font-size: 1.5rem;
            color: var(--light);
            background: none;
            border: none;
            cursor: pointer;
            transition: var(--transition);
        }
        .menu-toggle:hover {
            color: var(--primary);
        }
        .side-menu {
            position: fixed;
            top: 0;
            right: -100%;
            width: 250px;
            height: 100%;
            background: var(--darker);
            padding: 60px 20px 20px;
            transition: right 0.3s ease;
            z-index: 2000;
            display: flex;
            flex-direction: column;
            gap: 15px;
            box-shadow: -5px 0 15px rgba(0, 0, 0, 0.5);
        }
        .side-menu.active {
            right: 0;
        }
        .side-menu a {
            display: block;
            color: var(--light);
            text-decoration: none;
            padding: 15px 0;
            font-weight: 500;
            transition: var(--transition);
        }
        .side-menu a:hover {
            color: var(--primary);
        }
        .side-menu-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: none;
            border: none;
            color: var(--light);
            font-size: 1.5rem;
            cursor: pointer;
            transition: var(--transition);
        }
        .side-menu-close:hover {
            color: var(--primary);
        }
        .dashboard-container {
            max-width: 1200px;
            margin: 100px auto 40px;
            padding: 20px;
        }
        h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            background: linear-gradient(90deg, var(--primary), #FF6B00);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        h2 {
            color: var(--light);
            margin-bottom: 20px;
        }
        .dashboard-info {
            background: var(--darker);
            padding: 30px;
            border-radius: var(--border-radius);
            border: 1px solid var(--light-gray);
            margin-bottom: 40px;
        }
        .dashboard-info p {
            margin-bottom: 10px;
            color: var(--gray);
        }
        .dashboard-info span {
            color: var(--primary);
        }
        .profile-section {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary);
        }
        .profile-form {
            flex: 1;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--light);
            font-size: 0.9rem;
        }
        .form-control {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--light-gray);
            border-radius: var(--border-radius);
            color: var(--light);
            font-size: 1rem;
            transition: var(--transition);
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(255, 62, 0, 0.05);
        }
        .telegram-section {
            margin-top: 20px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: var(--border-radius);
            border: 1px solid var(--light-gray);
        }
        .telegram-section p {
            margin-bottom: 10px;
            color: var(--gray);
        }
        .telegram-code-container {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }
        .telegram-code {
            padding: 10px;
            background: var(--darker);
            border: 1px solid var(--light-gray);
            border-radius: var(--border-radius);
            color: var(--light);
            font-family: monospace;
            flex: 1;
        }
        .copy-btn {
            padding: 10px 15px;
            background: var(--primary);
            color: white;
            border-radius: var(--border-radius);
            cursor: pointer;
        }
        .copy-btn:hover {
            background: #FF5200;
        }
        .telegram-status {
            margin-top: 10px;
            color: var(--gray);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .telegram-status.linked {
            color: var(--light);
        }
        .telegram-checkmark {
            width: 1.3rem;
            height: 1.3rem;
            fill: var(--light);
            transition: var(--transition);
        }
        .telegram-status:hover .telegram-checkmark {
            fill: var(--primary);
        }
        .bookings-section {
            background: var(--darker);
            padding: 30px;
            border-radius: var(--border-radius);
            border: 1px solid var(--light-gray);
        }
        .bookings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .booking-card {
            background: rgba(255, 255, 255, 0.03);
            border-radius: var(--border-radius);
            padding: 20px;
            border: 1px solid var(--light-gray);
            transition: var(--transition);
        }
        .booking-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .booking-header h3 {
            font-size: 1.2rem;
            color: var(--primary);
        }
        .booking-status {
            padding: 5px 10px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
            background: var(--primary-light);
            color: var(--primary);
        }
        .booking-details p {
            margin-bottom: 10px;
            color: var(--gray);
        }
        .booking-details strong {
            color: var(--light);
        }
        .booking-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .no-bookings {
            text-align: center;
            color: var(--gray);
            padding: 20px;
        }
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }
            .menu-toggle {
                display: block;
            }
            .side-menu {
                display: flex;
            }
            .side-menu.active + header .menu-toggle {
                display: none;
            }
            .auth-buttons .btn-outline {
                display: none;
            }
            .dashboard-container {
                margin: 80px auto 20px;
                padding: 15px;
            }
            h1 { font-size: 2rem; }
            h2 { font-size: 1.5rem; }
            .dashboard-info { padding: 20px; }
            .profile-section { flex-direction: column; align-items: flex-start; }
            .profile-avatar { width: 80px; height: 80px; }
            .form-control { padding: 10px; font-size: 0.9rem; }
            .btn { padding: 10px 20px; font-size: 0.9rem; width: 100%; }
            .telegram-code-container { flex-direction: column; }
            .telegram-code { width: 100%; }
            .copy-btn { width: 100%; }
            .bookings-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 576px) {
            .logo { font-size: 1.2rem; }
            .logo img { height: 40px; }
            .side-menu { width: 200px; padding: 50px 15px 15px; }
            .menu-toggle { font-size: 1.3rem; }
            h1 { font-size: 1.8rem; }
            .dashboard-info { padding: 15px; }
            .profile-avatar { width: 60px; height: 60px; }
            .form-group label { font-size: 0.8rem; }
            .form-control { font-size: 0.8rem; }
            .btn { font-size: 0.8rem; }
            .booking-card { padding: 15px; }
        }
    </style>
</head>
<body>
    <header id="header">
        <div class="container header-container">
            <a href="index.php" class="logo">
                <img src="logo.png" alt="N1ntendo">
                <span>N1NTENDO</span>
            </a>
            <nav class="nav-links">
                <a href="index.php#home">Главная</a>
                <a href="index.php#features">Возможности</a>
                <a href="index.php#games">Игры</a>
                <a href="index.php#pricing">Тарифы</a>
                <a href="index.php#contact">Контакты</a>
                <a href="dashboard.php" class="active">Личный кабинет</a>
            </nav>
            <div class="auth-buttons">
                <?php if ($is_logged_in): ?>
                    <div class="user-profile">
                        <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Avatar" class="user-avatar">
                        <div class="user-nickname">
                            <span><?php echo htmlspecialchars($nickname); ?></span>
                        </div>
                    </div>
                    <a href="logout.php" class="btn btn-outline">Выйти</a>
                <?php else: ?>
                    <button class="btn btn-primary" id="loginBtn">Войти</button>
                <?php endif; ?>
                <button class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </header>
    <div class="side-menu">
        <button class="side-menu-close" id="sideMenuClose">
            <i class="fas fa-times"></i>
        </button>
        <a href="index.php#home">Главная</a>
        <a href="index.php#features">Возможности</a>
        <a href="index.php#games">Игры</a>
        <a href="index.php#pricing">Тарифы</a>
        <a href="index.php#contact">Контакты</a>
        <a href="dashboard.php">Личный кабинет</a>
        <?php if ($is_logged_in): ?>
            <a href="logout.php">Выйти</a>
        <?php else: ?>
            <a href="#" id="mobileLogin">Войти</a>
        <?php endif; ?>
    </div>
    <div class="dashboard-container">
        <h1>Личный кабинет</h1>
        <div class="dashboard-info">
            <div class="profile-section">
                <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Avatar" class="profile-avatar">
                <form class="profile-form" id="profileForm" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="nickname">Ник</label>
                        <input type="text" id="nickname" name="nickname" class="form-control" value="<?php echo htmlspecialchars($nickname); ?>" placeholder="Введите ник">
                    </div>
                    <div class="form-group">
                        <label for="avatar">Аватарка</label>
                        <input type="file" id="avatar" name="avatar" class="form-control" accept="image/*">
                    </div>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </form>
            </div>
            <p>Логин: <span><?php echo htmlspecialchars($username); ?></span></p>
            <p>Email: <span><?php echo htmlspecialchars($email); ?></span></p>
            <p>IP при регистрации: <span><?php echo htmlspecialchars($_SERVER['REMOTE_ADDR']); ?></span></p>
            <p>User ID: <span><?php echo htmlspecialchars($user_id); ?></span></p>
            <a href="index.php" class="btn btn-outline" style="margin-top: 20px;">На главную</a>
            <div class="telegram-section">
                <p><strong>Привязка Telegram</strong></p>
                <?php if ($telegram_data && $telegram_data['is_linked']): ?>
                    <p class="telegram-status linked">
                        <svg class="telegram-checkmark" viewBox="0 0 24 24">
                            <path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/>
                        </svg>
                        Привязан к Telegram: <span><?php echo htmlspecialchars($telegram_data['telegram_username']); ?></span>
                    </p>
                <?php else: ?>
                    <p>Нажмите кнопку ниже, чтобы получить код для привязки вашего аккаунта к Telegram-боту.</p>
                    <button class="btn btn-outline btn-telegram" id="telegramBtn">
                        <i class="fab fa-telegram-plane"></i> Получить код Telegram
                    </button>
                    <div id="telegramCodeContainer" style="display: none;">
                        <p>Ваш код для Telegram-бота:</p>
                        <div class="telegram-code-container">
                            <code id="telegramCode" class="telegram-code"></code>
                            <button class="copy-btn" id="copyCodeBtn">Копировать</button>
                        </div>
                        <p class="telegram-status" id="telegramStatus">Аккаунт не привязан</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <h2>Ваши текущие бронирования</h2>
        <div class="bookings-section">
            <?php if (empty($bookings)): ?>
                <p class="no-bookings">У вас пока нет бронирований.</p>
            <?php else: ?>
                <div class="bookings-grid">
                    <?php foreach ($bookings as $booking): ?>
                        <div class="booking-card" data-booking-id="<?php echo htmlspecialchars($booking['booking_id']); ?>">
                            <div class="booking-header">
                                <h3>Бронирование #<?php echo htmlspecialchars($booking['booking_id']); ?></h3>
                                <span class="booking-status">Активно</span>
                            </div>
                            <div class="booking-details">
                                <p><strong>Компьютер:</strong> PC<?php echo htmlspecialchars($booking['computer_id']); ?></p>
                                <p><strong>Дата:</strong> <?php echo date('d.m.Y', strtotime($booking['booking_date'])); ?></p>
                                <p><strong>Время:</strong> <?php echo htmlspecialchars($booking['booking_time']); ?>:00 - <?php echo htmlspecialchars($booking['booking_time'] + 1); ?>:00</p>
                                <p><strong>Телефон:</strong> <?php echo htmlspecialchars($booking['phone_number']); ?></p>
                                <p><strong>Пользователь:</strong> <?php echo htmlspecialchars($booking['username'] ?? '@unknown'); ?></p>
                            </div>
                            <div class="booking-actions">
                                <button class="btn btn-outline">Редактировать</button>
                                <button class="btn btn-danger cancel-booking" data-booking-id="<?php echo htmlspecialchars($booking['booking_id']); ?>">Отменить</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const header = document.getElementById('header');
            const sideMenu = document.querySelector('.side-menu');
            const menuToggle = document.getElementById('menuToggle');
            const sideMenuClose = document.getElementById('sideMenuClose');
            window.addEventListener('scroll', () => {
                if (window.scrollY > 50) header.classList.add('scrolled');
                else header.classList.remove('scrolled');
            });
            menuToggle.addEventListener('click', () => {
                sideMenu.classList.toggle('active');
                document.body.classList.toggle('menu-open');
            });
            sideMenuClose.addEventListener('click', () => {
                sideMenu.classList.remove('active');
                document.body.classList.remove('menu-open');
            });
            sideMenu.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', () => {
                    sideMenu.classList.remove('active');
                    document.body.classList.remove('menu-open');
                });
            });
            const profileForm = document.getElementById('profileForm');
            if (profileForm) {
                profileForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const formData = new FormData(profileForm);
                    try {
                        const response = await fetch('update_profile.php', {
                            method: 'POST',
                            body: formData
                        });
                        const data = await response.json();
                        alert(data.message);
                        if (data.success) {
                            document.querySelector('.profile-avatar').src = data.avatar || '<?php echo htmlspecialchars($avatar); ?>';
                            document.querySelector('.user-avatar').src = data.avatar || '<?php echo htmlspecialchars($avatar); ?>';
                        }
                    } catch (error) {
                        console.error('Profile update error:', error);
                        alert('Ошибка: ' + error.message);
                    }
                });
            }
            const telegramBtn = document.getElementById('telegramBtn');
            const telegramCodeContainer = document.getElementById('telegramCodeContainer');
            const telegramCodeElement = document.getElementById('telegramCode');
            const copyCodeBtn = document.getElementById('copyCodeBtn');
            const telegramStatus = document.getElementById('telegramStatus');
            if (telegramBtn) {
                telegramBtn.addEventListener('click', async () => {
                    try {
                        console.log('Fetching Telegram code...');
                        const response = await fetch('generate_telegram_code.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            credentials: 'same-origin'
                        });
                        if (!response.ok) {
                            throw new Error(`HTTP error! Status: ${response.status}`);
                        }
                        const data = await response.json();
                        console.log('Response data:', data);
                        if (data.success) {
                            telegramCodeContainer.style.display = 'block';
                            telegramCodeElement.textContent = data.telegram_code;
                            telegramStatus.textContent = data.is_linked 
                                ? `Привязан к Telegram: @${data.telegram_username}` 
                                : 'Аккаунт не привязан';
                            telegramStatus.classList.toggle('linked', data.is_linked);
                        } else {
                            console.error('Error from server:', data.message);
                            alert('Ошибка: ' + data.message);
                        }
                    } catch (error) {
                        console.error('Telegram code fetch error:', error);
                        alert('Ошибка при получении кода: ' + error.message);
                    }
                });
            }
            if (copyCodeBtn) {
                copyCodeBtn.addEventListener('click', () => {
                    const code = telegramCodeElement.textContent;
                    if (!code) {
                        console.error('No code to copy');
                        alert('Ошибка: код отсутствует');
                        return;
                    }
                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(code).then(() => {
                            console.log('Code copied to clipboard');
                            alert('Код скопирован!');
                        }).catch(err => {
                            console.error('Clipboard API error:', err);
                            fallbackCopy(code);
                        });
                    } else {
                        fallbackCopy(code);
                    }
                });
            }
            function fallbackCopy(text) {
                try {
                    const textArea = document.createElement('textarea');
                    textArea.value = text;
                    textArea.style.position = 'fixed';
                    textArea.style.opacity = '0';
                    document.body.appendChild(textArea);
                    textArea.focus();
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                    console.log('Code copied using fallback');
                    alert('Код скопирован!');
                } catch (err) {
                    console.error('Fallback copy error:', err);
                    alert('Ошибка при копировании: ' + err.message);
                }
            }
            document.querySelectorAll('.cancel-booking').forEach(button => {
                button.addEventListener('click', async () => {
                    const bookingId = button.getAttribute('data-booking-id');
                    if (!confirm('Вы уверены, что хотите отменить это бронирование?')) return;
                    try {
                        const response = await fetch('cancel_booking.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: `booking_id=${encodeURIComponent(bookingId)}`
                        });
                        const data = await response.json();
                        alert(data.message);
                        if (data.success) {
                            document.querySelector(`.booking-card[data-booking-id="${bookingId}"]`).remove();
                            const remainingBookings = document.querySelectorAll('.booking-card').length;
                            if (remainingBookings === 0) {
                                document.querySelector('.bookings-section').innerHTML = '<p class="no-bookings">У вас пока нет бронирований.</p>';
                            }
                        }
                    } catch (error) {
                        console.error('Cancel booking error:', error);
                        alert('Ошибка при отмене бронирования: ' + error.message);
                    }
                });
            });
        });
    </script>
</body>
</html>