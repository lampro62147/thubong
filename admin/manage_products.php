<?php
session_start();
// Bảo vệ trang admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

include '../includes/db.php';
include '../includes/header.php';

// Biến lưu thông báo
$category_message = '';
$product_message = '';

// =================== XỬ LÝ CATEGORIES ===================
// Thêm category mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $cat_name = trim($_POST['cat_name'] ?? '');
    $cat_desc = trim($_POST['cat_description'] ?? '');
    
    if ($cat_name) {
        // Kiểm tra trùng tên
        $check_stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
        $check_stmt->execute([$cat_name]);
        
        if ($check_stmt->fetch()) {
            $category_message = "<div class='alert alert-error'>⚠️ Tên thể loại đã tồn tại!</div>";
        } else {
            $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            $stmt->execute([$cat_name, $cat_desc]);
            $category_message = "<div class='alert alert-success'>✅ Thêm thể loại thành công!</div>";
        }
    } else {
        $category_message = "<div class='alert alert-error'>⚠️ Vui lòng nhập tên thể loại!</div>";
    }
}

// Xóa category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category'])) {
    $cat_id = (int)($_POST['category_id'] ?? 0);
    
    if ($cat_id) {
        // Kiểm tra có sản phẩm nào sử dụng category này không
        $check_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
        $check_stmt->execute([$cat_id]);
        $result = $check_stmt->fetch();
        
        if ($result['count'] > 0) {
            $category_message = "<div class='alert alert-error'>⚠️ Không thể xóa! Có {$result['count']} sản phẩm đang sử dụng thể loại này.</div>";
        } else {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$cat_id]);
            $category_message = "<div class='alert alert-success'>✅ Xóa thể loại thành công!</div>";
        }
    }
}

// =================== XỬ LÝ SẢN PHẨM ===================
// Lấy danh sách categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Xử lý thêm sản phẩm
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = trim($_POST['name'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $desc = trim($_POST['description'] ?? '');
    $stock = (int)($_POST['stock'] ?? 0);
    $category_id = (int)($_POST['category_id'] ?? 0);

    if ($name && $price > 0) {
        $image = '';
        if (!empty($_FILES['image']['name'])) {
            $upload_dir = '../uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $filename = time() . '_' . basename($_FILES['image']['name']);
            $image = 'uploads/' . $filename;
            move_uploaded_file($_FILES['image']['tmp_name'], '../' . $image);
        }

        $stmt = $pdo->prepare("INSERT INTO products (name, price, description, image, stock, category_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $price, $desc, $image, $stock, $category_id ?: null]);
        $product_message = "<div class='alert alert-success'>✅ Thêm sản phẩm thành công!</div>";
    } else {
        $product_message = "<div class='alert alert-error'>⚠️ Vui lòng nhập đầy đủ thông tin sản phẩm!</div>";
    }
}

// Lấy danh sách sản phẩm với category name
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
if ($category_filter > 0) {
    $stmt = $pdo->prepare("
        SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.category_id = ?
        ORDER BY p.id DESC
    ");
    $stmt->execute([$category_filter]);
} else {
    $stmt = $pdo->query("
        SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        ORDER BY p.id DESC
    ");
}
$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý sản phẩm & Thể loại</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script>
        function showTab(tabName) {
            // Ẩn tất cả các tab
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Hiện tab được chọn
            document.getElementById(tabName + '-tab').classList.add('active');
            document.querySelector('[onclick="showTab(\'' + tabName + '\')"]').classList.add('active');
            
            // Lưu tab active vào localStorage
            localStorage.setItem('activeTab', tabName);
        }
        
        // Khôi phục tab active khi tải trang
        document.addEventListener('DOMContentLoaded', function() {
            const activeTab = localStorage.getItem('activeTab') || 'products';
            showTab(activeTab);
        });
    </script>
</head>
<body>

<main class="container">
    <a href="dashboard.php" class="back-link">&larr; Quay lại Dashboard</a>
    
    <h2>📦 Quản lý Sản phẩm & Thể loại</h2>

    <!-- Tab Navigation -->
    <div class="management-tabs">
        <button class="tab-btn active" onclick="showTab('products')">🛍️ Sản phẩm</button>
        <button class="tab-btn" onclick="showTab('categories')">🏷️ Thể loại</button>
    </div>

    <!-- =================== TAB SẢN PHẨM =================== -->
    <div id="products-tab" class="tab-content active">
        <?php echo $product_message; ?>
        
        <!-- Form thêm sản phẩm -->
        <div class="form-section">
            <h3>➕ Thêm sản phẩm mới</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="add_product" value="1">
                <div class="form-row">
                    <div class="form-group">
                        <label>Tên sản phẩm</label>
                        <input type="text" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>Giá (₫)</label>
                        <input type="number" name="price" step="0.01" min="0" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Thể loại</label>
                        <select name="category_id" class="form-control">
                            <option value="">-- Chọn thể loại --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Số lượng tồn kho</label>
                        <input type="number" name="stock" value="10" min="0">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Mô tả</label>
                    <textarea name="description" rows="3" placeholder="Mô tả sản phẩm..."></textarea>
                </div>
                
                <div class="form-group">
                    <label>Ảnh đại diện</label>
                    <input type="file" name="image" accept="image/*">
                </div>
                
                <button type="submit" class="btn">➕ Thêm sản phẩm</button>
            </form>
        </div>

        <!-- Danh sách sản phẩm -->
        <h3>📋 Danh sách sản phẩm (<?= count($products) ?> sản phẩm)</h3>
        
        <?php if (!empty($categories)): ?>
        <div class="category-filters">
            <a href="manage_products.php" class="category-filter-btn <?= $category_filter == 0 ? 'active' : '' ?>">
                Tất cả (<?= count($products) ?>)
            </a>
            <?php foreach ($categories as $cat): 
                $count_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
                $count_stmt->execute([$cat['id']]);
                $product_count = $count_stmt->fetch()['count'];
            ?>
                <?php if ($product_count > 0): ?>
                <a href="manage_products.php?category=<?= $cat['id'] ?>" 
                   class="category-filter-btn <?= $category_filter == $cat['id'] ? 'active' : '' ?>">
                    <?= htmlspecialchars($cat['name']) ?> (<?= $product_count ?>)
                </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($products)): ?>
            <div class="product-list-grid">
                <?php foreach ($products as $p): ?>
                <div class="product-item">
                    <?php
                    $image_url = $p['image'] ?? '';
                    if ($image_url) {
                        $image_url = '/' . ltrim($image_url, '/');
                    }
                    $full_path = $_SERVER['DOCUMENT_ROOT'] . $image_url;
                    if ($image_url && file_exists($full_path)) {
                        $display_image = $image_url;
                    } else {
                        $display_image = '/assets/images/no-image.jpg';
                    }
                    ?>
                    <img src="<?= htmlspecialchars($display_image) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                    <div class="product-info">
                        <h4><?= htmlspecialchars($p['name']) ?></h4>
                        <p><strong class="product-price"><?= number_format($p['price'], 0, ',', '.') ?> ₫</strong></p>
                        
                        <?php if ($p['category_name']): ?>
                            <div class="category-info">
                                <span class="category-badge" style="background: <?= getCategoryColor($p['category_id'] ?? 0) ?>">
                                    🏷️ <?= htmlspecialchars($p['category_name']) ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        
                        <p class="stock-info">📦 Tồn kho: <span class="stock-count"><?= $p['stock'] ?></span></p>
                        
                        <div class="actions">
                            <a href="edit_product.php?id=<?= $p['id'] ?>" class="btn-edit">✏️ Sửa</a>
                            <form method="POST" action="delete_product.php" onsubmit="return confirm('Xác nhận xóa sản phẩm này?')">
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <button type="submit" class="btn-delete"> Xóa</button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-message">
                <p>📭 Chưa có sản phẩm nào. Hãy thêm sản phẩm đầu tiên!</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- =================== TAB THỂ LOẠI =================== -->
    <div id="categories-tab" class="tab-content">
        <?php echo $category_message; ?>
        
        <!-- Form thêm thể loại -->
        <div class="form-section">
            <h3>➕ Thêm thể loại mới</h3>
            <form method="POST">
                <input type="hidden" name="add_category" value="1">
                <div class="form-group">
                    <label>Tên thể loại</label>
                    <input type="text" name="cat_name" required placeholder="Ví dụ: Disney Princess">
                </div>
                <div class="form-group">
                    <label>Mô tả</label>
                    <textarea name="cat_description" rows="2" placeholder="Mô tả về thể loại này"></textarea>
                </div>
                <button type="submit" class="btn">➕ Thêm thể loại</button>
            </form>
        </div>

        <!-- Danh sách thể loại -->
        <h3>🏷️ Danh sách thể loại (<?= count($categories) ?> thể loại)</h3>

        <?php if (!empty($categories)): ?>
            <div class="categories-grid">
                <?php foreach ($categories as $cat): 
                    // Đếm số sản phẩm trong category
                    $count_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
                    $count_stmt->execute([$cat['id']]);
                    $product_count = $count_stmt->fetch()['count'];
                ?>
                <div class="category-card">
                    <div class="category-header">
                        <h4><?= htmlspecialchars($cat['name']) ?></h4>
                        <span class="category-badge" style="background: <?= getCategoryColor($cat['id']) ?>">
                            ID: <?= $cat['id'] ?>
                        </span>
                    </div>
                    
                    <p class="category-description"><?= htmlspecialchars($cat['description']) ?: '<em>Không có mô tả</em>' ?></p>
                    
                    <div class="category-stats">
                        <span class="product-count">📦 <?= $product_count ?> sản phẩm</span>
                        <span class="created-date">📅 <?= date('d/m/Y', strtotime($cat['created_at'])) ?></span>
                    </div>
                    
                    <div class="category-actions">
                        <?php if ($product_count > 0): ?>
                            <a href="manage_products.php?category=<?= $cat['id'] ?>" class="btn-view">
                                👁️ Xem sản phẩm
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($product_count == 0): ?>
                            <form method="POST" onsubmit="return confirm('Xác nhận xóa thể loại này?')" style="display:inline;">
                                <input type="hidden" name="delete_category" value="1">
                                <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                                <button type="submit" class="btn-delete"> Xóa</button>
                            </form>
                        <?php else: ?>
                            <span class="disabled-action">Không thể xóa</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-message">
                <p>📭 Chưa có thể loại nào. Hãy thêm thể loại đầu tiên!</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include '../includes/footer.php'; ?>

<?php
// Hàm tạo màu cho category dựa trên ID
function getCategoryColor($category_id) {
    $colors = [
        '#e3f2fd', // xanh nhạt
        '#f3e5f5', // tím nhạt  
        '#e8f5e9', // xanh lá nhạt
        '#fff3e0', // cam nhạt
        '#fce4ec', // hồng nhạt
        '#e1f5fe', // xanh dương nhạt
        '#f9fbe7', // vàng nhạt
        '#f1f8e9', // xanh lá mạ
    ];
    
    $index = $category_id % count($colors);
    return $colors[$index];
}
?>
</body>
</html>