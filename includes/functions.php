<?php
// Helper Functions

// Sanitize input data
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Check if user is logged in (admin)
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

// Check if institution is logged in
function isInstitutionLoggedIn() {
    return isset($_SESSION['institution_id']) && !empty($_SESSION['institution_id']);
}

// Redirect function
function redirect($url) {
    header("Location: $url");
    exit();
}

// Format file size
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

// Upload file function
function uploadFile($file, $uploadPath, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'avi', 'mov']) {
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];

    // Get file extension
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    // Check if file type is allowed
    if (!in_array($fileExt, $allowedTypes)) {
        return ['success' => false, 'message' => 'نوع الملف غير مسموح به'];
    }

    // Check for upload errors
    if ($fileError !== 0) {
        return ['success' => false, 'message' => 'حدث خطأ أثناء رفع الملف'];
    }

    // Check file size (max 50MB)
    if ($fileSize > 50 * 1024 * 1024) {
        return ['success' => false, 'message' => 'حجم الملف كبير جداً (الحد الأقصى 50 ميجابايت)'];
    }

    // Generate unique file name
    $newFileName = uniqid('', true) . '.' . $fileExt;
    $fileDestination = $uploadPath . '/' . $newFileName;

    // Create directory if not exists
    if (!file_exists($uploadPath)) {
        mkdir($uploadPath, 0777, true);
    }

    // Upload file
    if (move_uploaded_file($fileTmpName, $fileDestination)) {
        return [
            'success' => true,
            'file_name' => $newFileName,
            'file_path' => $fileDestination,
            'file_size' => $fileSize,
            'file_type' => in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif']) ? 'image' : 'video'
        ];
    } else {
        return ['success' => false, 'message' => 'فشل رفع الملف'];
    }
}

// Delete file function
function deleteFile($filePath) {
    if (file_exists($filePath)) {
        return unlink($filePath);
    }
    return false;
}

// Get time ago
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60) {
        return 'الآن';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return "منذ $mins دقيقة";
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return "منذ $hours ساعة";
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return "منذ $days يوم";
    } else {
        return date('Y-m-d', $timestamp);
    }
}
?>
