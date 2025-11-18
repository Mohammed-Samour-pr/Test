<?php
// اختبار بسيط لصفحة تسجيل الدخول
echo "✅ يمكن الوصول لمجلد admin بنجاح!<br><br>";

// اختبار المسارات
echo "المسار الحالي: " . __DIR__ . "<br>";
echo "المسار الرئيسي: " . dirname(__DIR__) . "<br><br>";

// اختبار وجود الملفات
$files_to_check = [
    '../config/database.php',
    '../includes/functions.php',
    '../assets/css/style.css'
];

echo "<h3>فحص الملفات:</h3>";
foreach ($files_to_check as $file) {
    $full_path = __DIR__ . '/' . $file;
    if (file_exists($full_path)) {
        echo "✅ موجود: $file<br>";
    } else {
        echo "❌ مفقود: $file<br>";
    }
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>اختبار تسجيل الدخول</title>
</head>
<body style="direction: rtl; font-family: Arial;">
    <h2>صفحة تسجيل الدخول - نسخة تجريبية</h2>
    <p>إذا ظهرت هذه الصفحة، فالمشكلة في ملفات config أو includes</p>
</body>
</html>
