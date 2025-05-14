<DOCUMENT>
<?php
session_start();
$is_logged_in = isset($_SESSION['user_id']);
$username = $is_logged_in ? $_SESSION['username'] : '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>N1ntendo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preload" href="logo.png" as="image">
    <link rel="preload" href="nintendo.png" as="image">
    <link rel="preload" href="DOTA.png" as="image">
    <link rel="preload" href="CS.png" as="image">
    <link rel="preload" href="GTA.png" as="image">
    <link rel="preload" href="RUST.png" as="image">
    <link rel="preload" href="PUBG.png" as="image">
    <link rel="preload" href="FORNITE.png" as="image">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #ff0000;
            --secondary: #ffffff;
            --dark: #000000;
            --darker: #1a1a1a;
            --light: #ffffff;
            --neon-glow: 0 0 10px rgba(255, 0, 0, 0.7), 0 0 20px rgba(255, 0, 0, 0.5);
            --scrollbar-color: #808080;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Roboto', sans-serif;
        }
        body {
            background-color: var(--dark);
            color: var(--light);
            overflow-x: hidden;
            line-height: 1.6;
        }
        body.loading {
            overflow: hidden;
            height: 100vh;
        }
        .loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #000;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.5s ease;
        }
        .loader-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }
        .loader-logo {
            width: 120px;
            height: 120px;
            margin-bottom: 30px;
            animation: pulse 1.5s infinite ease-in-out;
        }
        .loader-text {
            color: white;
            font-size: 1.2rem;
            margin-bottom: 20px;
            opacity: 0.8;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .loader-bar-container {
            width: 300px;
            height: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            overflow: hidden;
            position: relative;
            margin-bottom: 20px;
        }
        .loader-progress {
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 0;
            background: linear-gradient(90deg, var(--primary), #ff5e5e);
            animation: loading 2s cubic-bezier(0.65, 0, 0.35, 1) forwards;
            border-radius: 4px;
        }
        .loader-percentage {
            color: white;
            font-size: 1rem;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .loader-dots {
            display: flex;
            gap: 10px;
        }
        .loader-dot {
            width: 10px;
            height: 10px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            animation: dotPulse 1.4s infinite ease-in-out;
        }
        .loader-dot:nth-child(1) { animation-delay: 0s; }
        .loader-dot:nth-child(2) { animation-delay: 0.2s; }
        .loader-dot:nth-child(3) { animation-delay: 0.4s; }
        @keyframes loading {
            0% { width: 0; }
            100% { width: 100%; }
        }
        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }
        @keyframes dotPulse {
            0%, 100% { transform: scale(1); background: rgba(255, 255, 255, 0.3); }
            50% { transform: scale(1.3); background: var(--primary); }
        }
        @keyframes slideOutLeft {
            0% { transform: translateX(0); opacity: 1; }
            100% { transform: translateX(-100%); opacity: 0; }
        }
        @keyframes slideOutRight {
            0% { transform: translateX(0); opacity: 1; }
            100% { transform: translateX(100%); opacity: 0; }
        }
        @keyframes slideInFromLeft {
            0% { transform: translateX(-100%); opacity: 0; }
            100% { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideInFromRight {
            0% { transform: translateX(100%); opacity: 0; }
            100% { transform: translateX(0); opacity: 1; }
        }
        @keyframes modalOpen {
            0% { transform: scale(0.7); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }
        @keyframes modalClose {
            0% { transform: scale(1); opacity: 1; }
            100% { transform: scale(0.7); opacity: 0; }
        }
        a, button, .card, .menu-item {
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: var(--darker);
        }
        ::-webkit-scrollbar-thumb {
            background: var(--scrollbar-color);
            border-radius: 4px;
        }
        header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            padding: 1.5rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 0, 0, 0.2);
        }
        .logo {
            display: flex;
            align-items: center;
            text-decoration: none;
        }
        .header-logo {
            height: 60px;
            width: auto;
            transition: all 0.3s ease;
        }
        .header-logo:hover {
            transform: scale(1.05);
            filter: drop-shadow(0 0 5px rgba(255, 0, 0, 0.5));
        }
        .logo-text {
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
            margin-left: 10px;
        }
        .nav-links {
            display: flex;
            gap: 2rem;
        }
        .nav-links a {
            color: var(--light);
            text-decoration: none;
            font-weight: 500;
            position: relative;
            padding: 0.5rem 0;
        }
        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary);
            transition: width 0.4s ease;
        }
        .nav-links a:hover::after {
            width: 100%;
        }
        .cta-button {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.8rem 1.8rem;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(255, 0, 0, 0.4);
            position: relative;
            overflow: hidden;
            text-decoration: none;
        }
        .cta-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(255, 0, 0, 0.6);
        }
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--light);
            font-size: 1.5rem;
            cursor: pointer;
        }
        .hero {
            height: 100vh;
            display: flex;
            align-items: center;
            padding: 0 5%;
            position: relative;
            overflow: hidden;
        }
        .hero-content {
            max-width: 600px;
        }
        .hero h1 {
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
            line-height: 1.2;
            color: var(--light);
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 1s 0.3s forwards;
        }
        .hero h1 span {
            color: var(--primary);
        }
        .hero p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            color: rgba(255, 255, 255, 0.8);
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 1s 0.6s forwards;
        }
        .hero-buttons {
            display: flex;
            gap: 1rem;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 1s 0.9s forwards;
        }
        .secondary-button {
            background: transparent;
            color: var(--light);
            border: 2px solid var(--primary);
            padding: 0.8rem 1.8rem;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        .secondary-button:hover {
            background: rgba(255, 0, 0, 0.2);
            transform: translateY(-3px);
        }
        .hero-image {
            position: absolute;
            right: 5%;
            bottom: 10%;
            width: 40%;
            max-width: 600px;
            opacity: 0;
            animation: nintendoFloat 1.5s 0.5s forwards, float 6s infinite 2s ease-in-out;
            filter: drop-shadow(0 0 15px rgba(255, 0, 0, 0.7));
        }
        @keyframes nintendoFloat {
            0% { opacity: 0; transform: translateX(100px) rotate(15deg) scale(0.8); }
            60% { transform: translateX(0) rotate(-5deg) scale(1.05); }
            80% { transform: rotate(2deg) scale(1); }
            100% { opacity: 1; transform: translateX(0) rotate(0) scale(1); }
        }
        @keyframes fadeInUp {
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        .section {
            padding: 8rem 5%;
            position: relative;
        }
        .section-title {
            text-align: center;
            margin-bottom: 5rem;
            opacity: 0;
            transform: translateY(30px);
        }
        .section-title h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--primary);
        }
        .section-title p {
            color: rgba(255, 255, 255, 0.7);
            max-width: 600px;
            margin: 0 auto;
        }
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        .feature-card {
            background: rgba(26, 26, 26, 0.6);
            border: 1px solid rgba(255, 0, 0, 0.2);
            border-radius: 16px;
            padding: 2.5rem;
            backdrop-filter: blur(10px);
            transition: all 0.4s ease;
            opacity: 0;
            transform: translateY(30px);
        }
        .feature-card:hover {
            transform: translateY(-10px);
            border-color: var(--primary);
            box-shadow: var(--neon-glow);
        }
        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            color: var(--primary);
        }
        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--light);
        }
        .feature-card p {
            color: rgba(255, 255, 255, 0.7);
        }
        .games-slider {
            position: relative;
            margin-top: 3rem;
            width: 100%;
            overflow: hidden;
            padding: 0 60px;
        }
        .games-slider-container {
            position: relative;
            padding: 20px 0;
        }
        .games-track {
            display: flex;
            gap: 30px;
            padding: 10px 0;
            transition: transform 0.5s ease-out;
            will-change: transform;
        }
        .game-card {
            flex: 0 0 300px;
            background: rgba(26, 26, 26, 0.6);
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid rgba(255, 0, 0, 0.2);
            transition: all 0.4s ease;
            opacity: 0;
            transform: translateY(30px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
        }
        .game-card:hover {
            transform: translateY(-10px) scale(1.03);
            box-shadow: var(--neon-glow), 0 15px 30px rgba(0, 0, 0, 0.4);
            border-color: var(--primary);
        }
        .game-image-container {
            position: relative;
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
            padding: 1.5rem;
        }
        .game-info h3 {
            margin-bottom: 0.5rem;
            font-size: 1.3rem;
            color: var(--light);
        }
        .game-info p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.95rem;
            margin-bottom: 1rem;
            line-height: 1.5;
        }
        .game-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .game-tag {
            background: rgba(255, 0, 0, 0.2);
            color: var(--primary);
            padding: 0.3rem 0.8rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .game-card:hover .game-tag {
            background: rgba(255, 0, 0, 0.3);
        }
        .slider-nav {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
            padding: 10px 0;
        }
        .slider-dot {
            width: 12px;
            height: 12px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .slider-dot.active {
            background: var(--primary);
            transform: scale(1.2);
        }
        .slider-arrows {
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            display: flex;
            justify-content: space-between;
            pointer-events: none;
            z-index: 20;
            transform: translateY(-50%);
        }
        .slider-arrow {
            width: 50px;
            height: 50px;
            background: rgba(0, 0, 0, 0.7);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            pointer-events: all;
            color: white;
            font-size: 1.2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .slider-arrow:hover {
            background: var(--primary);
            transform: scale(1.1);
        }
        .pricing-cards {
            display: flex;
            justify-content: center;
            gap: 2rem;
            flex-wrap: wrap;
            margin-top: 3rem;
        }
        .pricing-card {
            background: rgba(26, 26, 26, 0.6);
            border-radius: 16px;
            padding: 2.5rem;
            width: 320px;
            border: 1px solid rgba(255, 0, 0, 0.2);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            opacity: 0;
            transform: translateY(30px);
        }
        .pricing-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: var(--primary);
        }
        .pricing-card.popular::before {
            height: 8px;
        }
        .pricing-card.popular {
            border-color: var(--primary);
            transform: translateY(30px) scale(1.05);
        }
        .pricing-card:hover {
            transform: translateY(-10px);
            border-color: var(--primary);
            box-shadow: var(--neon-glow);
        }
        .pricing-card.popular:hover {
            transform: translateY(-10px) scale(1.05);
        }
        .popular-tag {
            position: absolute;
            top: 20px;
            right: 20px;
            background: var(--primary);
            color: var(--dark);
            padding: 0.3rem 1rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 700;
        }
        .pricing-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--light);
        }
        .price {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: var(--primary);
        }
        .price span {
            font-size: 1rem;
            color: rgba(255, 255, 255, 0.7);
        }
        .pricing-features {
            margin-bottom: 2rem;
        }
        .pricing-feature {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            margin-bottom: 0.8rem;
            color: rgba(255, 255, 255, 0.8);
        }
        .pricing-feature i {
            color: var(--primary);
        }
        .contact-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 3rem;
            margin-top: 3rem;
        }
        .contact-info {
            opacity: 0;
            transform: translateY(30px);
        }
        .contact-info h3 {
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
            color: var(--primary);
        }
        .contact-details {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        .contact-item {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .contact-icon {
            width: 50px;
            height: 50px;
            background: rgba(255, 0, 0, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: var(--primary);
        }
        .contact-text h4 {
            font-size: 1.1rem;
            margin-bottom: 0.3rem;
            color: var(--light);
        }
        .contact-text p, .contact-text a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .contact-text a:hover {
            color: var(--primary);
        }
        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        .social-link {
            width: 40px;
            height: 40px;
            background: rgba(255, 0, 0, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: var(--light);
            transition: all 0.3s ease;
            text-decoration: none;
        }
        .social-link:hover {
            background: var(--primary);
            transform: translateY(-3px);
        }
        .contact-form {
            background: rgba(26, 26, 26, 0.6);
            border-radius: 16px;
            padding: 2rem;
            border: 1px solid rgba(255, 0, 0, 0.2);
            opacity: 0;
            transform: translateY(30px);
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: rgba(255, 255, 255, 0.9);
        }
        .form-control {
            width: 100%;
            padding: 0.8rem 1rem;
            background: rgba(13, 13, 13, 0.5);
            border: 1px solid rgba(255, 0, 0, 0.3);
            border-radius: 8px;
            color: var(--light);
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 0, 0, 0.2);
        }
        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }
        footer {
            background: var(--darker);
            padding: 5rem 5% 2rem;
            position: relative;
        }
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 3rem;
            margin-bottom: 3rem;
        }
        .footer-column h3 {
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
            color: var(--light);
            position: relative;
            padding-bottom: 0.5rem;
        }
        .footer-column h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 2px;
            background: var(--primary);
        }
        .footer-links {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
        }
        .footer-links a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .footer-links a:hover {
            color: var(--primary);
        }
        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 0, 0, 0.2);
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.9rem;
        }
        .credit {
            position: absolute;
            right: 20px;
            bottom: 0;
            opacity: 0.7;
            font-size: 0.8rem;
        }
        .animate {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s ease;
        }
        .animate.show {
            opacity: 1;
            transform: translateY(0);
        }
        body:not(.loading) .animate {
            opacity: 1;
            transform: translateY(0);
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 10000;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .modal.active {
            display: flex;
            opacity: 1;
        }
        .modal-content {
            background: var(--darker);
            padding: 2rem;
            border-radius: 16px;
            width: 100%;
            max-width: 400px;
            position: relative;
            transform: scale(0.7);
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            border: 1px solid rgba(255, 0, 0, 0.3);
            box-shadow: var(--neon-glow);
        }
        .modal.active .modal-content {
            transform: scale(1);
        }
        .modal-content.slide-out-left {
            animation: slideOutLeft 0.3s forwards;
        }
        .modal-content.slide-out-right {
            animation: slideOutRight 0.3s forwards;
        }
        .modal-content.slide-in-from-left {
            animation: slideInFromLeft 0.3s forwards;
        }
        .modal-content.slide-in-from-right {
            animation: slideInFromRight 0.3s forwards;
        }
        .modal-content.modalOpen {
            animation: modalOpen 0.3s forwards;
        }
        .modal-content.modalClose {
            animation: modalClose 0.3s forwards;
        }
        .modal-close {
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            color: var(--light);
            font-size: 1.5rem;
            cursor: pointer;
            transition: color 0.3s ease;
        }
        .modal-close:hover {
            color: var(--primary);
        }
        .modal-title {
            color: var(--primary);
            font-size: 2rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .auth-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .auth-form .form-group {
            position: relative;
        }
        .auth-form .form-control {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 0, 0, 0.2);
            padding-left: 2.5rem;
        }
        .auth-form .form-control:focus {
            background: rgba(255, 255, 255, 0.1);
        }
        .auth-icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
            font-size: 1.2rem;
        }
        .auth-toggle {
            text-align: center;
            color: rgba(255, 255, 255, 0.7);
            margin-top: 1rem;
        }
        .auth-toggle a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        .auth-toggle a:hover {
            color: #ff5e5e;
        }
        .auth-form .cta-button::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s ease, height 0.6s ease;
        }
        .auth-form .cta-button:hover::after {
            width: 300px;
            height: 300px;
        }
        @media (max-width: 992px) {
            .hero h1 { font-size: 2.8rem; }
            .hero-image { width: 50%; }
            .game-card { flex: 0 0 280px; }
        }
        @media (max-width: 768px) {
            .nav-links {
                position: fixed;
                top: 80px;
                left: -100%;
                width: 80%;
                height: calc(100vh - 80px);
                background: var(--darker);
                flex-direction: column;
                align-items: center;
                justify-content: center;
                gap: 2rem;
                transition: left 0.4s ease;
                border-right: 1px solid rgba(255, 0, 0, 0.2);
            }
            .nav-links.active { left: 0; }
            .menu-toggle { display: block; }
            .hero { flex-direction: column; justify-content: center; text-align: center; padding-top: 100px; }
            .hero-content { max-width: 100%; }
            .hero-buttons { justify-content: center; }
            .hero-image { position: relative; right: auto; bottom: auto; width: 80%; margin-top: 3rem; }
            .pricing-cards { flex-direction: column; align-items: center; }
            .pricing-card { width: 100%; max-width: 400px; }
            .slider-arrow { width: 40px; height: 40px; font-size: 1rem; }
        }
        @media (max-width: 576px) {
            .hero h1 { font-size: 2.2rem; }
            .hero p { font-size: 1rem; }
            .hero-buttons { flex-direction: column; gap: 1rem; }
            .section-title h2 { font-size: 2rem; }
            .game-card { flex: 0 0 260px; }
            .loader-logo { width: 80px; height: 80px; }
            .loader-bar-container { width: 250px; }
            .loader-text { font-size: 1rem; }
            .games-slider { padding: 0 40px; }
        }
        .welcome-text {
            font-family: 'Roboto', sans-serif;
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--light);
        }
    </style>
</head>
<body class="loading">
    <div class="loader">
        <div class="loader-content">
            <img src="logo.png" alt="Loading" class="loader-logo">
            <div class="loader-text">Загрузка...</div>
            <div class="loader-bar-container">
                <div class="loader-progress"></div>
            </div>
            <div class="loader-percentage" id="loaderPercentage">0%</div>
            <div class="loader-dots">
                <div class="loader-dot"></div>
                <div class="loader-dot"></div>
                <div class="loader-dot"></div>
            </div>
        </div>
    </div>
    <header>
        <a href="#" class="logo" id="logo">
            <img src="logo.png" alt="N1ntendo" class="header-logo">
            <span class="logo-text">N1NTENDO</span>
        </a>
        <nav class="nav-links">
            <a href="#home" class="menu-item">Главная</a>
            <a href="#features" class="menu-item">Возможности</a>
            <a href="#games" class="menu-item">Игры</a>
            <a href="#pricing" class="menu-item">Тарифы</a>
            <a href="#contact" class="menu-item">Контакты</a>
        </nav>
        <?php if ($is_logged_in): ?>
            <span class="welcome-text">Привет, <?php echo htmlspecialchars($username); ?>!</span>
            <a href="logout.php" class="cta-button">Выйти</a>
        <?php else: ?>
            <button class="cta-button" id="loginBtn">Войти</button>
        <?php endif; ?>
        <button class="menu-toggle" id="menuToggle">
            <i class="fas fa-bars"></i>
        </button>
    </header>
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
                <button type="submit" class="cta-button">Войти</button>
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
                <button type="submit" class="cta-button">Зарегистрироваться</button>
                <div class="auth-toggle">
                    Уже есть аккаунт? <a href="#" id="showLogin">Войти</a>
                </div>
            </form>
        </div>
    </div>
    <section class="hero" id="home">
        <div class="hero-content">
            <h1>Добро пожаловать в <span>N1ntendo</span></h1>
            <p>Эксклюзивный компьютерный клуб с атмосферой настоящего гейминга и топовым оборудованием</p>
            <div class="hero-buttons">
                <button class="cta-button">Начать сейчас</button>
                <button class="secondary-button">Узнать больше</button>
            </div>
        </div>
        <img src="nintendo.png" alt="Nintendo Club" class="hero-image">
    </section>
    <section class="section" id="features">
        <div class="section-title animate">
            <h2>Наши преимущества</h2>
            <p>Почему геймеры выбирают именно наш клуб</p>
        </div>
        <div class="features-grid">
            <div class="feature-card animate">
                <div class="feature-icon"><i class="fas fa-tachometer-alt"></i></div>
                <h3>Мощные ПК</h3>
                <p>Топовые игровые компьютеры с процессорами последнего поколения и видеокартами NVIDIA RTX</p>
            </div>
            <div class="feature-card animate">
                <div class="feature-icon"><i class="fas fa-network-wired"></i></div>
                <h3>Скоростной интернет</h3>
                <p>Гигабитное подключение с минимальным пингом для комфортной игры в любые онлайн-игры</p>
            </div>
            <div class="feature-card animate">
                <div class="feature-icon"><i class="fas fa-chair"></i></div>
                <h3>Комфорт</h3>
                <p>Эргономичные игровые кресла и профессиональные игровые мониторы с высокой частотой обновления</p>
            </div>
            <div class="feature-card animate">
                <div class="feature-icon"><i class="fas fa-utensils"></i></div>
                <h3>Зона отдыха</h3>
                <p>Уютная лаунж-зона с напитками и снеками, где можно отдохнуть между играми</p>
            </div>
        </div>
    </section>
    <section class="section" id="games">
        <div class="section-title animate">
            <h2>Популярные игры</h2>
            <p>В нашем клубе доступны все самые популярные игры</p>
        </div>
        <div class="games-slider">
            <div class="games-slider-container">
                <div class="slider-arrows">
                    <div class="slider-arrow prev" id="prevBtn"><i class="fas fa-chevron-left"></i></div>
                    <div class="slider-arrow next" id="nextBtn"><i class="fas fa-chevron-right"></i></div>
                </div>
                <div class="games-track" id="gamesTrack">
                    <div class="game-card animate">
                        <div class="game-image-container"><img src="DOTA.png" alt="Dota" class="game-image"></div>
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
                    <div class="game-card animate">
                        <div class="game-image-container"><img src="CS.png" alt="CS" class="game-image"></div>
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
                    <div class="game-card animate">
                        <div class="game-image-container"><img src="GTA.png" alt="GTA" class="game-image"></div>
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
                    <div class="game-card animate">
                        <div class="game-image-container"><img src="RUST.png" alt="RUST" class="game-image"></div>
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
                    <div class="game-card animate">
                        <div class="game-image-container"><img src="PUBG.png" alt="PUBG" class="game-image"></div>
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
                    <div class="game-card animate">
                        <div class="game-image-container"><img src="FORNITE.png" alt="Fortnite" class="game-image"></div>
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
            </div>
            <div class="slider-nav" id="sliderDots"></div>
        </div>
    </section>
    <section class="section" id="pricing">
        <div class="section-title animate">
            <h2>Наши тарифы</h2>
            <p>Выберите подходящий вариант для идеального игрового опыта</p>
        </div>
        <div class="pricing-cards">
            <div class="pricing-card animate">
                <h3>Стандарт</h3>
                <div class="price">600₽ <span>/час</span></div>
                <div class="pricing-features">
                    <div class="pricing-feature"><i class="fas fa-check"></i><span>Доступ к игровым ПК</span></div>
                    <div class="pricing-feature"><i class="fas fa-check"></i><span>Базовые игровые периферии</span></div>
                    <div class="pricing-feature"><i class="fas fa-check"></i><span>5% скидка в кафе</span></div>
                </div>
                <button class="cta-button" style="width: 100%;">Выбрать</button>
            </div>
            <div class="pricing-card popular animate">
                <div class="popular-tag">Популярный</div>
                <h3>Премиум</h3>
                <div class="price">900₽ <span>/час</span></div>
                <div class="pricing-features">
                    <div class="pricing-feature"><i class="fas fa-check"></i><span>Топовые игровые ПК</span></div>
                    <div class="pricing-feature"><i class="fas fa-check"></i><span>Профессиональные мониторы 240Гц</span></div>
                    <div class="pricing-feature"><i class="fas fa-check"></i><span>Премиум периферия</span></div>
                    <div class="pricing-feature"><i class="fas fa-check"></i><span>10% скидка в кафе</span></div>
                </div>
                <button class="cta-button" style="width: 100%;">Выбрать</button>
            </div>
            <div class="pricing-card animate">
                <h3>VIP</h3>
                <div class="price">1500₽ <span>/час</span></div>
                <div class="pricing-features">
                    <div class="pricing-feature"><i class="fas fa-check"></i><span>Эксклюзивные VIP-зоны</span></div>
                    <div class="pricing-feature"><i class="fas fa-check"></i><span>Персонализированные настройки</span></div>
                    <div class="pricing-feature"><i class="fas fa-check"></i><span>Бесплатные напитки</span></div>
                    <div class="pricing-feature"><i class="fas fa-check"></i><span>24/7 доступ</span></div>
                </div>
                <button class="cta-button" style="width: 100%;">Выбрать</button>
            </div>
        </div>
    </section>
    <section class="section" id="contact">
        <div class="section-title animate">
            <h2>Контакты</h2>
            <p>Свяжитесь с нами для бронирования или вопросов</p>
        </div>
        <div class="contact-container">
            <div class="contact-info animate">
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
                <h3 style="margin-top: 2rem;">Мы в соцсетях</h3>
                <div class="social-links">
                    <a href="https://vk.com/nintendonvrsk" class="social-link"><i class="fab fa-vk"></i></a>
                    <a href="https://t.me/n1ntendo_nvrsk" class="social-link"><i class="fab fa-telegram"></i></a>
                    <a href="https://www.youtube.com/watch?v=L2ObPJrRbKw" class="social-link"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            <div class="contact-form animate">
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
                    <button type="submit" class="cta-button" style="width: 100%;">Отправить</button>
                </form>
            </div>
        </div>
    </section>
    <footer>
        <div class="footer-content">
            <div class="footer-column">
                <h3>Nintendo Cyber Club</h3>
                <p>Передовой киберспортивный клуб, где технологии встречаются с комфортом и сообществом единомышленников.</p>
            </div>
            <div class="footer-column">
                <h3>Навигация</h3>
                <ul class="footer-links">
                    <li><a href="#home">Главная</a></li>
                    <li><a href="#features">Возможности</a></li>
                    <li><a href="#games">Игры</a></li>
                    <li><a href="#pricing">Тарифы</a></li>
                    <li><a href="#contact">Контакты</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3>Поддержка</h3>
                <ul class="footer-links">
                    <li><a href="#">FAQ</a></li>
                    <li><a href="#">Правила клуба</a></li>
                    <li><a href="#">Безопасность</a></li>
                    <li><a href="#">Вакансии</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3>Подписаться</h3>
                <p>Будьте в курсе новостей и специальных предложений.</p>
                <div class="footer-subscribe">
                    <input type="email" placeholder="Ваш email" class="form-control" style="margin-bottom: 1rem;">
                    <button class="cta-button">Подписаться</button>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© 2025 Nintendo Cyber Club. Все права защищены.</p>
            <p class="credit">by loaderaw</p>
        </div>
    </footer>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loader = document.querySelector('.loader');
            const percentage = document.getElementById('loaderPercentage');
            const body = document.body;
            let progress = 0;
            const interval = setInterval(() => {
                progress += Math.random() * 10;
                if (progress > 100) progress = 100;
                percentage.textContent = Math.floor(progress) + '%';
                if (progress >= 100) {
                    clearInterval(interval);
                    loader.style.opacity = '0';
                    setTimeout(() => {
                        loader.style.display = 'none';
                        body.classList.remove('loading');
                    }, 500);
                }
            }, 100);
            const loginBtn = document.getElementById('loginBtn');
            const loginModal = document.getElementById('loginModal');
            const registerModal = document.getElementById('registerModal');
            const closeLogin = document.getElementById('closeLogin');
            const closeRegister = document.getElementById('closeRegister');
            const showRegister = document.getElementById('showRegister');
            const showLogin = document.getElementById('showLogin');
            const loginForm = document.getElementById('loginForm');
            const registerForm = document.getElementById('registerForm');
            function openModal(modal, content) {
                modal.classList.add('active');
                content.classList.add('modalOpen');
                setTimeout(() => content.classList.remove('modalOpen'), 300);
                document.body.style.overflow = 'hidden';
            }
            function closeModal(modal, content) {
                content.classList.add('modalClose');
                setTimeout(() => {
                    modal.classList.remove('active');
                    content.classList.remove('modalClose');
                    document.body.style.overflow = 'auto';
                }, 300);
            }
            function switchModal(fromModal, fromContent, toModal, toContent) {
                let slideOutClass, slideInClass;
                if (fromModal.id === 'loginModal' && toModal.id === 'registerModal') {
                    slideOutClass = 'slide-out-left';
                    slideInClass = 'slide-in-from-right';
                } else if (fromModal.id === 'registerModal' && toModal.id === 'loginModal') {
                    slideOutClass = 'slide-out-right';
                    slideInClass = 'slide-in-from-left';
                }
                fromContent.classList.add(slideOutClass);
                setTimeout(() => {
                    fromModal.classList.remove('active');
                    fromContent.classList.remove(slideOutClass);
                    toModal.classList.add('active');
                    toContent.classList.add(slideInClass);
                    setTimeout(() => {
                        toContent.classList.remove(slideInClass);
                    }, 300);
                }, 300);
            }
            loginBtn.addEventListener('click', () => openModal(loginModal, loginModal.querySelector('.modal-content')));
            closeLogin.addEventListener('click', () => closeModal(loginModal, loginModal.querySelector('.modal-content')));
            closeRegister.addEventListener('click', () => closeModal(registerModal, registerModal.querySelector('.modal-content')));
            showRegister.addEventListener('click', (e) => {
                e.preventDefault();
                switchModal(loginModal, loginModal.querySelector('.modal-content'), 
                            registerModal, registerModal.querySelector('.modal-content'));
            });
            showLogin.addEventListener('click', (e) => {
                e.preventDefault();
                switchModal(registerModal, registerModal.querySelector('.modal-content'), 
                            loginModal, loginModal.querySelector('.modal-content'));
            });
            [loginModal, registerModal].forEach(modal => {
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) closeModal(modal, modal.querySelector('.modal-content'));
                });
            });
            registerForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const username = document.getElementById('registerUsername').value;
                const email = document.getElementById('registerEmail').value;
                const password = document.getElementById('registerPassword').value;
                const confirmPassword = document.getElementById('registerConfirmPassword').value;
                if (password !== confirmPassword) {
                    alert('Пароли не совпадают!');
                    return;
                }
                const formData = new FormData();
                formData.append('username', username);
                formData.append('email', email);
                formData.append('password', password);
                formData.append('confirm_password', confirmPassword);
                try {
                    const response = await fetch('register.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();
                    alert(data.message);
                    if (data.success) {
                        closeModal(registerModal, registerModal.querySelector('.modal-content'));
                        registerForm.reset();
                        openModal(loginModal, loginModal.querySelector('.modal-content'));
                    }
                } catch (error) {
                    alert('Ошибка: ' + error.message);
                }
            });
            loginForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const username = document.getElementById('loginUsername').value;
                const password = document.getElementById('loginPassword').value;
                const formData = new FormData();
                formData.append('username', username);
                formData.append('password', password);
                try {
                    const response = await fetch('login.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();
                    alert(data.message);
                    if (data.success) {
                        closeModal(loginModal, loginModal.querySelector('.modal-content'));
                        loginForm.reset();
                        window.location.reload();
                    }
                } catch (error) {
                    alert('Ошибка: ' + error.message);
                }
            });
            const menuToggle = document.getElementById('menuToggle');
            const navLinks = document.querySelector('.nav-links');
            const logo = document.getElementById('logo');
            menuToggle.addEventListener('click', () => {
                navLinks.classList.toggle('active');
                menuToggle.innerHTML = navLinks.classList.contains('active') 
                    ? '<i class="fas fa-times"></i>' 
                    : '<i class="fas fa-bars"></i>';
            });
            logo.addEventListener('click', (e) => {
                e.preventDefault();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (navLinks.classList.contains('active')) {
                        navLinks.classList.remove('active');
                        menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
                    }
                    document.querySelector(this.getAttribute('href')).scrollIntoView({
                        behavior: 'smooth'
                    });
                });
            });
            const animateElements = document.querySelectorAll('.animate');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('show');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1 });
            animateElements.forEach(el => observer.observe(el));
            const gamesTrack = document.getElementById('gamesTrack');
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            const sliderDots = document.getElementById('sliderDots');
            const gameCards = document.querySelectorAll('.game-card');
            let currentSlide = 0;
            let slidesToShow = 3;
            let cardWidth = 330;
            function updateSlider() {
                const offset = -currentSlide * cardWidth;
                gamesTrack.style.transform = `translateX(${offset}px)`;
                updateDots();
                updateArrows();
            }
            function updateDots() {
                const dots = sliderDots.querySelectorAll('.slider-dot');
                dots.forEach((dot, index) => {
                    dot.classList.toggle('active', index === currentSlide);
                });
            }
            function updateArrows() {
                prevBtn.style.visibility = currentSlide === 0 ? 'hidden' : 'visible';
                nextBtn.style.visibility = currentSlide >= gameCards.length - slidesToShow ? 'hidden' : 'visible';
            }
            function calculateSlidesToShow() {
                const containerWidth = document.querySelector('.games-slider').offsetWidth;
                slidesToShow = Math.min(Math.floor(containerWidth / cardWidth), gameCards.length);
                sliderDots.innerHTML = '';
                const dotsCount = Math.max(gameCards.length - slidesToShow + 1, 1);
                for (let i = 0; i < dotsCount; i++) {
                    const dot = document.createElement('div');
                    dot.classList.add('slider-dot');
                    if (i === currentSlide) dot.classList.add('active');
                    dot.addEventListener('click', () => {
                        currentSlide = i;
                        updateSlider();
                    });
                    sliderDots.appendChild(dot);
                }
                updateSlider();
            }
            prevBtn.addEventListener('click', () => {
                if (currentSlide > 0) {
                    currentSlide--;
                    updateSlider();
                }
            });
            nextBtn.addEventListener('click', () => {
                if (currentSlide < gameCards.length - slidesToShow) {
                    currentSlide++;
                    updateSlider();
                }
            });
            window.addEventListener('load', calculateSlidesToShow);
            window.addEventListener('resize', calculateSlidesToShow);
            const contactForm = document.getElementById('contactForm');
            contactForm.addEventListener('submit', (e) => {
                e.preventDefault();
                alert('Спасибо! Ваше сообщение отправлено.');
                contactForm.reset();
            });
            window.addEventListener('scroll', () => {
                const header = document.querySelector('header');
                if (window.scrollY > 50) {
                    header.style.padding = '1rem 5%';
                    header.style.background = 'rgba(7, 7, 14, 0.95)';
                } else {
                    header.style.padding = '1.5rem 5%';
                    header.style.background = 'rgba(15, 15, 26, 0.8)';
                }
            });
        });
    </script>
</body>
</html></DOCUMENT>