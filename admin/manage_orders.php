<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

include '../includes/db.php';

// Xác định base URL động
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$base_url = $protocol . $host . '/';

// Xử lý cập nhật trạng thái đơn hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['action'])) {
    $order_id = (int)$_POST['order_id'];
    $action = $_POST['action'];

    $status_map = [
        'confirm' => 'confirmed',
        'ship' => 'shipped',
        'deliver' => 'delivered'
    ];

    if (isset($status_map[$action])) {
        $new_status = $status_map[$action];
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $order_id]);
        
        // Giảm tồn kho khi xác nhận đơn hàng
        if ($action === 'confirm') {
            // Lấy tất cả sản phẩm trong đơn hàng từ bảng order_items
            $stmt = $pdo->prepare("
                SELECT product_id, quantity 
                FROM order_items 
                WHERE order_id = ?
            ");
            $stmt->execute([$order_id]);
            $items = $stmt->fetchAll();
            
            // Giảm số lượng tồn kho cho từng sản phẩm
            foreach ($items as $item) {
                $update_stmt = $pdo->prepare("
                    UPDATE products 
                    SET stock = stock - ? 
                    WHERE id = ? AND stock >= ?
                ");
                $update_stmt->execute([$item['quantity'], $item['product_id'], $item['quantity']]);
                
                // Kiểm tra nếu tồn kho không đủ
                if ($update_stmt->rowCount() == 0) {
                    // Lấy tên sản phẩm để hiển thị thông báo
                    $product_stmt = $pdo->prepare("SELECT name FROM products WHERE id = ?");
                    $product_stmt->execute([$item['product_id']]);
                    $product = $product_stmt->fetch();
                    
                    $_SESSION['error'] = "Sản phẩm '{$product['name']}' không đủ tồn kho!";
                    header("Location: manage_orders.php");
                    exit;
                }
            }
            $success = "✅ Đã xác nhận đơn hàng #{$order_id} và giảm tồn kho thành công!";
        } else {
            $success = " Cập nhật trạng thái đơn hàng #{$order_id} thành công!";
        }
    }
}

// Lấy danh sách đơn hàng
$stmt = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC");
$orders = $stmt->fetchAll();

$status_map = [
    'pending' => ['label' => '⏳ Chờ xác nhận', 'color' => '#f57c00'],
    'confirmed' => ['label' => '✅ Đã xác nhận', 'color' => '#388e3c'],
    'shipped' => ['label' => '🚚 Đang giao', 'color' => '#1976d2'],
    'delivered' => ['label' => '📦 Đã giao', 'color' => '#0288d1']
];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đơn hàng</title>
    <!-- 🟢 Sử dụng base URL động -->
    <base href="<?= htmlspecialchars($base_url) ?>">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- Header -->
<?php include '../includes/header.php'; ?>

<main class="container">
    <!-- 🟢 Sửa link quay lại dashboard -->
    <a href="admin/dashboard.php" class="back-link">&larr; Quay lại Dashboard</a>
    <h2>📋 Quản lý đơn hàng</h2>

    <?php if (isset($success)): ?>
        <div class="success"><?= $success ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="error"><?= $_SESSION['error'] ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <p class="info">Tổng số: <?= count($orders) ?> đơn hàng</p>

    <table class="order-table">
        <thead>
            <tr>
                <th>Đơn #</th>
                <th>Người dùng</th>
                <th>Tổng tiền</th>
                <th>Trạng thái</th>
                <th>Địa chỉ</th>
                <th>Ngày tạo</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $o): ?>
            <tr>
                <td><strong>#<?= $o['id'] ?></strong></td>
                <td><?= $o['user_id'] ?: 'Khách' ?></td>
                <td><?= number_format($o['total'], 0, ',', '.') ?> ₫</td>
                <td>
                    <span class="status-badge" style="background: <?= $status_map[$o['status']]['color'] ?>">
                        <?= $status_map[$o['status']]['label'] ?>
                    </span>
                </td>
                <td><?= htmlspecialchars(substr($o['address'], 0, 30)) ?>...</td>
                <td><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                <td>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                        <?php if ($o['status'] === 'pending'): ?>
                            <button type="submit" name="action" value="confirm" class="btn btn-confirm">
                                ✅ Xác nhận
                            </button>
                        <?php elseif ($o['status'] === 'confirmed'): ?>
                            <button type="submit" name="action" value="ship" class="btn btn-ship">🚚 Giao hàng</button>
                        <?php elseif ($o['status'] === 'shipped'): ?>
                            <button type="submit" name="action" value="deliver" class="btn btn-deliver">📦 Hoàn thành</button>
                        <?php else: ?>
                            <span class="success">✔️ Đã hoàn thành</span>
                        <?php endif; ?>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</main>

<?php include '../includes/footer.php'; ?>
</body>
</html>