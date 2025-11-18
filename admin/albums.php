<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

$page_title = 'إدارة الألبومات';
$success = '';
$error = '';

$conn = getConnection();

// Handle actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];

    // Delete album
    if ($action == 'delete' && isset($_GET['id'])) {
        $id = intval($_GET['id']);

        // Get album cover image to delete
        $stmt = $conn->prepare("SELECT cover_image FROM albums WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $album = $result->fetch_assoc();

        if ($album && $album['cover_image']) {
            deleteFile('../' . $album['cover_image']);
        }

        $stmt = $conn->prepare("DELETE FROM albums WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success = 'تم حذف الألبوم بنجاح';
        } else {
            $error = 'حدث خطأ أثناء الحذف';
        }
        $stmt->close();
    }

    // Toggle status
    if ($action == 'toggle' && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = $conn->prepare("UPDATE albums SET is_active = NOT is_active WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success = 'تم تحديث حالة الألبوم بنجاح';
        }
        $stmt->close();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $institution_id = intval($_POST['institution_id']);
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $display_order = intval($_POST['display_order']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $cover_image = '';

    // Handle cover image upload
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
        $upload_result = uploadFile($_FILES['cover_image'], '../uploads/images', ['jpg', 'jpeg', 'png', 'gif']);
        if ($upload_result['success']) {
            $cover_image = 'uploads/images/' . $upload_result['file_name'];
        } else {
            $error = $upload_result['message'];
        }
    }

    if (empty($title) || $institution_id == 0) {
        $error = 'يرجى إدخال جميع الحقول المطلوبة';
    } else {
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            // Update
            $id = intval($_POST['id']);

            if ($cover_image) {
                // Delete old cover image
                $stmt = $conn->prepare("SELECT cover_image FROM albums WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $old_album = $result->fetch_assoc();
                if ($old_album && $old_album['cover_image']) {
                    deleteFile('../' . $old_album['cover_image']);
                }

                $stmt = $conn->prepare("UPDATE albums SET institution_id = ?, title = ?, description = ?, cover_image = ?, display_order = ?, is_active = ? WHERE id = ?");
                $stmt->bind_param("issssii", $institution_id, $title, $description, $cover_image, $display_order, $is_active, $id);
            } else {
                $stmt = $conn->prepare("UPDATE albums SET institution_id = ?, title = ?, description = ?, display_order = ?, is_active = ? WHERE id = ?");
                $stmt->bind_param("issiii", $institution_id, $title, $description, $display_order, $is_active, $id);
            }

            if ($stmt->execute()) {
                $success = 'تم تحديث الألبوم بنجاح';
            } else {
                $error = 'حدث خطأ أثناء التحديث';
            }
            $stmt->close();
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO albums (institution_id, title, description, cover_image, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssii", $institution_id, $title, $description, $cover_image, $display_order, $is_active);

            if ($stmt->execute()) {
                $success = 'تم إضافة الألبوم بنجاح';
            } else {
                $error = 'حدث خطأ أثناء الإضافة';
            }
            $stmt->close();
        }
    }
}

// Get album for edit
$edit_album = null;
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM albums WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_album = $result->fetch_assoc();
    $stmt->close();
}

// Get all institutions for dropdown
$institutions = $conn->query("SELECT id, name FROM institutions WHERE is_active = 1 ORDER BY name");

// Get all albums with institution info
$albums = $conn->query("
    SELECT a.*, i.name as institution_name,
           (SELECT COUNT(*) FROM media WHERE album_id = a.id) as media_count
    FROM albums a
    JOIN institutions i ON a.institution_id = i.id
    ORDER BY a.created_at DESC
");

include 'header.php';
?>

<div class="page-header">
    <h1 class="page-title">إدارة الألبومات</h1>
    <p class="page-subtitle">إضافة وتعديل وحذف الألبومات الخاصة بالمؤسسات</p>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<!-- Add/Edit Form -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <?php echo $edit_album ? 'تعديل الألبوم' : 'إضافة ألبوم جديد'; ?>
        </h3>
    </div>
    <div class="card-body">
        <form method="POST" action="" enctype="multipart/form-data">
            <?php if ($edit_album): ?>
                <input type="hidden" name="id" value="<?php echo $edit_album['id']; ?>">
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">المؤسسة *</label>
                    <select name="institution_id" class="form-control" required>
                        <option value="">-- اختر المؤسسة --</option>
                        <?php while ($inst = $institutions->fetch_assoc()): ?>
                            <option value="<?php echo $inst['id']; ?>"
                                    <?php echo ($edit_album && $edit_album['institution_id'] == $inst['id']) ? 'selected' : ''; ?>>
                                <?php echo $inst['name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">عنوان الألبوم *</label>
                    <input type="text" name="title" class="form-control" placeholder="مثال: فعاليات 2024"
                           value="<?php echo $edit_album['title'] ?? ''; ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">ترتيب العرض</label>
                    <input type="number" name="display_order" class="form-control" value="<?php echo $edit_album['display_order'] ?? 0; ?>">
                    <small style="color: var(--gray-500);">الألبومات ذات الترتيب الأقل تظهر أولاً</small>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">الوصف</label>
                <textarea name="description" class="form-control" rows="3" placeholder="وصف مختصر عن الألبوم"><?php echo $edit_album['description'] ?? ''; ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">صورة الغلاف</label>
                <input type="file" name="cover_image" class="form-control" accept="image/*" onchange="previewImage(this, 'cover_preview')">
                <?php if ($edit_album && $edit_album['cover_image']): ?>
                    <img id="cover_preview" src="../<?php echo $edit_album['cover_image']; ?>" alt="Cover" style="max-width: 200px; margin-top: 1rem; border-radius: 0.5rem;">
                <?php else: ?>
                    <img id="cover_preview" src="" alt="Preview" style="max-width: 200px; margin-top: 1rem; border-radius: 0.5rem; display: none;">
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label style="display: flex; align-items: center; cursor: pointer;">
                    <input type="checkbox" name="is_active" value="1"
                           <?php echo (!$edit_album || $edit_album['is_active']) ? 'checked' : ''; ?>
                           style="margin-left: 0.5rem;">
                    <span>تفعيل الألبوم</span>
                </label>
            </div>

            <div style="display: flex; gap: 1rem;">
                <button type="submit" class="btn btn-primary">
                    <?php echo $edit_album ? '💾 تحديث' : '➕ إضافة'; ?>
                </button>
                <?php if ($edit_album): ?>
                    <a href="albums.php" class="btn btn-light">إلغاء</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Albums List -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">قائمة الألبومات (<?php echo $albums->num_rows; ?>)</h3>
    </div>
    <div class="card-body">
        <?php if ($albums->num_rows > 0): ?>
            <div style="overflow-x: auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>الصورة</th>
                            <th>عنوان الألبوم</th>
                            <th>المؤسسة</th>
                            <th>عدد الوسائط</th>
                            <th>الحالة</th>
                            <th>التاريخ</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($album = $albums->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $album['id']; ?></td>
                                <td>
                                    <?php if ($album['cover_image']): ?>
                                        <img src="../<?php echo $album['cover_image']; ?>" alt="Cover" style="width: 50px; height: 50px; object-fit: cover; border-radius: 0.375rem;">
                                    <?php else: ?>
                                        <div style="width: 50px; height: 50px; background: var(--gray-200); border-radius: 0.375rem; display: flex; align-items: center; justify-content: center;">📁</div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo $album['title']; ?></strong></td>
                                <td><?php echo $album['institution_name']; ?></td>
                                <td>
                                    <span class="badge badge-primary"><?php echo $album['media_count']; ?> ملف</span>
                                </td>
                                <td>
                                    <?php if ($album['is_active']): ?>
                                        <span class="badge badge-success">نشط</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">غير نشط</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo timeAgo($album['created_at']); ?></td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <a href="media.php?album=<?php echo $album['id']; ?>" class="btn btn-sm btn-light" title="عرض الوسائط">👁️</a>
                                        <a href="?edit=<?php echo $album['id']; ?>" class="btn btn-sm btn-light" title="تعديل">✏️</a>
                                        <a href="?action=toggle&id=<?php echo $album['id']; ?>" class="btn btn-sm btn-light" title="تفعيل/تعطيل">
                                            <?php echo $album['is_active'] ? '🔓' : '🔒'; ?>
                                        </a>
                                        <a href="?action=delete&id=<?php echo $album['id']; ?>"
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirmDelete('هل أنت متأكد من حذف هذا الألبوم؟ سيتم حذف جميع الوسائط المرتبطة به.')"
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
                لا توجد ألبومات حتى الآن
            </p>
        <?php endif; ?>
    </div>
</div>

<?php
closeConnection($conn);
include 'footer.php';
?>
