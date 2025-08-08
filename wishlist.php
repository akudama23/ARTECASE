<?php
session_start();
include 'connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle delete action
if (isset($_GET['delete'])) {
    $product_id = $_GET['delete'];
    
    // Prepare and execute delete statement
    $delete_sql = "DELETE FROM wishlist WHERE user_id = ? AND product_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("ii", $user_id, $product_id);
    $delete_stmt->execute();
    $delete_stmt->close();
    
    // Redirect back to wishlist page to refresh the list
    header("Location: wishlist.php");
    exit();
}

// Fetch wishlist items for the current user with product details
$wishlist_sql = "SELECT p.id, p.product_name, p.price, p.product_image, p.description 
                 FROM wishlist 
                 JOIN products p ON wishlist.product_id = p.id
                 WHERE wishlist.user_id = ?";
$wishlist_stmt = $conn->prepare($wishlist_sql);
$wishlist_stmt->bind_param("i", $user_id);
$wishlist_stmt->execute();
$wishlist_result = $wishlist_stmt->get_result();
$wishlist_items = $wishlist_result->fetch_all(MYSQLI_ASSOC);
$wishlist_stmt->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toko Aksesoris Ponsel - Wishlist</title>
    <link rel="stylesheet" href="style_index1.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Poppins:wght@400;500;600&family=Trirong:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                    <li><a href="homepage.php">Beranda</a></li>
                    <li><a href="produk.php">Produk</a></li>
                    <li><a href="kontak.php">Kontak</a></li>
                    <li><a href="tentangkami.php">Tentang kami</a></li>
                    <!-- <li><a href="daftar.php">Daftar</a></li> -->
                </ul>
            </nav>
            <div class="nav-utility">
                <a href="keranjang.php" class="cart">
                    <i class="fa-solid fa-cart-shopping"></i>
                </a>
                <a href="wishlist.php" class="nav-active">
                    <i class="fa-regular fa-heart"></i>
                </a>
                <a href="akun.php">
                    <i class="fa-solid fa-user"></i>
                </a>
            </div>
        </div>
        <div class="header-divider"></div>
    </header>

    <section class="section container">
        <div class="section-header">
            <div class="section-tag">
                <div class="tag-color"></div>
                <div class="section-subtitle">Wishlist</div>
            </div>
            <h2 class="section-title">Produk yang Anda Inginkan</h2>
        </div>
        
        <?php if (empty($wishlist_items)): ?>
            <div class="empty-wishlist">
                <p>Wishlist Anda kosong.</p>
                <a href="produk.php" class="primary-button">Telusuri Produk</a>
            </div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($wishlist_items as $item): ?>
                    <div class="product-card">
                    <div class="product-image-wrapper">
                        <?php
                        $imagePath = $item['product_image'];
                        if (!preg_match('/^https?:\/\//i', $imagePath) && !file_exists($imagePath)) {
                            $imagePath = 'IMAGE CASE/' . $imagePath;
                        }
                        ?>
                        <a href="detail_produk.php?id=<?php echo $item['id']; ?>">
                            <img src="<?php echo htmlspecialchars($imagePath); ?>" 
                                alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                onerror="this.src='IMAGE CASE/no-image.jpg';this.alt='Gambar tidak tersedia'">
                        </a>
                        <div class="product-actions-overlay">
                            <a href="wishlist.php?delete=<?php echo $item['id']; ?>" 
                            onclick="return confirm('Apakah Anda yakin ingin menghapus produk ini dari wishlist?');">
                                <i class="fa-solid fa-trash"></i>
                            </a>
                            <i class="fa-solid fa-shopping-cart" 
                            onclick="addToCart(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['product_name']); ?>')"></i>
                        </div>
                    </div>
                    <div class="product-info">
                        <h3><a href="detail_produk.php?id=<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['product_name']); ?></a></h3>
                        <div class="product-price">RP. <?php echo number_format($item['price'], 0, ',', '.'); ?></div>
                        <?php if (!empty($item['description'])): ?>
                            <p class="product-description"><?php echo htmlspecialchars($item['description']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
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
    function addToCart(productId, productName) {
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
                alert(data.message || "Gagal menambahkan ke keranjang.");
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert("Terjadi kesalahan saat menambahkan ke keranjang.");
        });
    }
    </script>
</body>
</html>