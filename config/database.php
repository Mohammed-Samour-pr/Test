<?php
// Database Configuration

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'media_management');

// Create database connection
function getConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($conn->connect_error) {
            die("خطأ في الاتصال بقاعدة البيانات: " . $conn->connect_error);
        }

        $conn->set_charset("utf8mb4");
        return $conn;
    } catch (Exception $e) {
        die("خطأ: " . $e->getMessage());
    }
}

// Close database connection
function closeConnection($conn) {
    if ($conn) {
        $conn->close();
    }
}
?>
