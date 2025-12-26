<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <!-- 🟢 Giữ nguyên base để fix header -->
    
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<!-- Header -->
<?php include '../includes/header.php'; ?>

<main class="admin-container">
    <h2>🎛️ Bảng điều khiển Admin</h2>
    <p>Chào mừng bạn quay trở lại! Quản lý cửa hàng của bạn tại đây.</p>

    <div class="admin-grid">
        <!-- ✅ SỬA LINK Ở ĐÂY -->
        <a href="/admin/manage_products.php" class="admin-card">
            <div class="admin-icon">📦</div>
            <h3>Quản lý sản phẩm</h3>
        </a>
        <a href="/admin/manage_users.php" class="admin-card">
            <div class="admin-icon">👥</div>
            <h3>Quản lý người dùng</h3>
        </a>
        <a href="/admin/manage_orders.php" class="admin-card">
            <div class="admin-icon">📋</div>
            <h3>Quản lý đơn hàng</h3>
        </a>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
</body>
</html>