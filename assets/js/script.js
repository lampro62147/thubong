// Tự động focus vào ô tìm kiếm
document.addEventListener('DOMContentLoaded', function() {
    // Tìm kiếm trên thanh tìm kiếm chính
    const searchInput = document.querySelector('.search-form-main input[type="text"]');
    
    if (searchInput) {
        // Focus vào ô tìm kiếm
        searchInput.focus();
        
        // Nếu có từ khóa tìm kiếm, highlight nó
        if (searchInput.value) {
            searchInput.select();
        }
        
        // Xử lý phím Enter để submit form
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                submitForm(this.closest('form'));
            }
        });
    }
    
    // Xử lý cho các form filter và sort
    const filterForms = document.querySelectorAll('.filter-form, .sort-form-main');
    filterForms.forEach(form => {
        // Auto submit khi thay đổi select
        const select = form.querySelector('select');
        if (select) {
            select.addEventListener('change', function() {
                submitForm(this.closest('form'));
            });
        }
    });
    
    // Xử lý nút xóa tag
    const removeTags = document.querySelectorAll('.remove-tag');
    removeTags.forEach(tag => {
        tag.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('href');
            if (url) {
                showLoading();
                setTimeout(() => {
                    window.location.href = url;
                }, 300);
            }
        });
    });
    
    // Xử lý nút xóa tất cả bộ lọc
    const clearAllBtn = document.querySelector('.clear-all-filters');
    if (clearAllBtn) {
        clearAllBtn.addEventListener('click', function(e) {
            e.preventDefault();
            showLoading();
            setTimeout(() => {
                window.location.href = window.location.pathname;
            }, 300);
        });
    }
    
    // Xử lý nút xóa filter thể loại
    const removeFilterBtn = document.querySelector('.remove-filter');
    if (removeFilterBtn) {
        removeFilterBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('href');
            if (url) {
                showLoading();
                setTimeout(() => {
                    window.location.href = url;
                }, 300);
            }
        });
    }
    
    // Xử lý nút reset filters trong no-products
    const resetFiltersBtn = document.querySelector('.reset-filters-btn');
    if (resetFiltersBtn) {
        resetFiltersBtn.addEventListener('click', function(e) {
            e.preventDefault();
            showLoading();
            setTimeout(() => {
                window.location.href = this.getAttribute('href');
            }, 300);
        });
    }
    
    // Hiệu ứng hover cho product cards
    const productCards = document.querySelectorAll('.product-card');
    productCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-10px)';
            this.style.boxShadow = '0 15px 30px rgba(0,0,0,0.1)';
            this.style.zIndex = '10';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 4px 12px rgba(0,0,0,0.05)';
            this.style.zIndex = '1';
        });
    });
    
    // Hiệu ứng click cho category badges trong product cards
    const categoryBadges = document.querySelectorAll('.category-badge');
    categoryBadges.forEach(badge => {
        badge.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('href');
            if (url) {
                showLoading();
                setTimeout(() => {
                    window.location.href = url;
                }, 300);
            }
        });
    });
    
    // Cập nhật hiển thị nút xóa bộ lọc
    const updateClearButton = () => {
        const clearAllBtn = document.querySelector('.clear-all-filters');
        const urlParams = new URLSearchParams(window.location.search);
        const hasSearch = urlParams.has('search') && urlParams.get('search') !== '';
        const hasCategory = urlParams.has('category') && urlParams.get('category') !== '';
        
        if (clearAllBtn) {
            clearAllBtn.style.display = (hasSearch || hasCategory) ? 'inline-flex' : 'none';
        }
    };
    
    updateClearButton();
    
    // Xử lý resize window để đảm bảo responsive
    window.addEventListener('resize', function() {
        // Cập nhật hiển thị các element nếu cần
        const filterSortRow = document.querySelector('.filter-sort-row');
        if (filterSortRow && window.innerWidth <= 768) {
            filterSortRow.style.gridTemplateColumns = '1fr';
        } else if (filterSortRow) {
            filterSortRow.style.gridTemplateColumns = '1fr 1fr';
        }
    });
});

// Hàm submit form với loading
function submitForm(form) {
    showLoading();
    
    // Thêm delay nhỏ để hiển thị loading
    setTimeout(() => {
        form.submit();
    }, 300);
}

// Hàm hiển thị loading overlay
function showLoading() {
    // Tạo loading overlay nếu chưa có
    let overlay = document.querySelector('.loading-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'loading-overlay';
        overlay.innerHTML = `
            <div class="loading-content">
                <div class="loading-spinner"></div>
                <p>Đang tải dữ liệu...</p>
            </div>
        `;
        document.body.appendChild(overlay);
        
        // Thêm CSS cho loading nếu chưa có
        if (!document.querySelector('#loading-styles')) {
            const style = document.createElement('style');
            style.id = 'loading-styles';
            style.textContent = `
                .loading-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(255, 255, 255, 0.95);
                    z-index: 9999;
                    display: none;
                    justify-content: center;
                    align-items: center;
                    backdrop-filter: blur(3px);
                }
                
                .loading-overlay.active {
                    display: flex;
                    animation: fadeIn 0.3s ease;
                }
                
                .loading-content {
                    text-align: center;
                    animation: slideUp 0.3s ease;
                }
                
                .loading-spinner {
                    width: 60px;
                    height: 60px;
                    border: 4px solid #f3f3f3;
                    border-top: 4px solid #ee4d2d;
                    border-radius: 50%;
                    animation: spin 1s linear infinite;
                    margin: 0 auto 20px;
                }
                
                .loading-overlay p {
                    color: #666;
                    font-size: 16px;
                    font-weight: 500;
                    margin-top: 15px;
                }
                
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
                
                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
                
                @keyframes slideUp {
                    from { 
                        opacity: 0;
                        transform: translateY(20px);
                    }
                    to { 
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
            `;
            document.head.appendChild(style);
        }
    }
    
    overlay.classList.add('active');
}

// Hàm ẩn loading overlay (nếu cần)
function hideLoading() {
    const overlay = document.querySelector('.loading-overlay');
    if (overlay) {
        overlay.classList.remove('active');
        setTimeout(() => {
            if (overlay.parentNode) {
                overlay.parentNode.removeChild(overlay);
            }
        }, 300);
    }
}

// Debounce function cho các event
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Xử lý scroll để cố định filter/sort trên mobile
if (window.innerWidth <= 768) {
    const filterSortRow = document.querySelector('.filter-sort-row');
    let isSticky = false;
    let originalTop = 0;
    
    if (filterSortRow) {
        originalTop = filterSortRow.offsetTop;
        
        const handleScroll = debounce(() => {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            if (scrollTop > originalTop + 100 && !isSticky) {
                filterSortRow.style.position = 'fixed';
                filterSortRow.style.top = '80px';
                filterSortRow.style.left = '20px';
                filterSortRow.style.right = '20px';
                filterSortRow.style.zIndex = '100';
                filterSortRow.style.boxShadow = '0 5px 20px rgba(0,0,0,0.1)';
                isSticky = true;
            } else if (scrollTop <= originalTop + 100 && isSticky) {
                filterSortRow.style.position = '';
                filterSortRow.style.top = '';
                filterSortRow.style.left = '';
                filterSortRow.style.right = '';
                filterSortRow.style.zIndex = '';
                filterSortRow.style.boxShadow = '';
                isSticky = false;
            }
        }, 10);
        
        window.addEventListener('scroll', handleScroll);
    }
}