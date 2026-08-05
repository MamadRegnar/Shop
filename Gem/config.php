<?php
// config.php
error_reporting(0);
session_start();

$host = '51.83.49.125';
$dbname = 's267069_FreeFireGem';
$username = 'u267069_HUfPcT5VH4'; // تغییر دهید
$password = 'e!3Szol4zR5d5a2aPVq1@@j0'; // تغییر دهید

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("❌ خطا در اتصال به دیتابیس: " . $e->getMessage());
}

// تنظیمات ادمین - حتماً تغییر دهید!
$admin_username = 'admin';
$admin_password_hash = password_hash('BosBos', PASSWORD_DEFAULT);

// تنظیمات امنیتی
$max_requests_per_ip = 3; // حداکثر درخواست از هر IP در 24 ساعت
$min_diamond_amount = 10;
$max_diamond_amount = 10000;
?>
