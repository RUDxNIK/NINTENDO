<?php // N1ntendo
session_start();
$is_logged_in = isset($_SESSION['user_id']);
$username = $is_logged_in ? $_SESSION['username'] : '';
$nickname = $is_logged_in ? ($_SESSION['nickname'] ?? $username) : '';
$avatar = $is_logged_in ? ($_SESSION['avatar'] ?? 'uploads/default_avatar.png') : 'uploads/default_avatar.png';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>N1ntendo | Премиум киберклуб</title>
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

        h1, h2, h3, h4 {
            font-weight: 600;
            line-height: 1.2;
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

        .section {
            padding: 100px 0;
            position: relative;
        }

        .section-title {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-title h2 {
            font-size: 2.5rem;
            margin-bottom: 15px;
            background: linear-gradient(90deg, var(--primary), #FF6B00);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: inline-block;
        }

        .section-title p {
            color: var(--gray);
            max-width: 600px;
            margin: 0 auto;
            font-size: 1.1rem;
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

        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding-top: 100px;
            position: relative;
            overflow: hidden;
            background: var(--dark);
        }

        .hero-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .joystick {
            position: absolute;
            width: 40px;
            height: 40px;
            background: url('https://cdn-icons-png.flaticon.com/512/1087/1087995.png') no-repeat center;
            background-size: contain;
            opacity: 0.2;
            animation: fall 10s linear infinite;
        }

        @keyframes fall {
            0% { transform: translateY(-100vh) rotate(0deg); opacity: 0.2; }
            100% { transform: translateY(100vh) rotate(360deg); opacity: 0.1; }
        }

        .hero-content {
            max-width: 700px;
            margin: 0 auto;
            text-align: center;
            z-index: 1;
            padding: 20px;
        }

        .hero h1 {
            font-size: 3.5rem;
            margin-bottom: 20px;
            color: var(--light);
        }

        .hero h1 span {
            background: linear-gradient(90deg, var(--primary), #FF6B00);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero p {
            font-size: 1.2rem;
            margin-bottom: 30px;
            color: var(--gray);
        }

        .hero-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .feature-card {
            background: var(--darker);
            border-radius: var(--border-radius);
            padding: 30px;
            border: 1px solid var(--light-gray);
            transition: var(--transition);
        }

        .feature-card:hover {
            transform: translateY(-10px);
            border-color: var(--primary);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .feature-icon {
            width: 50px;
            height: 50px;
            background: var(--primary-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            color: var(--primary);
            font-size: 1.2rem;
        }

        .feature-card h3 {
            font-size: 1.3rem;
            margin-bottom: 15px;
            color: var(--light);
        }

        .feature-card p {
            color: var(--gray);
        }

        .games-slider {
            position: relative;
            margin-top: 40px;
            max-width: 100%;
            padding: 0 60px;
        }

        .games-track {
            display: flex;
            gap: 20px;
            padding: 10px 0;
            overflow-x: hidden;
            scroll-behavior: smooth;
        }

        .game-card {
            flex: 0 0 calc(100% - 40px);
            max-width: 300px;
            background: var(--darker);
            border-radius: var(--border-radius);
            overflow: hidden;
            border: 1px solid var(--light-gray);
            transition: var(--transition);
        }

        .game-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .game-image-container {
            width: 100%;
            height: 180px;
            overflow: hidden;
        }

        .game-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .game-card:hover .game-image {
            transform: scale(1.05);
        }

        .game-info {
            padding: 20px;
        }

        .game-info h3 {
            margin-bottom: 10px;
            font-size: 1.2rem;
            color: var(--light);
        }

        .game-info p {
            color: var(--gray);
            font-size: 0.9rem;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .game-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .game-tag {
            background: var(--primary-light);
            color: var(--primary);
            padding: 5px 12px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .slider-prev, .slider-next {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: var(--primary);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            font-size: 1.2rem;
            z-index: 10;
        }

        .slider-prev { left: 10px; }
        .slider-next { right: 10px; }

        .pricing-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 40px;
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .pricing-card {
            background: var(--darker);
            border-radius: var(--border-radius);
            padding: 40px 30px;
            border: 1px solid var(--light-gray);
            transition: var(--transition);
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 400px;
        }

        .pricing-card.popular {
            border-color: var(--primary);
            transform: scale(1.02);
        }

        .pricing-card:hover {
            transform: translateY(-10px);
            border-color: var(--primary);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .popular-tag {
            position: absolute;
            top: 20px;
            right: 20px;
            background: var(--primary);
            color: var(--dark);
            padding: 5px 15px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 700;
        }

        .pricing-card h3 {
            font-size: 1.3rem;
            margin-bottom: 15px;
            color: var(--light);
        }

        .price {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--light);
        }

        .price span {
            font-size: 1rem;
            color: var(--gray);
            font-weight: 400;
        }

        .pricing-features {
            margin-bottom: 30px;
        }

        .pricing-feature {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
            color: var(--gray);
        }

        .pricing-feature i {
            color: var(--primary);
            font-size: 0.9rem;
        }

        .pricing-card button {
            width: 100%;
        }

        .contact-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
            margin-top: 40px;
        }

        .contact-info h3 {
            font-size: 1.5rem;
            margin-bottom: 25px;
            color: var(--light);
        }

        .contact-details {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .contact-icon {
            width: 50px;
            height: 50px;
            background: var(--primary-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: var(--primary);
        }

        .contact-text h4 {
            font-size: 1.1rem;
            margin-bottom: 5px;
            color: var(--light);
        }

        .contact-text p, .contact-text a {
            color: var(--gray);
            transition: var(--transition);
        }

        .contact-text a:hover {
            color: var(--primary);
        }

        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .social-link {
            width: 40px;
            height: 40px;
            background: var(--primary-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            color: var(--light);
            transition: var(--transition);
        }

        .social-link:hover {
            background: var(--primary);
            transform: translateY(-3px);
        }

        .contact-form {
            background: var(--darker);
            border-radius: var(--border-radius);
            padding: 30px;
            border: 1px solid var(--light-gray);
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

        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }

        footer {
            background: var(--darker);
            padding: 40px 0;
            border-top: 1px solid var(--light-gray);
        }

        .footer-bottom {
            text-align: center;
            color: var(--gray);
            font-size: 0.9rem;
        }

        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }

        .modal.active {
            opacity: 1;
            pointer-events: all;
        }

        .modal-content {
            background: var(--darker);
            padding: 40px;
            border-radius: var(--border-radius);
            width: 100%;
            max-width: 450px;
            position: relative;
            transform: translateY(20px);
            transition: all 0.3s ease;
            border: 1px solid var(--light-gray);
        }

        .modal.active .modal-content {
            transform: translateY(0);
        }

        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: none;
            border: none;
            color: var(--gray);
            font-size: 1.5rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .modal-close:hover {
            color: var(--primary);
        }

        .modal-title {
            color: var(--light);
            font-size: 1.8rem;
            margin-bottom: 30px;
            text-align: center;
        }

        .auth-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .auth-form .form-group {
            position: relative;
        }

        .auth-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            font-size: 01rem;
        }

        .auth-form .form-control {
            padding-left: 45px;
        }

        .auth-toggle {
            text-align: center;
            color: var(--gray);
            margin-top: 10px;
        }

        .auth-toggle a {
            color: var(--primary);
            font-weight: 500;
        }

        .auth-toggle a:hover {
            text-decoration: underline;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate {
            opacity: 0;
            animation: slideUp 0.6s forwards;
        }

        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }
        .delay-4 { animation-delay: 0.4s; }
        .delay-5 { animation-delay: 0.5s; }
        .delay-6 { animation-delay: 0.6s; }
        .delay-7 { animation-delay: 0.7s; }
        .delay-8 { animation-delay: 0.8s; }

        @media (max-width: 992px) {
            .hero h1 { font-size: 2.8rem; }
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

            body.menu-open {
                padding-top: 80px;
            }

            .hero {
                padding-top: 120px;
                padding-bottom: 60px;
            }

            .hero-content {
                max-width: 90%;
            }

            .hero-buttons {
                justify-content: center;
            }

            .section {
                padding: 70px 0;
            }

            .section-title h2 {
                font-size: 2rem;
            }

            .games-slider {
                padding: 0 50px;
            }

            .game-card {
                flex: 0 0 calc(100% - 40px);
                max-width: 280px;
            }
        }

        @media (max-width: 576px) {
            .hero h1 { font-size: 2rem; }
            .hero p { font-size: 1rem; }
            .hero-buttons { flex-direction: column; }
            .section-title h2 { font-size: 1.8rem; }
            .pricing-cards { grid-template-columns: 1fr; }
            .modal-content { padding: 30px 20px; }
            .game-card { flex: 0 0 calc(100% - 20px); max-width: 100%; }
            .games-slider { padding: 0 40px; }
            .logo { font-size: 1.2rem; }
            .logo img { height: 40px; }
            .side-menu { width: 200px; padding: 50px 15px 15px; }
            .menu-toggle { font-size: 1.3rem; }
        }
    </style>
</head>
<body>
    <header id="header">
        <div class="container header-container">
            <a href="#" class="logo">
                <img src="logo.png" alt="N1ntendo">
                <span>N1NTENDO</span>
            </a>
            
            <nav class="nav-links">
                <a href="#home" class="active">Главная</a>
                <a href="#features">Возможности</a>
                <a href="#games">Игры</a>
                <a href="#pricing">Тарифы</a>
                <a href="#contact">Контакты</a>
                <?php if ($is_logged_in): ?>
                    <a href="dashboard.php">Личный кабинет</a>
                <?php endif; ?>
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

    <section class="hero section" id="home">
        <div class="hero-background" id="joystickContainer"></div>
        <div class="container">
            <div class="hero-content animate">
                <h1>Премиум <span>киберклуб</span> для настоящих геймеров</h1>
                <p>Эксклюзивное пространство с топовым оборудованием и комфортной атмосферой для идеального игрового опыта.</p>
                <div class="hero-buttons">
                    <a href="#pricing" class="btn btn-primary">Выбрать тариф</a>
                    <a href="#features" class="btn btn-outline">Узнать больше</a>
                </div>
            </div>
        </div>
    </section>

    <section class="section" id="features">
        <div class="container">
            <div class="section-title animate">
                <h2>Наши преимущества</h2>
                <p>Почему геймеры выбирают именно наш клуб</p>
            </div>
            <div class="features-grid">
                <div class="feature-card animate delay-1">
                    <div class="feature-icon"><i class="fas fa-tachometer-alt"></i></div>
                    <h3>Мощные ПК</h3>
                    <p>Топовые игровые компьютеры с процессорами Intel Core i9 и видеокартами NVIDIA RTX 4090</p>
                </div>
                <div class="feature-card animate delay-2">
                    <div class="feature-icon"><i class="fas fa-network-wired"></i></div>
                    <h3>Скоростной интернет</h3>
                    <p>Гигабитное подключение с пингом менее 5ms для комфортной игры в любые онлайн-игры</p>
                </div>
                <div class="feature-card animate delay-3">
                    <div class="feature-icon"><i class="fas fa-chair"></i></div>
                    <h3>Комфорт</h3>
                    <p>Эргономичные кресла Secretlab и профессиональные мониторы с частотой 240Hz</p>
                </div>
                <div class="feature-card animate delay-4">
                    <div class="feature-icon"><i class="fas fa-utensils"></i></div>
                    <h3>Зона отдыха</h3>
                    <p>Уютная лаунж-зона с напитками и снеками, где можно отдохнуть между играми</p>
                </div>
                <div class="feature-card animate delay-5">
                    <div class="feature-icon"><i class="fas fa-headset"></i></div>
                    <h3>Техподдержка</h3>
                    <p>Круглосуточная помощь от нашей команды для настройки и комфорта</p>
                </div>
                <div class="feature-card animate delay-6">
                    <div class="feature-icon"><i class="fas fa-trophy"></i></div>
                    <h3>Турниры</h3>
                    <p>Регулярные соревнования с призами для всех желающих</p>
                </div>
                <div class="feature-card animate delay-7">
                    <div class="feature-icon"><i class="fas fa-gamepad"></i></div>
                    <h3>Широкий выбор игр</h3>
                    <p>Доступ к сотням игр, включая новинки и классику</p>
                </div>
                <div class="feature-card animate delay-8">
                    <div class="feature-icon"><i class="fas fa-users"></i></div>
                    <h3>Сообщество</h3>
                    <p>Объединяйтесь с единомышленниками для совместной игры</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section" id="games">
        <div class="container">
            <div class="section-title animate">
                <h2>Популярные игры</h2>
                <p>В нашем клубе доступны все самые популярные игры</p>
            </div>
            <div class="games-slider animate delay-1">
                <button class="slider-prev"><i class="fas fa-chevron-left"></i></button>
                <div class="games-track">
                    <div class="game-card">
                        <div class="game-image-container">
                            <img src="DOTA.png" alt="Dota 2" class="game-image">
                        </div>
                        <div class="game-info">
                            <h3>DOTA 2</h3>
                            <p>Командная стратегия в реальном времени с сотнями уникальных героев</p>
                            <div class="game-tags">
                                <span class="game-tag">MOBA</span>
                                <span class="game-tag">Мультиплеер</span>
                                <span class="game-tag">Стратегия</span>
                            </div>
                        </div>
                    </div>
                    <div class="game-card">
                        <div class="game-image-container">
                            <img src="CS.png" alt="CS 2" class="game-image">
                        </div>
                        <div class="game-info">
                            <h3>CS 2</h3>
                            <p>Тактический шутер с реалистичной стрельбой и командными сражениями</p>
                            <div class="game-tags">
                                <span class="game-tag">FPS</span>
                                <span class="game-tag">Экшен</span>
                                <span class="game-tag">Киберспорт</span>
                            </div>
                        </div>
                    </div>
                    <div class="game-card">
                        <div class="game-image-container">
                            <img src="GTA.png" alt="GTA 5" class="game-image">
                        </div>
                        <div class="game-info">
                            <h3>GTA 5</h3>
                            <p>Открытый мир с криминальными историями и полной свободой действий</p>
                            <div class="game-tags">
                                <span class="game-tag">Открытый мир</span>
                                <span class="game-tag">Экшен</span>
                                <span class="game-tag">Приключения</span>
                            </div>
                        </div>
                    </div>
                    <div class="game-card">
                        <div class="game-image-container">
                            <img src="RUST.png" alt="RUST" class="game-image">
                        </div>
                        <div class="game-info">
                            <h3>RUST</h3>
                            <p>Выживание в жестоком мире, где каждый сам за себя</p>
                            <div class="game-tags">
                                <span class="game-tag">Выживание</span>
                                <span class="game-tag">Песочница</span>
                                <span class="game-tag">Хардкор</span>
                            </div>
                        </div>
                    </div>
                    <div class="game-card">
                        <div class="game-image-container">
                            <img src="PUBG.png" alt="PUBG" class="game-image">
                        </div>
                        <div class="game-info">
                            <h3>PUBG</h3>
                            <p>Реалистичная королевская битва с тактическим геймплеем</p>
                            <div class="game-tags">
                                <span class="game-tag">Баттл-рояль</span>
                                <span class="game-tag">FPS</span>
                                <span class="game-tag">Экшен</span>
                            </div>
                        </div>
                    </div>
                    <div class="game-card">
                        <div class="game-image-container">
                            <img src="FORNITE.png" alt="Fortnite" class="game-image">
                        </div>
                        <div class="game-info">
                            <h3>Fortnite</h3>
                            <p>Яркая королевская битва с элементами строительства</p>
                            <div class="game-tags">
                                <span class="game-tag">Баттл-рояль</span>
                                <span class="game-tag">Экшен</span>
                                <span class="game-tag">Казуал</span>
                            </div>
                        </div>
                    </div>
                </div>
                <button class="slider-next"><i class="fas fa-chevron-right"></i></button>
            </div>
        </div>
    </section>

    <section class="section" id="pricing">
        <div class="container">
            <div class="section-title animate">
                <h2>Наши тарифы</h2>
                <p>Выберите подходящий вариант для идеального игрового опыта</p>
            </div>
            <div class="pricing-cards">
                <div class="pricing-card animate delay-1">
                    <h3>Стандарт</h3>
                    <div class="price">600₽ <span>/час</span></div>
                    <div class="pricing-features">
                        <div class="pricing-feature"><i class="fas fa-check"></i> Доступ к игровым ПК</div>
                        <div class="pricing-feature"><i class="fas fa-check"></i> Базовые игровые периферии</div>
                        <div class="pricing-feature"><i class="fas fa-check"></i> 5% скидка в кафе</div>
                    </div>
                    <button class="btn btn-outline">Выбрать</button>
                </div>
                <div class="pricing-card popular animate delay-2">
                    <div class="popular-tag">Популярный</div>
                    <h3>Премиум</h3>
                    <div class="price">900₽ <span>/час</span></div>
                    <div class="pricing-features">
                        <div class="pricing-feature"><i class="fas fa-check"></i> Топовые игровые ПК</div>
                        <div class="pricing-feature"><i class="fas fa-check"></i> Профессиональные мониторы 240Гц</div>
                        <div class="pricing-feature"><i class="fas fa-check"></i> Премиум периферия</div>
                        <div class="pricing-feature"><i class="fas fa-check"></i> 10% скидка в кафе</div>
                    </div>
                    <button class="btn btn-primary">Выбрать</button>
                </div>
                <div class="pricing-card animate delay-3">
                    <h3>VIP</h3>
                    <div class="price">1500₽ <span>/час</span></div>
                    <div class="pricing-features">
                        <div class="pricing-feature"><i class="fas fa-check"></i> Эксклюзивные VIP-зоны</div>
                        <div class="pricing-feature"><i class="fas fa-check"></i> Персонализированные настройки</div>
                        <div class="pricing-feature"><i class="fas fa-check"></i> Бесплатные напитки</div>
                        <div class="pricing-feature"><i class="fas fa-check"></i> 24/7 доступ</div>
                    </div>
                    <button class="btn btn-outline">Выбрать</button>
                </div>
            </div>
        </div>
    </section>

    <section class="section" id="contact">
        <div class="container">
            <div class="section-title animate">
                <h2>Контакты</h2>
                <p>Свяжитесь с нами для бронирования или вопросов</p>
            </div>
            <div class="contact-container">
                <div class="contact-info animate delay-1">
                    <h3>Наши контакты</h3>
                    <div class="contact-details">
                        <div class="contact-item">
                            <div class="contact-icon"><i class="fas fa-map-marker-alt"></i></div>
                            <div class="contact-text">
                                <h4>Адрес</h4>
                                <p>проспект Ленина, Новороссийск</p>
                            </div>
                        </div>
                        <div class="contact-item">
                            <div class="contact-icon"><i class="fas fa-phone-alt"></i></div>
                            <div class="contact-text">
                                <h4>Телефон</h4>
                                <a href="tel:+79981373428">+7 (988) 137-34-28</a>
                            </div>
                        </div>
                        <div class="contact-item">
                            <div class="contact-icon"><i class="fas fa-envelope"></i></div>
                            <div class="contact-text">
                                <h4>Email</h4>
                                <a href="mailto:support@n1ntendo.ru">support@n1ntendo.ru</a>
                            </div>
                        </div>
                        <div class="contact-item">
                            <div class="contact-icon"><i class="fas fa-clock"></i></div>
                            <div class="contact-text">
                                <h4>Часы работы</h4>
                                <p>Круглосуточно, 7 дней в неделю</p>
                            </div>
                        </div>
                    </div>
                    <h3 style="margin-top: 30px;">Мы в соцсетях</h3>
                    <div class="social-links">
                        <a href="https://vk.com/nintendonvrsk" class="social-link"><i class="fab fa-vk"></i></a>
                        <a href="https://t.me/n1ntendo_nvrsk" class="social-link"><i class="fab fa-telegram"></i></a>
                        <a href="https://www.youtube.com/watch?v=L2ObPJrRbKw" class="social-link"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="contact-form animate delay-2">
                    <form id="contactForm">
                        <div class="form-group">
                            <label for="name">Ваше имя</label>
                            <input type="text" id="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="subject">Тема</label>
                            <input type="text" id="subject" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="message">Сообщение</label>
                            <textarea id="message" class="form-control" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Отправить</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="footer-bottom">
                <p>© 2025 N1ntendo Cyber Club. Все права защищены.</p>
            </div>
        </div>
    </footer>

    <div class="modal" id="loginModal">
        <div class="modal-content">
            <button class="modal-close" id="closeLogin"><i class="fas fa-times"></i></button>
            <h2 class="modal-title">Вход</h2>
            <form class="auth-form" id="loginForm">
                <div class="form-group">
                    <i class="fas fa-user auth-icon"></i>
                    <input type="text" class="form-control" id="loginUsername" placeholder="Имя пользователя" required>
                </div>
                <div class="form-group">
                    <i class="fas fa-lock auth-icon"></i>
                    <input type="password" class="form-control" id="loginPassword" placeholder="Пароль" required>
                </div>
                <button type="submit" class="btn btn-primary">Войти</button>
                <div class="auth-toggle">
                    Нет аккаунта? <a href="#" id="showRegister">Зарегистрироваться</a>
                </div>
            </form>
        </div>
    </div>

    <div class="modal" id="registerModal">
        <div class="modal-content">
            <button class="modal-close" id="closeRegister"><i class="fas fa-times"></i></button>
            <h2 class="modal-title">Регистрация</h2>
            <form class="auth-form" id="registerForm">
                <div class="form-group">
                    <i class="fas fa-user auth-icon"></i>
                    <input type="text" class="form-control" id="registerUsername" placeholder="Имя пользователя" required>
                </div>
                <div class="form-group">
                    <i class="fas fa-user-circle auth-icon"></i>
                    <input type="text" class="form-control" id="registerNickname" placeholder="Ник (опционально)">
                </div>
                <div class="form-group">
                    <i class="fas fa-envelope auth-icon"></i>
                    <input type="email" class="form-control" id="registerEmail" placeholder="Email" required>
                </div>
                <div class="form-group">
                    <i class="fas fa-lock auth-icon"></i>
                    <input type="password" class="form-control" id="registerPassword" placeholder="Пароль" required>
                </div>
                <div class="form-group">
                    <i class="fas fa-lock auth-icon"></i>
                    <input type="password" class="form-control" id="registerConfirmPassword" placeholder="Подтвердите пароль" required>
                </div>
                <button type="submit" class="btn btn-primary">Зарегистрироваться</button>
                <div class="auth-toggle">
                    Уже есть аккаунт? <a href="#" id="showLogin">Войти</a>
                </div>
            </form>
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

            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.querySelector(this.getAttribute('href')).scrollIntoView({ behavior: 'smooth' });
                });
            });

            const loginBtn = document.getElementById('loginBtn');
            const loginModal = document.getElementById('loginModal');
            const registerModal = document.getElementById('registerModal');
            const closeLogin = document.getElementById('closeLogin');
            const closeRegister = document.getElementById('closeRegister');
            const showRegister = document.getElementById('showRegister');
            const showLogin = document.getElementById('showLogin');
            const mobileLogin = document.getElementById('mobileLogin');

            function openModal(modal) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }

            function closeModal(modal) {
                modal.classList.remove('active');
                document.body.style.overflow = 'auto';
            }

            if (loginBtn) loginBtn.addEventListener('click', () => openModal(loginModal));
            closeLogin.addEventListener('click', () => closeModal(loginModal));
            closeRegister.addEventListener('click', () => closeModal(registerModal));
            showRegister.addEventListener('click', (e) => { e.preventDefault(); closeModal(loginModal); openModal(registerModal); });
            showLogin.addEventListener('click', (e) => { e.preventDefault(); closeModal(registerModal); openModal(loginModal); });
            if (mobileLogin) mobileLogin.addEventListener('click', (e) => { 
                e.preventDefault(); 
                sideMenu.classList.remove('active'); 
                openModal(loginModal); 
                document.body.classList.remove('menu-open'); 
            });

            [loginModal, registerModal].forEach(modal => {
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) closeModal(modal);
                });
            });

            const joystickContainer = document.getElementById('joystickContainer');
            function createJoystick() {
                const joystick = document.createElement('div');
                joystick.classList.add('joystick');
                joystick.style.left = `${Math.random() * 100}vw`;
                joystick.style.animationDuration = `${Math.random() * 5 + 5}s`;
                joystickContainer.appendChild(joystick);
                setTimeout(() => joystick.remove(), 10000);
            }
            setInterval(createJoystick, 500);

            const track = document.querySelector('.games-track');
            const prevBtn = document.querySelector('.slider-prev');
            const nextBtn = document.querySelector('.slider-next');
            const cardWidth = window.innerWidth < 576 ? window.innerWidth - 40 : 320;
            prevBtn.addEventListener('click', () => track.scrollBy({ left: -cardWidth, behavior: 'smooth' }));
            nextBtn.addEventListener('click', () => track.scrollBy({ left: cardWidth, behavior: 'smooth' }));

            const loginForm = document.getElementById('loginForm');
            if (loginForm) {
                loginForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const username = document.getElementById('loginUsername').value;
                    const password = document.getElementById('loginPassword').value;
                    try {
                        const response = await fetch('login.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: `username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`
                        });
                        const data = await response.json();
                        alert(data.message);
                        if (data.success) { closeModal(loginModal); window.location.reload(); }
                    } catch (error) { alert('Ошибка: ' + error.message); }
                });
            }

            const registerForm = document.getElementById('registerForm');
            if (registerForm) {
                registerForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const username = document.getElementById('registerUsername').value;
                    const nickname = document.getElementById('registerNickname').value;
                    const email = document.getElementById('registerEmail').value;
                    const password = document.getElementById('registerPassword').value;
                    const confirmPassword = document.getElementById('registerConfirmPassword').value;
                    if (password !== confirmPassword) { alert('Пароли не совпадают!'); return; }
                    try {
                        const response = await fetch('register.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: `username=${encodeURIComponent(username)}&nickname=${encodeURIComponent(nickname)}&email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}&confirm_password=${encodeURIComponent(confirmPassword)}`
                        });
                        const data = await response.json();
                        alert(data.message);
                        if (data.success) { closeModal(registerModal); openModal(loginModal); registerForm.reset(); }
                    } catch (error) { alert('Ошибка: ' + error.message); }
                });
            }

            const contactForm = document.getElementById('contactForm');
            if (contactForm) {
                contactForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const name = document.getElementById('name').value;
                    const email = document.getElementById('email').value;
                    const subject = document.getElementById('subject').value;
                    const message = document.getElementById('message').value;
                    try {
                        const response = await fetch('submit_request.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: `name=${encodeURIComponent(name)}&email=${encodeURIComponent(email)}&subject=${encodeURIComponent(subject)}&message=${encodeURIComponent(message)}`
                        });
                        const data = await response.json();
                        alert(data.message);
                        if (data.success) contactForm.reset();
                    } catch (error) { alert('Ошибка: ' + error.message); }
                });
            }

            const animateElements = document.querySelectorAll('.animate');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1 });
            animateElements.forEach(el => observer.observe(el));
        });
    </script>
</body>
</html>