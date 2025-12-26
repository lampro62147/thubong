<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

include '../includes/db.php';
include '../includes/header.php';

// Xử lý thêm/sửa/xóa category
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'add' && isset($_POST['name'])) {
            $name = trim($_POST['name']);
            $desc = trim($_POST['description'] ?? '');
            
            if ($name) {
                $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
                $stmt->execute([$name, $desc]);
                $success = "Thêm thể loại thành công!";
            }
        }
        elseif ($action === 'update' && isset($_POST['category_id'])) {
            $id = (int)$_POST['category_id'];
            $name = trim($_POST['name']);
            $desc = trim($_POST['description'] ?? '');
            
            if ($name) {
                $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
                $stmt->execute([$name, $desc, $id]);
                $success = "Cập nhật thể loại thành công!";
            }
        }
        elseif ($action === 'delete' && isset($_POST['category_id'])) {
            $id = (int)$_POST['category_id'];
            
            // Kiểm tra xem có sản phẩm nào thuộc category này không
            $check = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
            $check->execute([$id]);
            $result = $check->fetch();
            
            if ($result['count'] > 0) {
                $error = "Không thể xóa thể loại này vì có {$result['count']} sản phẩm đang sử dụng!";
            } else {
                $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                $stmt->execute([$id]);
                $success = "Xóa thể loại thành công!";
            }
        }
    }
}

// Lấy danh sách categories với số sản phẩm
$stmt = $pdo->query("
    SELECT c.*, 
           COUNT(p.id) as product_count
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id
    GROUP BY c.id
    ORDER BY c.name
");
$categories = $stmt->fetchAll();

// Chế độ chỉnh sửa
$edit_mode = false;
$edit_category = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_category = $stmt->fetch();
    if ($edit_category) {
        $edit_mode = true;
    }
}
?>

<main class="container">
    <a href="dashboard.php" class="back-link">&larr; Quay lại Dashboard</a>
    
    <h2>🏷️ Quản lý thể loại thú bông</h2>

    <?php if (isset($success)): ?>
        <div class="success"><?= $success ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <?php if ($edit_mode): ?>
        <!-- FORM CHỈNH SỬA THỂ LOẠI -->
        <div class="form-section">
            <h3>✏️ Chỉnh sửa thể loại</h3>
            <form method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="category_id" value="<?= $edit_category['id'] ?>">
                <div class="form-group">
                    <label>Tên thể loại</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($edit_category['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Mô tả</label>
                    <textarea name="description" rows="3"><?= htmlspecialchars($edit_category['description']) ?></textarea>
                </div>
                <button type="submit" class="btn btn-save">💾 Lưu thay đổi</button>
                <a href="manage_categories.php" class="cancel-link">❌ Hủy</a>
            </form>
        </div>
    <?php else: ?>
        <!-- FORM THÊM THỂ LOẠI MỚI -->
        <div class="form-section">
            <h3>➕ Thêm thể loại mới</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label>Tên thể loại</label>
                    <input type="text" name="name" required placeholder="Ví dụ: Disney Princess">
                </div>
                <div class="form-group">
                    <label>Mô tả</label>
                    <textarea name="description" rows="2" placeholder="Mô tả về thể loại này"></textarea>
                </div>
                <button type="submit" class="btn">➕ Thêm thể loại</button>
            </form>
        </div>
    <?php endif; ?>

    <!-- DANH SÁCH THỂ LOẠI -->
    <h3>📋 Danh sách thể loại (<?= count($categories) ?> thể loại)</h3>

    <?php if (!empty($categories)): ?>
        <table class="users-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên thể loại</th>
                    <th>Mô tả</th>
                    <th>Số sản phẩm</th>
                    <th>Ngày tạo</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $cat): ?>
                <tr>
                    <td><?= $cat['id'] ?></td>
                    <td>
                        <strong><?= htmlspecialchars($cat['name']) ?></strong>
                        <div style="margin-top: 5px;">
                            <span class="category-badge category-<?= $cat['id'] ?>">
                                🏷️ <?= htmlspecialchars($cat['name']) ?>
                            </span>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($cat['description']) ?: '<em>Không có mô tả</em>' ?></td>
                    <td>
                        <?php if ($cat['product_count'] > 0): ?>
                            <a href="manage_products.php?category=<?= $cat['id'] ?>" class="btn btn-view" style="padding: 3px 8px;">
                                👁️ <?= $cat['product_count'] ?> sp
                            </a>
                        <?php else: ?>
                            <span class="disabled-action">0 sp</span>
                        <?php endif; ?>
                    </td>
                    <td><?= date('d/m/Y', strtotime($cat['created_at'])) ?></td>
                    <td>
                        <a href="manage_categories.php?edit=<?= $cat['id'] ?>" class="btn btn-edit">✏️ Sửa</a>
                        <?php if ($cat['product_count'] == 0): ?>
                            <form method="POST" onsubmit="return confirm('Xác nhận xóa thể loại này?')" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                                <button type="submit" class="btn btn-delete">🗑️ Xóa</button>
                            </form>
                        <?php else: ?>
                            <span class="disabled-action">Không thể xóa</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="empty-message">
            <p>Chưa có thể loại nào. Hãy thêm thể loại đầu tiên!</p>
        </div>
    <?php endif; ?>
</main>

<?php include '../includes/footer.php'; ?>