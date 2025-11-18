<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

$page_title = 'إدارة المؤسسات';
$success = '';
$error = '';

$conn = getConnection();

// Handle actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];

    // Delete institution
    if ($action == 'delete' && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = $conn->prepare("DELETE FROM institutions WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success = 'تم حذف المؤسسة بنجاح';
        } else {
            $error = 'حدث خطأ أثناء الحذف';
        }
        $stmt->close();
    }

    // Toggle status
    if ($action == 'toggle' && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = $conn->prepare("UPDATE institutions SET is_active = NOT is_active WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success = 'تم تحديث حالة المؤسسة بنجاح';
        }
        $stmt->close();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['name']);
    $access_code = sanitize($_POST['access_code']);
    $description = sanitize($_POST['description']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($name) || empty($access_code)) {
        $error = 'يرجى إدخال جميع الحقول المطلوبة';
    } else {
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            // Update
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("UPDATE institutions SET name = ?, access_code = ?, description = ?, is_active = ? WHERE id = ?");
            $stmt->bind_param("sssii", $name, $access_code, $description, $is_active, $id);

            if ($stmt->execute()) {
                $success = 'تم تحديث المؤسسة بنجاح';
            } else {
                if ($conn->errno == 1062) {
                    $error = 'رمز الدخول مستخدم بالفعل';
                } else {
                    $error = 'حدث خطأ أثناء التحديث';
                }
            }
            $stmt->close();
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO institutions (name, access_code, description, is_active) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $name, $access_code, $description, $is_active);

            if ($stmt->execute()) {
                $success = 'تم إضافة المؤسسة بنجاح';
            } else {
                if ($conn->errno == 1062) {
                    $error = 'رمز الدخول مستخدم بالفعل';
                } else {
                    $error = 'حدث خطأ أثناء الإضافة';
                }
            }
            $stmt->close();
        }
    }
}

// Get institution for edit
$edit_institution = null;
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM institutions WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_institution = $result->fetch_assoc();
    $stmt->close();
}

// Get all institutions
$institutions = $conn->query("SELECT *, (SELECT COUNT(*) FROM albums WHERE institution_id = institutions.id) as albums_count FROM institutions ORDER BY created_at DESC");

include 'header.php';
?>

<div class="page-header">
    <h1 class="page-title">إدارة المؤسسات</h1>
    <p class="page-subtitle">إضافة وتعديل وحذف المؤسسات المسجلة في النظام</p>
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
            <?php echo $edit_institution ? 'تعديل المؤسسة' : 'إضافة مؤسسة جديدة'; ?>
        </h3>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <?php if ($edit_institution): ?>
                <input type="hidden" name="id" value="<?php echo $edit_institution['id']; ?>">
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">اسم المؤسسة *</label>
                    <input type="text" name="name" class="form-control" placeholder="مثال: مؤسسة الأمل التعليمية"
                           value="<?php echo $edit_institution['name'] ?? ''; ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">رمز الدخول *</label>
                    <input type="text" name="access_code" class="form-control" placeholder="مثال: AMAL2024"
                           value="<?php echo $edit_institution['access_code'] ?? ''; ?>" required>
                    <small style="color: var(--gray-500);">رمز خاص للدخول إلى صفحة المؤسسة</small>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">الوصف</label>
                <textarea name="description" class="form-control" rows="3" placeholder="وصف مختصر عن المؤسسة"><?php echo $edit_institution['description'] ?? ''; ?></textarea>
            </div>

            <div class="form-group">
                <label style="display: flex; align-items: center; cursor: pointer;">
                    <input type="checkbox" name="is_active" value="1"
                           <?php echo (!$edit_institution || $edit_institution['is_active']) ? 'checked' : ''; ?>
                           style="margin-left: 0.5rem;">
                    <span>تفعيل المؤسسة</span>
                </label>
            </div>

            <div style="display: flex; gap: 1rem;">
                <button type="submit" class="btn btn-primary">
                    <?php echo $edit_institution ? '💾 تحديث' : '➕ إضافة'; ?>
                </button>
                <?php if ($edit_institution): ?>
                    <a href="institutions.php" class="btn btn-light">إلغاء</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Institutions List -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">قائمة المؤسسات (<?php echo $institutions->num_rows; ?>)</h3>
    </div>
    <div class="card-body">
        <?php if ($institutions->num_rows > 0): ?>
            <div style="overflow-x: auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>اسم المؤسسة</th>
                            <th>رمز الدخول</th>
                            <th>عدد الألبومات</th>
                            <th>الحالة</th>
                            <th>تاريخ الإضافة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($inst = $institutions->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $inst['id']; ?></td>
                                <td><strong><?php echo $inst['name']; ?></strong></td>
                                <td>
                                    <code style="background: var(--gray-100); padding: 0.25rem 0.5rem; border-radius: 0.25rem;">
                                        <?php echo $inst['access_code']; ?>
                                    </code>
                                </td>
                                <td>
                                    <span class="badge badge-primary"><?php echo $inst['albums_count']; ?> ألبوم</span>
                                </td>
                                <td>
                                    <?php if ($inst['is_active']): ?>
                                        <span class="badge badge-success">نشط</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">غير نشط</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('Y-m-d', strtotime($inst['created_at'])); ?></td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <a href="?edit=<?php echo $inst['id']; ?>" class="btn btn-sm btn-light" title="تعديل">✏️</a>
                                        <a href="?action=toggle&id=<?php echo $inst['id']; ?>" class="btn btn-sm btn-light" title="تفعيل/تعطيل">
                                            <?php echo $inst['is_active'] ? '🔓' : '🔒'; ?>
                                        </a>
                                        <a href="?action=delete&id=<?php echo $inst['id']; ?>"
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirmDelete('هل أنت متأكد من حذف هذه المؤسسة؟ سيتم حذف جميع الألبومات والوسائط المرتبطة بها.')"
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
                لا توجد مؤسسات مسجلة حتى الآن
            </p>
        <?php endif; ?>
    </div>
</div>

<?php
closeConnection($conn);
include 'footer.php';
?>
