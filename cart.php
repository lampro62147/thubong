<?php
include 'includes/header.php';
?>
<main>
    <h2>Giỏ hàng của bạn</h2>
    <?php if (empty($_SESSION['cart'])): ?>
        <p>Giỏ hàng trống. <a href="index.php">Mua sắm ngay!</a></p>
    <?php else: ?>
        <table class="cart-table">
            <thead>
                <tr>
                    <th>Sản phẩm</th>
                    <th>Giá</th>
                    <th>Số lượng</th>
                    <th>Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total = 0;
                foreach ($_SESSION['cart'] as $id => $item):
                    $line_total = $item['price'] * $item['quantity'];
                    $total += $line_total;
                ?>
                <tr>
                    <td>
                        <img src="<?= htmlspecialchars($item['image'] ?: 'assets/images/no-image.jpg') ?>" width="50">
                        <?= htmlspecialchars($item['name']) ?>
                    </td>
                    <td><?= number_format($item['price'], 0, ',', '.') ?> ₫</td>
                    <td><?= $item['quantity'] ?></td>
                    <td><?= number_format($line_total, 0, ',', '.') ?> ₫</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" align="right"><strong>Tổng cộng:</strong></td>
                    <td><strong><?= number_format($total, 0, ',', '.') ?> ₫</strong></td>
                </tr>
            </tfoot>
        </table>
        <div class="cart-actions">
            <a href="index.php" class="btn">Tiếp tục mua sắm</a>
            <a href="checkout.php" class="btn checkout">Thanh toán</a>
        </div>
    <?php endif; ?>
</main>
<?php include 'includes/footer.php'; ?>