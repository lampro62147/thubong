<?php
include 'includes/header.php';
include 'includes/db.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    die('Không có mã đơn hàng');
}

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) {
    die('Đơn hàng không tồn tại');
}

// Mapping trạng thái
$status_map = [
    'pending' => ['label' => '⏳ Chờ xác nhận', 'color' => '#f57c00'],
    'confirmed' => ['label' => '✅ Đã xác nhận', 'color' => '#388e3c'],
    'shipped' => ['label' => '🚚 Đang giao', 'color' => '#1976d2'],
    'delivered' => ['label' => '📦 Đã giao', 'color' => '#0288d1']
];

$current_status = $status_map[$order['status']] ?? ['label' => 'Không xác định', 'color' => '#9e9e9e'];

// Lấy chi tiết sản phẩm
$stmt = $pdo->prepare("SELECT p.name, oi.quantity, oi.price FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$stmt->execute([$id]);
$items = $stmt->fetchAll();
?>

<main class="order-tracking-container">
    <div class="order-header">
        <h1>📦 Theo dõi đơn hàng #<?= $id ?></h1>
        <div class="status-badge" style="background: <?= $current_status['color'] ?>;">
            <?= $current_status['label'] ?>
        </div>
    </div>

    <div class="address-box">
        <strong>Địa chỉ giao hàng:</strong><br>
        <?= htmlspecialchars($order['address']) ?>
    </div>

    <h3>Chi tiết sản phẩm:</h3>
    <div class="items-list">
        <?php foreach ($items as $item): ?>
        <div class="item-row">
            <img src="assets/images/no-image.jpg" alt="<?= htmlspecialchars($item['name']) ?>" class="item-image">
            <div class="item-info">
                <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                <div class="item-qty">Số lượng: <?= $item['quantity'] ?></div>
            </div>
            <div class="item-price"><?= number_format($item['price'], 0, ',', '.') ?> ₫</div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="total-box">
        Tổng tiền: <?= number_format($order['total'], 0, ',', '.') ?> ₫
    </div>

    <a href="index.php" class="back-btn">← Quay lại trang chủ</a>
</main>

<?php include 'includes/footer.php'; ?>