<?php
session_start();
include 'connection.php';

// Check if product ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: produk.php");
    exit();
}

$product_id = $_GET['id'];

// Get product details
$product_sql = "SELECT * FROM products WHERE id = ?";
$product_stmt = $conn->prepare($product_sql);
$product_stmt->bind_param("i", $product_id);
$product_stmt->execute();
$product_result = $product_stmt->get_result();

if ($product_result->num_rows === 0) {
    // Product not found, redirect to products page
    header("Location: produk.php");
    exit();
}

$product = $product_result->fetch_assoc();
$product_stmt->close();

// Get related products (same category, excluding current product)
$related_products_sql = "SELECT id, product_name, product_image, price, stok 
                         FROM products 
                         WHERE category = ? AND id != ? 
                         ORDER BY RAND() 
                         LIMIT 4";
$related_products_stmt = $conn->prepare($related_products_sql);
$related_products_stmt->bind_param("si", $product['category'], $product_id);
$related_products_stmt->execute();
$related_products_result = $related_products_stmt->get_result();
$related_products = $related_products_result->fetch_all(MYSQLI_ASSOC);
$related_products_stmt->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['product_name']); ?> - ArteCase</title>
    <link rel="stylesheet" href="style_index1.css">
    <link rel="stylesheet" href="style_detail_produk.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Poppins:wght@400;500;600&family=Trirong:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                    <li><a href="produk.php">Produk</a></li>
                    <li><a href="kontak.php">Kontak</a></li>
                    <li><a href="tentangkami.php">Tentang kami</a></li>
                    <li><a href="daftar.php">Daftar</a></li>
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
                <?php else: ?>
                    <a href="login.php">
                        <i class="fa-solid fa-user"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="header-divider"></div>
    </header>

    <section class="section container">
        <div class="product-detail-container">
            <div class="product-detail-image">
                <?php
                $imagePath = $product['product_image'];
                if (!preg_match('/^https?:\/\//i', $imagePath) && !file_exists($imagePath)) {
                    $imagePath = 'IMAGE CASE/' . $imagePath;
                }
                ?>
                <img src="<?php echo htmlspecialchars($imagePath); ?>" 
                     alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                     onerror="this.src='IMAGE CASE/no-image.jpg';this.alt='Gambar tidak tersedia'">
            </div>
            
            <div class="product-detail-info">
                <h1><?php echo htmlspecialchars($product['product_name']); ?></h1>
                
                <div class="product-price">
                    RP. <?php echo number_format($product['price'], 0, ',', '.'); ?>
                </div>
                
                <!-- Tambahkan informasi stok -->
                <div class="product-stock">
                    <span class="stock-label">Stok:</span>
                    <span class="stock-value <?php echo ($product['stok'] > 0) ? 'in-stock' : 'out-of-stock'; ?>">
                        <?php echo ($product['stok'] > 0) ? 'Tersedia (' . $product['stok'] . ')' : 'Habis'; ?>
                    </span>
                </div>
                
                <?php if (!empty($product['description'])): ?>
                    <div class="product-description">
                        <h3>Deskripsi Produk</h3>
                        <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                    </div>
                <?php endif; ?>
                
                <div class="product-actions">
                    <button class="add-to-cart-btn" 
                            onclick="addToCart(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['product_name']); ?>', <?php echo $product['stok']; ?>)"
                            <?php echo ($product['stok'] <= 0) ? 'disabled' : ''; ?>>
                        <i class="fa-solid fa-cart-shopping"></i> 
                        <?php echo ($product['stok'] > 0) ? 'Tambah ke Keranjang' : 'Stok Habis'; ?>
                    </button>
                    <button class="add-to-wishlist-btn" onclick="addToWishlist(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['product_name']); ?>')">
                        <i class="fa-regular fa-heart"></i> Tambah ke Wishlist
                    </button>
                </div>
                
                <div class="product-meta">
                    <div class="meta-item">
                        <span class="meta-label">Kategori:</span>
                        <span class="meta-value"><?php echo htmlspecialchars($product['category'] ?? 'Tidak ada kategori'); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (!empty($related_products)): ?>
    <div class="related-products-section">
        <h2>Produk Terkait</h2>
        <div class="product-grid">
            <?php foreach ($related_products as $related_product): ?>
                <div class="product-card">
                    <div class="product-image-wrapper">
                        <?php
                        $imagePath = $related_product['product_image'];
                        if (!preg_match('/^https?:\/\//i', $imagePath) && !file_exists($imagePath)) {
                            $imagePath = 'IMAGE CASE/' . $imagePath;
                        }
                        ?>
                        <a href="detail_produk.php?id=<?php echo $related_product['id']; ?>">
                            <img src="<?php echo htmlspecialchars($imagePath); ?>" 
                                 alt="<?php echo htmlspecialchars($related_product['product_name']); ?>"
                                 onerror="this.src='IMAGE CASE/no-image.jpg';this.alt='Gambar tidak tersedia'">
                        </a>
                        <div class="product-actions-overlay">
                            <i class="fa-regular fa-heart" onclick="addToWishlist(<?php echo $related_product['id']; ?>, '<?php echo htmlspecialchars($related_product['product_name']); ?>')"></i>
                            <i class="fa-solid fa-shopping-cart" onclick="addToCart(<?php echo $related_product['id']; ?>, '<?php echo htmlspecialchars($related_product['product_name']); ?>', <?php echo $related_product['stok']; ?>)"></i>
                        </div>
                    </div>
                    <div class="product-info">
                        <h3><a href="detail_produk.php?id=<?php echo $related_product['id']; ?>"><?php echo htmlspecialchars($related_product['product_name']); ?></a></h3>
                        <div class="product-price">RP. <?php echo number_format($related_product['price'], 0, ',', '.'); ?></div>
                        <div class="product-stock">
                            <span class="stock-label">Stok:</span>
                            <span class="stock-value <?php echo ($related_product['stok'] > 0) ? 'in-stock' : 'out-of-stock'; ?>">
                                <?php echo ($related_product['stok'] > 0) ? 'Tersedia (' . $related_product['stok'] . ')' : 'Habis'; ?>
                            </span>
                        </div>
                    </div>
                </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
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

    function addToCart(productId, productName, stock) {
        <?php if(isset($_SESSION['user_id'])): ?>
            if (stock <= 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Stok Habis',
                    text: "Maaf, produk ini sedang tidak tersedia.",
                    confirmButtonColor: '#3085d6',
                });
                return;
            }

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