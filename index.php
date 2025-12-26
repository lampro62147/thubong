<?php
include 'includes/header.php';
include 'includes/db.php';

// Xử lý tìm kiếm
$search = $_GET['search'] ?? '';

// Xử lý lọc theo category
$category_id = $_GET['category'] ?? '';

// Xử lý sort
$sort = $_GET['sort'] ?? 'newest';
$order_by = '';

switch ($sort) {
    case 'price_asc':
        $order_by = 'p.price ASC';
        $sort_text = 'Giá: Thấp đến Cao';
        break;
    case 'price_desc':
        $order_by = 'p.price DESC';
        $sort_text = 'Giá: Cao đến Thấp';
        break;
    case 'name_asc':
        $order_by = 'p.name ASC';
        $sort_text = 'Tên: A-Z';
        break;
    case 'name_desc':
        $order_by = 'p.name DESC';
        $sort_text = 'Tên: Z-A';
        break;
    case 'category_asc':
        $order_by = 'c.name ASC';
        $sort_text = 'Thể loại: A-Z';
        break;
    case 'category_desc':
        $order_by = 'c.name DESC';
        $sort_text = 'Thể loại: Z-A';
        break;
    default:
        $order_by = 'p.id DESC';
        $sort_text = 'Mới nhất';
}

// Query sản phẩm với tìm kiếm, lọc và sort
$sql = "SELECT p.*, c.name as category_name, c.id as category_id 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id";
$where = [];
$params = [];

if (!empty($search)) {
    // Tìm kiếm theo tên sản phẩm hoặc tên category
    $where[] = "(p.name LIKE ? OR c.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($category_id) && is_numeric($category_id)) {
    $where[] = "p.category_id = ?";
    $params[] = $category_id;
}

if (!empty($where)) {
    $sql .= " WHERE " . implode(' AND ', $where);
}

if ($order_by) {
    $sql .= " ORDER BY $order_by";
}

// Thực thi query
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Lấy danh sách categories để hiển thị trong dropdown
$category_stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $category_stmt->fetchAll();
?>

<main>
    <h2>Sản phẩm nổi bật</h2>
    
    <!-- TÌM KIẾM - RIÊNG 1 HÀNG -->
    <div class="search-section">
        <form method="get" class="search-form-main">
            <div class="search-wrapper">
                <input type="text" 
                       name="search" 
                       placeholder="Nhập tên sản phẩm hoặc thể loại để tìm kiếm..." 
                       value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="search-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    Tìm kiếm
                </button>
            </div>
            <?php if (!empty($search) || !empty($category_id)): ?>
                <a href="?" class="clear-all-filters">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                    Xóa tất cả bộ lọc
                </a>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- LỌC THỂ LOẠI VÀ SẮP XẾP - CHUNG 1 HÀNG -->
    <div class="filter-sort-row">
        <!-- Lọc theo thể loại -->
        <div class="filter-box">
            <h3 class="filter-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                </svg>
                Lọc theo thể loại
            </h3>
            <form method="get" class="filter-form">
                <?php if (!empty($search)): ?>
                    <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                <?php endif; ?>
                
                <div class="category-select-wrapper">
                    <select name="category" id="category" onchange="this.form.submit()">
                        <option value="">Tất cả thể loại</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" 
                                <?= $category_id == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="select-arrow"></div>
                </div>
                
                <?php if (!empty($category_id)): ?>
                    <a href="?<?= !empty($search) ? 'search=' . urlencode($search) : '' ?>" class="remove-filter">
                        Bỏ lọc
                    </a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Sắp xếp -->
        <div class="sort-box">
            <h3 class="sort-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="8" y1="6" x2="21" y2="6"></line>
                    <line x1="8" y1="12" x2="21" y2="12"></line>
                    <line x1="8" y1="18" x2="21" y2="18"></line>
                    <line x1="3" y1="6" x2="3" y2="6"></line>
                    <line x1="3" y1="12" x2="3" y2="12"></line>
                    <line x1="3" y1="18" x2="3" y2="18"></line>
                </svg>
                Sắp xếp
            </h3>
            <form method="get" class="sort-form-main">
                <!-- Giữ lại tham số tìm kiếm và thể loại -->
                <?php if (!empty($search)): ?>
                    <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                <?php endif; ?>
                <?php if (!empty($category_id)): ?>
                    <input type="hidden" name="category" value="<?= htmlspecialchars($category_id) ?>">
                <?php endif; ?>
                
                <div class="sort-select-wrapper">
                    <select name="sort" id="sort" onchange="this.form.submit()">
                        <option value="newest" <?= $sort == 'newest' ? 'selected' : '' ?>>Mới nhất</option>
                        <option value="price_asc" <?= $sort == 'price_asc' ? 'selected' : '' ?>>Giá: Thấp đến Cao</option>
                        <option value="price_desc" <?= $sort == 'price_desc' ? 'selected' : '' ?>>Giá: Cao đến Thấp</option>
                        <option value="name_asc" <?= $sort == 'name_asc' ? 'selected' : '' ?>>Tên: A-Z</option>
                        <option value="name_desc" <?= $sort == 'name_desc' ? 'selected' : '' ?>>Tên: Z-A</option>
                        <option value="category_asc" <?= $sort == 'category_asc' ? 'selected' : '' ?>>Thể loại: A-Z</option>
                        <option value="category_desc" <?= $sort == 'category_desc' ? 'selected' : '' ?>>Thể loại: Z-A</option>
                    </select>
                    <div class="select-arrow"></div>
                </div>
            </form>
            <div class="current-sort-info">
                <span class="sort-indicator">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    <?= htmlspecialchars($sort_text) ?>
                </span>
            </div>
        </div>
    </div>
    
    <!-- Hiển thị kết quả tìm kiếm -->
    <?php if (!empty($search) || !empty($category_id)): ?>
        <div class="results-summary">
            <div class="results-content">
                <?php if (!empty($search)): ?>
                    <span class="result-tag search-tag">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        Tìm kiếm: "<?= htmlspecialchars($search) ?>"
                        <a href="?<?= !empty($category_id) ? 'category=' . $category_id : '' ?>" class="remove-tag">
                            ×
                        </a>
                    </span>
                <?php endif; ?>
                
                <?php if (!empty($category_id)): ?>
                    <?php 
                    $category_name = '';
                    foreach ($categories as $cat) {
                        if ($cat['id'] == $category_id) {
                            $category_name = $cat['name'];
                            break;
                        }
                    }
                    ?>
                    <span class="result-tag category-tag">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                        </svg>
                        Thể loại: <?= htmlspecialchars($category_name) ?>
                        <a href="?<?= !empty($search) ? 'search=' . urlencode($search) : '' ?>" class="remove-tag">
                            ×
                        </a>
                    </span>
                <?php endif; ?>
                
                <span class="results-count">
                    <strong><?= count($products) ?></strong> sản phẩm được tìm thấy
                </span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Hiển thị sản phẩm -->
    <div class="product-grid">
        <?php if ($products): ?>
            <?php foreach ($products as $p): ?>
            <div class="product-card">
                <?php
                $image_url = $p['image'] ?? '';
                if ($image_url) {
                    $image_url = '/' . ltrim($image_url, '/');
                }
                $full_path = $_SERVER['DOCUMENT_ROOT'] . $image_url;

                if ($image_url && file_exists($full_path)) {
                    $display_image = $image_url;
                } else {
                    $display_image = 'assets/images/no-image.jpg';
                }
                ?>
                <img src="<?= htmlspecialchars($display_image) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                <h3><?= htmlspecialchars($p['name']) ?></h3>
                <?php if (!empty($p['category_name'])): ?>
                    <p class="product-category">
                        <a href="?category=<?= $p['category_id'] ?>" class="category-badge">
                            <?= htmlspecialchars($p['category_name']) ?>
                        </a>
                    </p>
                <?php else: ?>
                    <p class="product-category">
                        <span class="category-badge no-category">Chưa phân loại</span>
                    </p>
                <?php endif; ?>
                <p class="price"><?= number_format($p['price'], 0, ',', '.') ?> ₫</p>
                <a href="product.php?id=<?= $p['id'] ?>" class="btn view-details-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                    Xem chi tiết
                </a>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-products-found">
                <div class="no-products-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#6c757d" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                </div>
                <h3>Không tìm thấy sản phẩm nào</h3>
                <p>Không có sản phẩm nào phù hợp với tiêu chí tìm kiếm của bạn.</p>
                <?php if (!empty($search) || !empty($category_id)): ?>
                    <a href="?" class="btn reset-filters-btn">
                        Xóa bộ lọc và xem tất cả sản phẩm
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

</main>

<?php include 'includes/footer.php'; ?>