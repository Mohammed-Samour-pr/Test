<?php
// تفعيل عرض الأخطاء للتشخيص
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

$page_title = 'لوحة التحكم الرئيسية';

// Get statistics
$conn = getConnection();

// Count institutions
$result = $conn->query("SELECT COUNT(*) as count FROM institutions");
$institutions_count = $result->fetch_assoc()['count'];

// Count albums
$result = $conn->query("SELECT COUNT(*) as count FROM albums");
$albums_count = $result->fetch_assoc()['count'];

// Count media files
$result = $conn->query("SELECT COUNT(*) as count FROM media");
$media_count = $result->fetch_assoc()['count'];

// Count active institutions
$result = $conn->query("SELECT COUNT(*) as count FROM institutions WHERE is_active = 1");
$active_institutions = $result->fetch_assoc()['count'];

// Get recent institutions
$recent_institutions = $conn->query("SELECT * FROM institutions ORDER BY created_at DESC LIMIT 5");

// Get recent albums
$recent_albums = $conn->query("
    SELECT a.*, i.name as institution_name
    FROM albums a
    JOIN institutions i ON a.institution_id = i.id
    ORDER BY a.created_at DESC
    LIMIT 5
");

include 'header.php';
?>

<div class="page-header">
    <h1 class="page-title">مرحباً، <?php echo $_SESSION['admin_name']; ?>!</h1>
    <p class="page-subtitle">إليك نظرة عامة على نظام إدارة الوسائط</p>
</div>

<!-- Statistics Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    <div class="stats-card">
        <div class="stats-number"><?php echo $institutions_count; ?></div>
        <div class="stats-label">إجمالي المؤسسات</div>
    </div>
    <div class="stats-card success">
        <div class="stats-number"><?php echo $active_institutions; ?></div>
        <div class="stats-label">المؤسسات النشطة</div>
    </div>
    <div class="stats-card warning">
        <div class="stats-number"><?php echo $albums_count; ?></div>
        <div class="stats-label">إجمالي الألبومات</div>
    </div>
    <div class="stats-card info">
        <div class="stats-number"><?php echo $media_count; ?></div>
        <div class="stats-label">إجمالي الوسائط</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(500px, 1fr)); gap: 1.5rem;">
    <!-- Recent Institutions -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">المؤسسات الحديثة</h3>
            <a href="institutions.php" class="btn btn-sm btn-light">عرض الكل</a>
        </div>
        <div class="card-body">
            <?php if ($recent_institutions->num_rows > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>اسم المؤسسة</th>
                            <th>رمز الدخول</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($inst = $recent_institutions->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo $inst['name']; ?></strong></td>
                                <td><code><?php echo $inst['access_code']; ?></code></td>
                                <td>
                                    <?php if ($inst['is_active']): ?>
                                        <span class="badge badge-success">نشط</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">غير نشط</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: var(--gray-500);">لا توجد مؤسسات</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Albums -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">الألبومات الحديثة</h3>
            <a href="albums.php" class="btn btn-sm btn-light">عرض الكل</a>
        </div>
        <div class="card-body">
            <?php if ($recent_albums->num_rows > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>اسم الألبوم</th>
                            <th>المؤسسة</th>
                            <th>التاريخ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($album = $recent_albums->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo $album['title']; ?></strong></td>
                                <td><?php echo $album['institution_name']; ?></td>
                                <td><?php echo timeAgo($album['created_at']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: var(--gray-500);">لا توجد ألبومات</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card" style="margin-top: 2rem;">
    <div class="card-header">
        <h3 class="card-title">إجراءات سريعة</h3>
    </div>
    <div class="card-body">
        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <a href="institutions.php?action=add" class="btn btn-primary">
                ➕ إضافة مؤسسة جديدة
            </a>
            <a href="albums.php?action=add" class="btn btn-success">
                📁 إنشاء ألبوم جديد
            </a>
            <a href="media.php?action=upload" class="btn btn-info">
                📤 رفع وسائط
            </a>
        </div>
    </div>
</div>

<?php
closeConnection($conn);
include 'footer.php';
?>
