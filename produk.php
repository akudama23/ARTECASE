<?php
session_start();
include 'connection.php';

// Function to get first product image for each category
function getCategoryImages($conn, $available_categories) {
    $categoryImages = [];
    $defaultImages = [
    'Softcase' => 'softcase-icon.jpg',
    'Headset' => 'headset-icon.jpg',
    'Smartwatch' => 'smartwatch-icon.jpg',
    'Earbuds' => 'earbuds-icon.jpg'
];
    
    foreach ($available_categories as $cat) {
        // Cek apakah kategori ada di default images
        if (array_key_exists($cat, $defaultImages)) {
            $categoryImages[$cat] = $defaultImages[$cat];
        } else {
            // Jika tidak, coba ambil dari database
            $sql = "SELECT product_image FROM products WHERE category = ? LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $cat);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $categoryImages[$cat] = $row['product_image'];
            } else {
                $categoryImages[$cat] = 'image23.jpg'; // Fallback image
            }
            
            $stmt->close();
        }
    }
    
    return $categoryImages;
}


// Function to get all products with optional category filter
function getAllProducts($conn, $category = null, $limit = null) {
    $sql = "SELECT id, product_name, product_image, price, stok, description, category FROM products";
    
    // Add category filter if provided
    if ($category) {
        $sql .= " WHERE category = ?";
    }
    
    $sql .= " ORDER BY id DESC";
    
    // Add limit if provided
    if ($limit) {
        $sql .= " LIMIT ?";
    }
    
    $stmt = $conn->prepare($sql);
    
    // Bind parameters based on what's provided
    if ($category && $limit) {
        $stmt->bind_param("si", $category, $limit);
    } elseif ($category) {
        $stmt->bind_param("s", $category);
    } elseif ($limit) {
        $stmt->bind_param("i", $limit);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    $stmt->close();
    return $products;
}

// Get category from query string if exists
$category = isset($_GET['category']) ? $_GET['category'] : null;

// Get all unique categories for the category filter
$categories_sql = "SELECT DISTINCT category FROM products";
$categories_result = $conn->query($categories_sql);
$available_categories = [];
while ($row = $categories_result->fetch_assoc()) {
    $available_categories[] = $row['category'];
}

// Get category images
$categoryImages = getCategoryImages($conn, $available_categories);

// Get all products (filtered by category if specified) - limit to 10 products
$products = getAllProducts($conn, $category, 20);

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toko Aksesoris Ponsel - Produk Kami</title>
    <link rel="stylesheet" href="style_index1.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Poppins:wght@400;500;600&family=Trirong:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Additional styles for horizontal category display */
        .categories-container {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }
        
        .category-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 15px;
            border-radius: 8px;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            cursor: pointer;
            min-width: 120px;
            text-align: center;
        }
        
        .category-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            background: #f8f9fa;
        }
        
        .category-item.active {
            background: var(--primary-red);
            color: white;
        }
        
        .category-item.active span {
            color: white;
        }
        
        .category-item img {
            width: 50px;
            height: 50px;
            object-fit: contain;
            margin-bottom: 10px;
        }
        
        .category-item span {
            font-weight: 500;
            color: #333;
        }
        
        .section-divider {
            margin: 30px 0;
        }
        
        .category-title {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: var(--primary-red);
        }
    </style>
</head>
<body>

    <div class="top-promo-bar"></div>

    <header class="header">
        <div class="container navbar-wrapper">
            <div class="logo">
                <img src="IMAGE CASE/LOGO ARTECASE.jpg" alt="Company Logo">
            </div>
            <nav class="main-nav">
                <ul>
                    <li><a href="<?php echo isset($_SESSION['user_id']) ? 'homepage.php' : 'index.php'; ?>">Beranda</a></li>
                    <li><a href="produk.php" class="nav-active">Produk</a></li>
                    <li><a href="kontak.php">Kontak</a></li>
                    <li><a href="tentangkami.php">Tentang kami</a></li>
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <li><a href="login.php">Masuk</a></li>
                     <?php endif; ?>
                </ul>
            </nav>
            <div class="nav-utility">
                <a href="<?php echo isset($_SESSION['user_id']) ? 'keranjang.php' : 'login.php'; ?>" class="cart">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <span class="cart-count">
                            <?php 
                            $count_sql = "SELECT COUNT(*) as count FROM cart WHERE user_id = ?";
                            $count_stmt = $conn->prepare($count_sql);
                            $count_stmt->bind_param("i", $_SESSION['user_id']);
                            $count_stmt->execute();
                            $count_result = $count_stmt->get_result();
                            $count_row = $count_result->fetch_assoc();
                            echo $count_row['count'];
                            $count_stmt->close();
                            ?>
                        </span>
                    <?php endif; ?>
                </a>
                <a href="<?php echo isset($_SESSION['user_id']) ? 'wishlist.php' : 'login.php'; ?>">
                    <i class="fa-regular fa-heart"></i>
                </a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="akun.php">
                        <i class="fa-solid fa-user"></i>
                    </a>
                
                <?php endif; ?>
            </div>
        </div>
        <div class="header-divider"></div>
    </header>

    <section class="section container">
        <!-- Kategori Section -->
        <div class="section-header">
            <div class="section-tag">
                <div class="tag-color"></div>
                <div class="section-subtitle">Kategori</div>
            </div>
            <h2 class="section-title">Cari Sesuai Kategori</h2>
        </div>
        
        <div class="categories-container">
            <a href="produk.php" class="category-item <?php echo !$category ? 'active' : ''; ?>">
                <img src="IMAGE CASE/image21.jpg" alt="Semua Produk Icon">
                <span>Semua Produk</span>
            </a>
            <?php
                // Display all available categories from database
                foreach ($available_categories as $cat) {
                    $iconImage = isset($categoryImages[$cat]) ? $categoryImages[$cat] : 'image23.jpg';
                    ?>
                    <a href="produk.php?category=<?php echo urlencode($cat); ?>" class="category-item <?php echo $category == $cat ? 'active' : ''; ?>">
                        <img src="IMAGE CASE/<?php echo htmlspecialchars($iconImage); ?>" 
                            alt="<?php echo htmlspecialchars($cat); ?> Icon"
                            onerror="this.src='IMAGE CASE/earbud-icon.jpg';this.alt='Gambar tidak tersedia'">
                        <span><?php echo htmlspecialchars($cat); ?></span>
                    </a>
                    <?php
                }
            ?>
        </div>
        
        <div class="section-divider"></div>
        
        <!-- Produk Kami Section -->
        <div class="section-header">
            <div class="section-tag">
                <div class="tag-color"></div>
                <div class="section-subtitle">Produk</div>
            </div>
            <h2 class="section-title">Produk Kami</h2>
            
            <?php if ($category): ?>
                <p class="category-title">Kategori: <?php echo htmlspecialchars($category); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="product-grid">
            <?php if (empty($products)): ?>
                <p class="no-products">Tidak ada produk yang tersedia untuk kategori ini.</p>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <div class="product-image-wrapper">
                            <?php
                            $imagePath = $product['product_image'];
                            if (!preg_match('/^https?:\/\//i', $imagePath) && !file_exists($imagePath)) {
                                $imagePath = 'IMAGE CASE/' . $imagePath;
                            }
                            ?>
                            <a href="detail_produk.php?id=<?php echo $product['id']; ?>">
                                <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>" onerror="this.src='IMAGE CASE/no-image.jpg';this.alt='Gambar tidak tersedia'">
                            </a>
                            <div class="product-actions-overlay">
                                <i class="fa-regular fa-heart" onclick="addToWishlist(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['product_name']); ?>')"></i>
                                <i class="fa-solid fa-shopping-cart" onclick="<?php echo ($product['stok'] > 0) ? "addToCart({$product['id']}, '".htmlspecialchars($product['product_name'])."')" : "alert('Stok habis')"; ?>"></i>
                            </div>
                        </div>
                        <div class="product-info">
                            <h3><a href="detail_produk.php?id=<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['product_name']); ?></a></h3>
                            <div class="product-price">RP. <?php echo number_format($product['price'], 0, ',', '.'); ?></div>
                            <div class="product-stock <?php echo ($product['stok'] > 0) ? 'in-stock' : 'out-of-stock'; ?>">
                                <?php echo ($product['stok'] > 0) ? 'Tersedia' : 'Stok Habis'; ?>
                            </div>
                            <?php if (!empty($product['description'])): ?>
                                <p class="product-description"><?php echo substr(htmlspecialchars($product['description']), 0, 100); ?>...</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="section-divider"></div>
    </section>

    <footer class="main-footer-container">
        <div class="footer-content-wrapper">
            <div class="footer-column">
                <h3 class="footer-column-title">Bantuan</h3>
                <p>Jl. Kalibata 3 no 12/21, Bogor</p>
                <p class="footer-link-item">ysfradana23@gmail.com</p>
                <p class="footer-link-item">+62 85881816690</p>
            </div>
            <div class="footer-column">
                <h3 class="footer-column-title">Akun</h3>
                <a class="footer-link-item" href="login2.php">Masuk</a>
                <a class="footer-link-item" href="keranjang.php">Keranjang</a>
                <a class="footer-link-item" href="wishlist.php">Keinginan</a>
            </div>
            <div class="footer-column">
                <h3 class="footer-column-title footer-heading-lowercase">Ikuti Kami</h3>
                <div class="social-link">
                    <i class="fab fa-instagram footer-social-icon"></i>
                    <a class="footer-link-item" href="https://www.instagram.com/">ArteCase</a>
                </div>
            </div>
        </div>
        <div class="footer-bottom-section">
            <div class="copyright-info">
                <i class="far fa-copyright copyright-icon"></i>
                <span class="copyright-text">Copyright Rimel 2022. All rights reserved</span>
            </div>
        </div>
    </footer>

    <script>
    function addToWishlist(productId, productName) {
        <?php if(isset($_SESSION['user_id'])): ?>
            fetch('add_to_wishlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId
                })
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: productName + " telah ditambahkan ke wishlist!",
                        confirmButtonColor: '#3085d6',
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: data.message || "Gagal menambahkan ke wishlist.",
                        confirmButtonColor: '#3085d6',
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: "Terjadi kesalahan saat menambahkan ke wishlist.",
                    confirmButtonColor: '#3085d6',
                });
            });
        <?php else: ?>
            Swal.fire({
                icon: 'warning',
                title: 'Perhatian',
                text: "Anda harus login terlebih dahulu untuk menambahkan ke wishlist.",
                confirmButtonColor: '#3085d6',
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'login.php';
                }
            });
        <?php endif; ?>
    }

    function addToCart(productId, productName) {
        <?php if(isset($_SESSION['user_id'])): ?>
            fetch('add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: 1
                })
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    // Update cart count if element exists
                    const cartCount = document.querySelector('.cart-count');
                    if (cartCount && data.cart_count !== undefined) {
                        cartCount.textContent = data.cart_count;
                    }
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: productName + " telah ditambahkan ke keranjang!",
                        confirmButtonColor: '#3085d6',
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: data.message || "Gagal menambahkan ke keranjang.",
                        confirmButtonColor: '#3085d6',
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: "Terjadi kesalahan saat menambahkan ke keranjang.",
                    confirmButtonColor: '#3085d6',
                });
            });
        <?php else: ?>
            Swal.fire({
                icon: 'warning',
                title: 'Perhatian',
                text: "Anda harus login terlebih dahulu untuk menambahkan ke keranjang.",
                confirmButtonColor: '#3085d6',
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'login.php';
                }
            });
        <?php endif; ?>
    }
    </script>

    
</body>
</html>