<?php
if (!isAdminLoggedIn()) {
    redirect('login.php');
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'لوحة التحكم'; ?> - إدارة الوسائط</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .stats-card {
            background: linear-gradient(135deg, var(--primary) 0%, #0095e8 100%);
            color: white;
            padding: 2rem;
            border-radius: 0.625rem;
            margin-bottom: 1.5rem;
        }
        .stats-card.success {
            background: linear-gradient(135deg, var(--success) 0%, #47be7d 100%);
        }
        .stats-card.warning {
            background: linear-gradient(135deg, var(--warning) 0%, #f1bc00 100%);
        }
        .stats-card.info {
            background: linear-gradient(135deg, var(--info) 0%, #6633d4 100%);
        }
        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .stats-label {
            font-size: 1rem;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-logo">
            <h2>🎨 إدارة الوسائط</h2>
        </div>
        <ul class="sidebar-menu">
            <li>
                <a href="index.php" class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                    <span class="sidebar-menu-icon">📊</span>
                    <span>الرئيسية</span>
                </a>
            </li>
            <li>
                <a href="institutions.php" class="<?php echo $current_page == 'institutions.php' ? 'active' : ''; ?>">
                    <span class="sidebar-menu-icon">🏢</span>
                    <span>المؤسسات</span>
                </a>
            </li>
            <li>
                <a href="albums.php" class="<?php echo $current_page == 'albums.php' ? 'active' : ''; ?>">
                    <span class="sidebar-menu-icon">📁</span>
                    <span>الألبومات</span>
                </a>
            </li>
            <li>
                <a href="media.php" class="<?php echo $current_page == 'media.php' ? 'active' : ''; ?>">
                    <span class="sidebar-menu-icon">🖼️</span>
                    <span>الوسائط</span>
                </a>
            </li>
            <li>
                <a href="logout.php">
                    <span class="sidebar-menu-icon">🚪</span>
                    <span>تسجيل الخروج</span>
                </a>
            </li>
        </ul>
        <div style="padding: 1.5rem; color: var(--gray-400); font-size: 0.875rem; border-top: 1px solid rgba(255, 255, 255, 0.1); margin-top: auto;">
            <p><strong>المسؤول:</strong></p>
            <p><?php echo $_SESSION['admin_name']; ?></p>
        </div>
    </div>

    <div class="main-content">
