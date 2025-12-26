<?php
$host = 'sql100.infinityfree.com';
$db   = 'if0_40225770_12';
$user = 'if0_40225770';
$pass = 'Dangtutra123'; // Thay nếu bạn đặt mật khẩu
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Kết nối CSDL thất bại: " . $e->getMessage());
}
?>