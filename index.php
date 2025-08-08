<?php
session_start();

// Redirect ke index.php jika belum login dan mencoba akses halaman lain
if (!isset($_SESSION['user_id'])) {
    $homepage = 'index.php';
} else {
    $homepage = 'homepage.php';
}

if (isset($_SESSION['user_id'])) {
    header("Location: homepage.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Toko Aksesoris Ponsel - Beranda</title>
    <link rel="stylesheet" href="style_index1.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Poppins:wght@400;500;600&family=Trirong:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
            <?php
            // Include koneksi database
            include 'connection.php';
            
            // Fungsi untuk mendapatkan semua produk
            function getAllProducts($conn, $limit = 12) {
                $sql = "SELECT id, product_name, product_image, price, stok FROM products ORDER BY id DESC LIMIT ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $limit);
                $stmt->execute();
                $result = $stmt->get_result();
                $products = [];
                while ($row = $result->fetch_assoc()) {
                    // Tambahkan debug untuk memeriksa path gambar
                    if (!file_exists($row['product_image']) && !file_exists('IMAGE CASE/' . $row['product_image'])) {
                        error_log("Gambar tidak ditemukan: " . $row['product_image']);
                    }
                    $products[] = $row;
                }
                $stmt->close();
                return $products;
            }
            
            // Ambil data produk dari database
            $products = getAllProducts($conn);
            
            // Debug: Tampilkan data produk untuk pemeriksaan
            // echo '<pre>'; print_r($products); echo '</pre>';
            ?>

<div class="top-promo-bar"></div>
    <header class="header">
        <div class="container navbar-wrapper">
            <div class="logo img">
                <img src="IMAGE CASE/LOGO ARTECASE.jpg" alt="Company Logo">
            </div>
            <nav class="main-nav">
                <ul>
                    <li><a href="index.php" class="nav-active">Beranda</a></li>
                    <li><a href="produk.php">Produk</a></li>
                    <li><a href="kontak.php">Kontak</a></li>
                    <li><a href="tentangkami.php">Tentang kami</a></li>
                    <li><a href="login.php">Masuk</a></li>
                </ul>
            </nav>
            <div class="nav-utility">
                <a href="login.php" class="cart">
                    <i class="fa-solid fa-cart-shopping"></i>
                </a>
                <a href="login.php">
                    <i class="fa-regular fa-heart"></i>
                </a>
            </div>
        </div>
        <div class="header-divider"></div>
    </header>

    <section class="hero-section container">
        <img src="IMAGE CASE/image20.jpg" alt="galaxy space" class="background-image">
        <div class="hero-content">
            <div class="hero-subtitle">samsung Galaxy S24 Ultra</div>
            <h1 class="hero-title">Ponselmu Adalah Kanvasmu. Ciptakan Gayamu Bersama Kami</h1>
        </div>
        <img src="IMAGE CASE/image19.png" alt="Phone Mockup" class="hero-phone-mockup">
        <img src="IMAGE CASE/iconimage.png" alt="Small Icon" class="hero-small-icon">
    </section>

    <div class="product-grid">
        <?php if (empty($products)): ?>
            <p class="no-products">Tidak ada produk yang tersedia saat ini.</p>
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
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
        <a href="produk.php" class="primary-button">Lihat Semua Produk</a>
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
    // Fungsi untuk menambahkan produk ke wishlist
    function addToWishlist(productId, productName) {
        // Cek apakah pengguna sudah login
        <?php if(isset($_SESSION['user_id'])): ?>
            // Kirim data ke server menggunakan AJAX
            fetch('wishlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_id: <?php echo $_SESSION['user_id']; ?>,
                    product_id: productId
                })
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    alert(productName + " telah ditambahkan ke wishlist!");
                } else {
                    alert("Gagal menambahkan ke wishlist.");
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert("Terjadi kesalahan saat menambahkan ke wishlist.");
            });
        <?php else: ?>
            // Jika belum login, arahkan ke halaman login
            alert("Anda harus login terlebih dahulu untuk menambahkan ke wishlist.");
            window.location.href = 'login.php';
        <?php endif; ?>
    }

    // Fungsi untuk menambahkan produk ke keranjang
    function addToCart(productId, productName) {
        // Cek apakah pengguna sudah login
        <?php if(isset($_SESSION['user_id'])): ?>
            // Kirim data ke server menggunakan AJAX
            fetch('add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_id: <?php echo $_SESSION['user_id']; ?>,
                    product_id: productId,
                    quantity: 1
                })
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    alert(productName + " telah ditambahkan ke keranjang!");
                } else {
                    alert("Gagal menambahkan ke keranjang.");
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert("Terjadi kesalahan saat menambahkan ke keranjang.");
            });
        <?php else: ?>
            // Jika belum login, arahkan ke halaman login
            alert("Anda harus login terlebih dahulu untuk menambahkan ke keranjang.");
            window.location.href = 'login.php';
        <?php endif; ?>
    }
    </script>
</body>
</html>