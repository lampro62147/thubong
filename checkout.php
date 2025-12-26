<?php
session_start();
include 'includes/header.php';

if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include 'includes/db.php';

    $address = trim($_POST['address'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    if (!$address) {
        $error = "Vui lòng nhập địa chỉ giao hàng.";
    } elseif (!$phone) {
        $error = "Vui lòng nhập số điện thoại.";
    } else {
        $total = 0;
        foreach ($_SESSION['cart'] as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        try {
            $pdo->beginTransaction();
            
            // Tạo đơn hàng với status = 'pending' (chờ xác nhận)
            // Khi admin xác nhận trong manage_orders.php, hệ thống sẽ tự động giảm số lượng
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, total, address, phone, status) VALUES (?, ?, ?, ?, 'pending')");
            
            // Lấy user_id từ session, nếu không có thì dùng 0 (khách)
            $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
            
            $stmt->execute([$user_id, $total, $address, $phone]);
            $order_id = $pdo->lastInsertId();

            // Lưu chi tiết đơn hàng vào order_items
            foreach ($_SESSION['cart'] as $id => $item) {
                $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt->execute([$order_id, $id, $item['quantity'], $item['price']]);
            }

            $pdo->commit();
            unset($_SESSION['cart']);
            header("Location: order-tracking.php?id=" . $order_id);
            exit;
            
        } catch (Exception $e) {
            $pdo->rollback();
            $error = "Lỗi khi tạo đơn hàng: " . $e->getMessage();
        }
    }
}
?>
<main class="checkout-container">
    <h2>Thanh toán</h2>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <!-- Hiển thị thông tin giỏ hàng -->
    <div class="order-summary">
        <h3>Đơn hàng của bạn:</h3>
        <?php 
        $subtotal = 0;
        $item_count = 0;
        foreach ($_SESSION['cart'] as $id => $item):
            $subtotal += $item['price'] * $item['quantity'];
            $item_count += $item['quantity'];
        ?>
        <div class="cart-item-review">
            <div>
                <strong>Sản phẩm #<?= $id ?></strong><br>
                <small>Số lượng: <?= $item['quantity'] ?> x <?= number_format($item['price'], 0, ',', '.') ?> ₫</small>
            </div>
            <div>
                <strong><?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?> ₫</strong>
            </div>
        </div>
        <?php endforeach; ?>
        
        <div class="summary-row total">
            <span>Tổng cộng:</span>
            <span style="color: #ee4d2d;"><?= number_format($subtotal, 0, ',', '.') ?> ₫</span>
        </div>
    </div>
    
    <form method="POST" class="checkout-form">
        <div class="form-group">
            <label>Họ và tên *</label>
            <input type="text" name="fullname" value="<?= htmlspecialchars($_SESSION['username'] ?? '') ?>" required>
        </div>
        
        <div class="form-group">
            <label>Số điện thoại *</label>
            <input type="tel" name="phone" pattern="[0-9]{10,11}" title="Số điện thoại 10-11 số" required>
        </div>
        
        <div class="form-group">
            <label>Địa chỉ giao hàng *</label>
            <textarea name="address" rows="4" required><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
        </div>
        
        <button type="submit" class="btn btn-primary btn-full">Xác nhận đặt hàng</button>
    </form>
</main>

<?php include 'includes/footer.php'; ?>