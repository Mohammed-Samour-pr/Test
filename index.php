<?php
// Main landing page - redirect to appropriate login

session_start();
require_once 'includes/functions.php';

// Check if admin is logged in
if (isAdminLoggedIn()) {
    header("Location: admin/index.php");
    exit();
}

// Check if institution is logged in
if (isInstitutionLoggedIn()) {
    header("Location: client/gallery.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام إدارة الوسائط</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .landing-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary) 0%, #7239EA 100%);
        }
        .landing-card {
            width: 100%;
            max-width: 600px;
            padding: 3rem;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            text-align: center;
        }
        .landing-logo {
            font-size: 5rem;
            margin-bottom: 1rem;
        }
        .landing-title {
            font-size: 2.5rem;
            color: var(--gray-900);
            margin-bottom: 1rem;
        }
        .landing-subtitle {
            font-size: 1.1rem;
            color: var(--gray-600);
            margin-bottom: 2rem;
        }
        .landing-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 2rem;
        }
        .landing-btn {
            padding: 2rem;
            background: var(--gray-100);
            border: 2px solid var(--gray-200);
            border-radius: 0.625rem;
            text-decoration: none;
            color: var(--gray-800);
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .landing-btn:hover {
            background: white;
            border-color: var(--primary);
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        .landing-btn-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        .landing-btn-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .landing-btn-desc {
            font-size: 0.875rem;
            color: var(--gray-500);
        }
        @media (max-width: 767px) {
            .landing-buttons {
                grid-template-columns: 1fr;
            }
            .landing-card {
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="landing-container">
        <div class="landing-card">
            <div class="landing-logo">🎨</div>
            <h1 class="landing-title">نظام إدارة الوسائط</h1>
            <p class="landing-subtitle">منصة متكاملة لإدارة وعرض الصور والفيديوهات للمؤسسات</p>

            <div class="landing-buttons">
                <a href="admin/login.php" class="landing-btn">
                    <div class="landing-btn-icon">👨‍💼</div>
                    <div class="landing-btn-title">لوحة التحكم</div>
                    <div class="landing-btn-desc">دخول المسؤولين</div>
                </a>

                <a href="client/login.php" class="landing-btn">
                    <div class="landing-btn-icon">🏢</div>
                    <div class="landing-btn-title">معرض الوسائط</div>
                    <div class="landing-btn-desc">دخول المؤسسات</div>
                </a>
            </div>

            <div style="margin-top: 3rem; padding-top: 2rem; border-top: 1px solid var(--gray-200);">
                <p style="color: var(--gray-500); font-size: 0.875rem;">
                    جميع الحقوق محفوظة © 2024
                </p>
            </div>
        </div>
    </div>
</body>
</html>
