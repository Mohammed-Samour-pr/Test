<?php
echo "✅ PHP يعمل بنجاح!<br><br>";
echo "معلومات النظام:<br>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server: " . $_SERVER['SERVER_SOFTWARE'] . "<br><br>";

// اختبار قاعدة البيانات
echo "<h3>اختبار الاتصال بقاعدة البيانات:</h3>";

$host = 'localhost';
$user = 'YOUR_DB_USER';      // ⚠️ غيّر هنا
$pass = 'YOUR_DB_PASSWORD';  // ⚠️ غيّر هنا
$dbname = 'YOUR_DB_NAME';    // ⚠️ غيّر هنا

$conn = @new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    echo "❌ فشل الاتصال: " . $conn->connect_error . "<br>";
    echo "<br>تأكد من:<br>";
    echo "1. اسم المستخدم صحيح<br>";
    echo "2. كلمة المرور صحيحة<br>";
    echo "3. اسم قاعدة البيانات صحيح<br>";
    echo "4. قاعدة البيانات موجودة<br>";
} else {
    echo "✅ الاتصال بقاعدة البيانات ناجح!<br>";
    echo "اسم القاعدة: " . $dbname . "<br>";
    $conn->close();
}
?>
