<?php
include 'includes/header.php';
include 'includes/db.php';

$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) {
    die('Sản phẩm không tồn tại');
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    die('Không tìm thấy sản phẩm');
}

// Xử lý thêm vào giỏ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qty = (int)($_POST['quantity'] ?? 1);
    if ($qty < 1) $qty = 1;

    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

    $_SESSION['cart'][$id] = [
        'name' => $product['name'],
        'price' => $product['price'],
        'quantity' => $qty,
        'image' => $product['image']
    ];

    header("Location: cart.php");
    exit;
}
?>
<main>
    <div class="product-detail">
        <img src="<?= htmlspecialchars($product['image'] ?: 'assets/images/no-image.jpg') ?>" alt="<?= htmlspecialchars($product['name']) ?>">
        <div class="product-info">
            <h1><?= htmlspecialchars($product['name']) ?></h1>
            <p class="price"><?= number_format($product['price'], 0, ',', '.') ?> ₫</p>
            <p><?= htmlspecialchars($product['description']) ?></p>
            <p>Kho còn: <?= $product['stock'] ?> sản phẩm</p>

            <form method="POST">
                <label>Số lượng:
                    <input type="number" name="quantity" value="1" min="1" max="<?= $product['stock'] ?>" required>
                </label><br><br>
                <button type="submit" class="btn">Thêm vào giỏ</button>
            </form>
        </div>
    </div>
</main>
<?php include 'includes/footer.php'; ?>