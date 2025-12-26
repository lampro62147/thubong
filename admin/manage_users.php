<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

include '../includes/db.php';
include '../includes/header.php';

// Xác định base URL động
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$base_url = $protocol . $host . '/';

// --- XỬ LÝ XÓA NGƯỜI DÙNG ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $user_id = (int)$_POST['user_id'];
    // Không cho xóa chính admin đang đăng nhập
    if ($user_id !== $_SESSION['user_id']) {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
        $message = " Xóa người dùng thành công!";
    } else {
        $error = " Không thể xóa chính tài khoản admin đang đăng nhập!";
    }
}

// --- XỬ LÝ CẬP NHẬT NGƯỜI DÙNG ---
$update_success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $user_id = (int)$_POST['user_id'];
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'user';

    if ($username && $email) {
        // Kiểm tra email có bị trùng không (trừ chính người dùng này)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            $error = " Email này đã được sử dụng bởi người dùng khác!";
        } else {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
            $stmt->execute([$username, $email, $role, $user_id]);
            $update_success = " Cập nhật thông tin thành công!";
            // Sau khi cập nhật, quay lại danh sách
            header("Location: manage_users.php?updated=1");
            exit;
        }
    } else {
        $error = " Vui lòng nhập đầy đủ thông tin!";
    }
}

// --- CHẾ ĐỘ SỬA: LẤY DỮ LIỆU NGƯỜI DÙNG ---
$edit_mode = false;
$edit_user = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_user = $stmt->fetch();
    if ($edit_user) {
        $edit_mode = true;
    }
}

// --- LẤY DANH SÁCH NGƯỜI DÙNG (nếu không ở chế độ sửa) ---
if (!$edit_mode) {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC");
    $users = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $edit_mode ? 'Sửa người dùng' : 'Quản lý người dùng' ?></title>
    <!-- 🟢 Sử dụng base URL động -->
    <base href="<?= htmlspecialchars($base_url) ?>">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<main class="container">
    <!-- 🟢 Sửa link quay lại dashboard -->
    <a href="admin/dashboard.php" class="back-link">&larr; Quay lại Dashboard</a>
    
    <?php if (isset($message)): ?>
        <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success"> Cập nhật thông tin thành công!</div>
    <?php endif; ?>

    <?php if ($edit_mode): ?>
        <!-- FORM SỬA NGƯỜI DÙNG -->
        <h2>✏️ Sửa thông tin người dùng</h2>
        <div class="form-section">
            <form method="POST">
                <input type="hidden" name="user_id" value="<?= $edit_user['id'] ?>">
                <div class="form-group">
                    <label>Tên đăng nhập</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($edit_user['username']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($edit_user['email']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Vai trò</label>
                    <select name="role" class="form-control">
                        <option value="user" <?= $edit_user['role'] === 'user' ? 'selected' : '' ?>>Người dùng</option>
                        <option value="admin" <?= $edit_user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>
                <button type="submit" name="update_user" class="btn btn-save">Lưu thay đổi</button>
                
            </form>
        </div>
    <?php else: ?>
        <!-- DANH SÁCH NGƯỜI DÙNG -->
        <h2>👥 Quản lý người dùng</h2>
        <p class="info">Tổng số: <?= count($users) ?> người dùng</p>

        <table class="users-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên đăng nhập</th>
                    <th>Email</th>
                    <th>Vai trò</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= $u['id'] ?></td>
                    <td><?= htmlspecialchars($u['username']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td>
                        <?php if ($u['role'] === 'admin'): ?>
                            <span class="role-admin">Admin</span>
                        <?php else: ?>
                            <span class="role-user">Người dùng</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <!-- 🟢 Sửa link sửa người dùng -->
                        <a href="admin/manage_users.php?edit=<?= $u['id'] ?>" class="btn btn-edit">✏️ Sửa</a>
                        <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                            <form method="POST" onsubmit="return confirm('Xác nhận xóa người dùng này?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <button type="submit" class="btn btn-delete">🗑️ Xóa</button>
                            </form>
                        <?php else: ?>
                            <span class="disabled-action">(Không thể xóa)</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>

<?php include '../includes/footer.php'; ?>
</body>
</html>