<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Redirect if already logged in
if (isInstitutionLoggedIn()) {
    redirect('gallery.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $access_code = sanitize($_POST['access_code']);

    if (empty($access_code)) {
        $error = 'يرجى إدخال رمز الدخول';
    } else {
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT id, name, access_code FROM institutions WHERE access_code = ? AND is_active = 1");
        $stmt->bind_param("s", $access_code);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $institution = $result->fetch_assoc();

            $_SESSION['institution_id'] = $institution['id'];
            $_SESSION['institution_name'] = $institution['name'];
            $_SESSION['institution_code'] = $institution['access_code'];

            redirect('gallery.php');
        } else {
            $error = 'رمز الدخول غير صحيح أو المؤسسة غير نشطة';
        }

        $stmt->close();
        closeConnection($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دخول المؤسسات - معرض الوسائط</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-logo">
                <h1>📸 معرض الوسائط</h1>
                <p>الدخول إلى صفحة المؤسسة</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">رمز الدخول الخاص بمؤسستك</label>
                    <input type="text" name="access_code" class="form-control"
                           placeholder="أدخل رمز الدخول"
                           style="text-align: center; font-size: 1.5rem; font-weight: 600; letter-spacing: 2px;"
                           required autofocus>
                </div>

                <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                    🔓 دخول
                </button>
            </form>

            <div style="margin-top: 2rem; padding: 1rem; background: var(--light); border-radius: 0.5rem; text-align: center;">
                <p style="color: var(--gray-600); font-size: 0.875rem; margin: 0;">
                    💡 إذا لم يكن لديك رمز دخول، يرجى التواصل مع الإدارة
                </p>
            </div>
        </div>
    </div>
</body>
</html>
