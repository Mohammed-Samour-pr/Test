<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

$page_title = 'إدارة الوسائط';
$success = '';
$error = '';

$conn = getConnection();

// Handle actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];

    // Delete media
    if ($action == 'delete' && isset($_GET['id'])) {
        $id = intval($_GET['id']);

        // Get media file path to delete
        $stmt = $conn->prepare("SELECT file_path FROM media WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $media = $result->fetch_assoc();

        if ($media) {
            deleteFile('../' . $media['file_path']);
        }

        $stmt = $conn->prepare("DELETE FROM media WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success = 'تم حذف الملف بنجاح';
        } else {
            $error = 'حدث خطأ أثناء الحذف';
        }
        $stmt->close();
    }
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload'])) {
    $album_id = intval($_POST['album_id']);
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $display_order = intval($_POST['display_order']);

    if ($album_id == 0) {
        $error = 'يرجى اختيار الألبوم';
    } elseif (!isset($_FILES['media_files'])) {
        $error = 'يرجى اختيار ملف واحد على الأقل';
    } else {
        $files = $_FILES['media_files'];
        $uploaded_count = 0;
        $failed_count = 0;

        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] == 0) {
                $file = [
                    'name' => $files['name'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'size' => $files['size'][$i],
                    'error' => $files['error'][$i]
                ];

                $upload_result = uploadFile($file, '../uploads/' . ($files['type'][$i] == 'video' ? 'videos' : 'images'));

                if ($upload_result['success']) {
                    $file_name = $upload_result['file_name'];
                    $file_path = $upload_result['file_path'];
                    $file_size = $upload_result['file_size'];
                    $file_type = $upload_result['file_type'];

                    $stmt = $conn->prepare("INSERT INTO media (album_id, file_name, file_path, file_type, file_size, title, description, display_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("isssissi", $album_id, $file_name, $file_path, $file_type, $file_size, $title, $description, $display_order);

                    if ($stmt->execute()) {
                        $uploaded_count++;
                    } else {
                        $failed_count++;
                    }
                    $stmt->close();
                } else {
                    $failed_count++;
                }
            }
        }

        if ($uploaded_count > 0) {
            $success = "تم رفع $uploaded_count ملف بنجاح";
        }
        if ($failed_count > 0) {
            $error = "فشل رفع $failed_count ملف";
        }
    }
}

// Get filter parameters
$filter_album = isset($_GET['album']) ? intval($_GET['album']) : 0;
$filter_type = isset($_GET['type']) ? $_GET['type'] : '';

// Build query
$query = "
    SELECT m.*, a.title as album_title, i.name as institution_name
    FROM media m
    JOIN albums a ON m.album_id = a.id
    JOIN institutions i ON a.institution_id = i.id
    WHERE 1=1
";

if ($filter_album > 0) {
    $query .= " AND m.album_id = $filter_album";
}

if ($filter_type == 'image' || $filter_type == 'video') {
    $query .= " AND m.file_type = '$filter_type'";
}

$query .= " ORDER BY m.created_at DESC";

$media_files = $conn->query($query);

// Get all albums for dropdown
$albums = $conn->query("
    SELECT a.id, a.title, i.name as institution_name
    FROM albums a
    JOIN institutions i ON a.institution_id = i.id
    WHERE a.is_active = 1
    ORDER BY i.name, a.title
");

include 'header.php';
?>

<div class="page-header">
    <h1 class="page-title">إدارة الوسائط</h1>
    <p class="page-subtitle">رفع وإدارة الصور والفيديوهات</p>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<!-- Upload Form -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">📤 رفع ملفات جديدة</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="upload" value="1">

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">الألبوم *</label>
                    <select name="album_id" class="form-control" required>
                        <option value="">-- اختر الألبوم --</option>
                        <?php
                        $albums_copy = $conn->query("
                            SELECT a.id, a.title, i.name as institution_name
                            FROM albums a
                            JOIN institutions i ON a.institution_id = i.id
                            WHERE a.is_active = 1
                            ORDER BY i.name, a.title
                        ");
                        while ($alb = $albums_copy->fetch_assoc()):
                        ?>
                            <option value="<?php echo $alb['id']; ?>" <?php echo ($filter_album == $alb['id']) ? 'selected' : ''; ?>>
                                <?php echo $alb['institution_name'] . ' - ' . $alb['title']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">العنوان (اختياري)</label>
                    <input type="text" name="title" class="form-control" placeholder="عنوان للملفات المرفوعة">
                </div>

                <div class="form-group">
                    <label class="form-label">ترتيب العرض</label>
                    <input type="number" name="display_order" class="form-control" value="0">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">الوصف (اختياري)</label>
                <textarea name="description" class="form-control" rows="2" placeholder="وصف مختصر"></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">الملفات *</label>
                <input type="file" name="media_files[]" class="form-control" accept="image/*,video/*" multiple required>
                <small style="color: var(--gray-500);">يمكنك اختيار ملفات متعددة (الحد الأقصى: 50 ميجابايت لكل ملف)</small>
            </div>

            <button type="submit" class="btn btn-primary">
                📤 رفع الملفات
            </button>
        </form>
    </div>
</div>

<!-- Filters -->
<div class="card">
    <div class="card-body">
        <form method="GET" action="">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; align-items: end;">
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">تصفية حسب الألبوم</label>
                    <select name="album" class="form-control">
                        <option value="">جميع الألبومات</option>
                        <?php while ($alb = $albums->fetch_assoc()): ?>
                            <option value="<?php echo $alb['id']; ?>" <?php echo ($filter_album == $alb['id']) ? 'selected' : ''; ?>>
                                <?php echo $alb['institution_name'] . ' - ' . $alb['title']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group" style="margin: 0;">
                    <label class="form-label">تصفية حسب النوع</label>
                    <select name="type" class="form-control">
                        <option value="">جميع الأنواع</option>
                        <option value="image" <?php echo ($filter_type == 'image') ? 'selected' : ''; ?>>صور</option>
                        <option value="video" <?php echo ($filter_type == 'video') ? 'selected' : ''; ?>>فيديوهات</option>
                    </select>
                </div>

                <div>
                    <button type="submit" class="btn btn-primary">تصفية</button>
                    <a href="media.php" class="btn btn-light">إعادة تعيين</a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Media Grid -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">الوسائط (<?php echo $media_files->num_rows; ?>)</h3>
    </div>
    <div class="card-body">
        <?php if ($media_files->num_rows > 0): ?>
            <div class="media-grid">
                <?php while ($media = $media_files->fetch_assoc()): ?>
                    <div class="media-item">
                        <?php if ($media['file_type'] == 'image'): ?>
                            <img src="../<?php echo $media['file_path']; ?>" alt="<?php echo $media['title']; ?>">
                        <?php else: ?>
                            <video src="../<?php echo $media['file_path']; ?>" controls style="width: 100%; height: 100%; object-fit: cover;"></video>
                        <?php endif; ?>

                        <div class="media-item-overlay">
                            <div style="display: flex; gap: 0.5rem;">
                                <a href="../<?php echo $media['file_path']; ?>" target="_blank" class="btn btn-sm btn-light" title="عرض">👁️</a>
                                <a href="?action=delete&id=<?php echo $media['id']; ?>&album=<?php echo $filter_album; ?>&type=<?php echo $filter_type; ?>"
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirmDelete('هل أنت متأكد من حذف هذا الملف؟')"
                                   title="حذف">🗑️</a>
                            </div>
                        </div>

                        <div style="position: absolute; top: 0.5rem; right: 0.5rem;">
                            <span class="badge <?php echo $media['file_type'] == 'image' ? 'badge-primary' : 'badge-success'; ?>" style="font-size: 0.7rem;">
                                <?php echo $media['file_type'] == 'image' ? '🖼️ صورة' : '🎥 فيديو'; ?>
                            </span>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- Details Table -->
            <div style="margin-top: 2rem; overflow-x: auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>النوع</th>
                            <th>العنوان</th>
                            <th>الألبوم</th>
                            <th>المؤسسة</th>
                            <th>الحجم</th>
                            <th>التاريخ</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $media_files->data_seek(0); // Reset pointer
                        while ($media = $media_files->fetch_assoc()):
                        ?>
                            <tr>
                                <td><?php echo $media['id']; ?></td>
                                <td>
                                    <span class="badge <?php echo $media['file_type'] == 'image' ? 'badge-primary' : 'badge-success'; ?>">
                                        <?php echo $media['file_type'] == 'image' ? 'صورة' : 'فيديو'; ?>
                                    </span>
                                </td>
                                <td><?php echo $media['title'] ?: 'بدون عنوان'; ?></td>
                                <td><?php echo $media['album_title']; ?></td>
                                <td><?php echo $media['institution_name']; ?></td>
                                <td><?php echo formatFileSize($media['file_size']); ?></td>
                                <td><?php echo timeAgo($media['created_at']); ?></td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <a href="../<?php echo $media['file_path']; ?>" target="_blank" class="btn btn-sm btn-light" title="عرض">👁️</a>
                                        <a href="?action=delete&id=<?php echo $media['id']; ?>&album=<?php echo $filter_album; ?>&type=<?php echo $filter_type; ?>"
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirmDelete('هل أنت متأكد من حذف هذا الملف؟')"
                                           title="حذف">🗑️</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="text-align: center; padding: 2rem; color: var(--gray-500);">
                لا توجد ملفات وسائط حتى الآن
            </p>
        <?php endif; ?>
    </div>
</div>

<?php
closeConnection($conn);
include 'footer.php';
?>
