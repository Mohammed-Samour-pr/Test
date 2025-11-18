<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isInstitutionLoggedIn()) {
    redirect('login.php');
}

$conn = getConnection();
$institution_id = $_SESSION['institution_id'];

// Get institution info
$stmt = $conn->prepare("SELECT * FROM institutions WHERE id = ?");
$stmt->bind_param("i", $institution_id);
$stmt->execute();
$institution = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get all active albums for this institution
$stmt = $conn->prepare("
    SELECT a.*, (SELECT COUNT(*) FROM media WHERE album_id = a.id) as media_count
    FROM albums a
    WHERE a.institution_id = ? AND a.is_active = 1
    ORDER BY a.display_order ASC, a.created_at DESC
");
$stmt->bind_param("i", $institution_id);
$stmt->execute();
$albums = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>معرض الألبومات - <?php echo $institution['name']; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .client-header {
            background: linear-gradient(135deg, var(--primary) 0%, #7239EA 100%);
            color: white;
            padding: 2rem;
            margin-bottom: 2rem;
            border-radius: 0.625rem;
        }
        .client-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        .client-header p {
            opacity: 0.9;
        }
        .logout-btn {
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <a href="logout.php" class="btn btn-danger logout-btn">🚪 تسجيل الخروج</a>

    <div class="container" style="padding: 2rem 15px;">
        <div class="client-header">
            <h1>🎨 <?php echo $institution['name']; ?></h1>
            <p><?php echo $institution['description'] ?: 'مرحباً بك في معرض الوسائط الخاص بمؤسستك'; ?></p>
        </div>

        <?php if ($albums->num_rows > 0): ?>
            <div style="margin-bottom: 1.5rem;">
                <h2 style="font-size: 1.5rem; color: var(--gray-900); margin-bottom: 0.5rem;">
                    📁 الألبومات (<?php echo $albums->num_rows; ?>)
                </h2>
                <p style="color: var(--gray-600);">
                    اضغط على أي ألبوم لعرض محتوياته
                </p>
            </div>

            <div class="gallery-grid">
                <?php while ($album = $albums->fetch_assoc()): ?>
                    <a href="album.php?id=<?php echo $album['id']; ?>" style="text-decoration: none; color: inherit;">
                        <div class="gallery-item">
                            <?php if ($album['cover_image']): ?>
                                <img src="../<?php echo $album['cover_image']; ?>" alt="<?php echo $album['title']; ?>" class="gallery-item-image">
                            <?php else: ?>
                                <div class="gallery-item-image" style="background: linear-gradient(135deg, var(--primary) 0%, var(--info) 100%); display: flex; align-items: center; justify-content: center; font-size: 3rem;">
                                    📁
                                </div>
                            <?php endif; ?>

                            <div class="gallery-item-content">
                                <h3 class="gallery-item-title"><?php echo $album['title']; ?></h3>

                                <?php if ($album['description']): ?>
                                    <p class="gallery-item-description"><?php echo $album['description']; ?></p>
                                <?php endif; ?>

                                <div class="gallery-item-footer">
                                    <span style="color: var(--gray-600); font-size: 0.875rem;">
                                        📷 <?php echo $album['media_count']; ?> ملف
                                    </span>
                                    <span style="color: var(--primary); font-weight: 600;">
                                        عرض ←
                                    </span>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body" style="padding: 4rem 2rem; text-align: center;">
                    <div style="font-size: 4rem; margin-bottom: 1rem;">📭</div>
                    <h3 style="color: var(--gray-700); margin-bottom: 0.5rem;">لا توجد ألبومات حالياً</h3>
                    <p style="color: var(--gray-500);">سيتم إضافة الألبومات قريباً</p>
                </div>
            </div>
        <?php endif; ?>

        <div style="margin-top: 3rem; text-align: center; color: var(--gray-500); font-size: 0.875rem;">
            <p>جميع الحقوق محفوظة © 2024</p>
        </div>
    </div>
</body>
</html>
<?php closeConnection($conn); ?>
