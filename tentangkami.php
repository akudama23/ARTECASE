<?php
session_start();

// Redirect berdasarkan status login
if (isset($_SESSION['user_id'])) {
    // Jika sudah login, pastikan kembali ke homepage.php
    $homepage = 'homepage.php';
} else {
    // Jika belum login, pastikan kembali ke index.php
    $homepage = 'index.php';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toko Aksesoris Ponsel - Tentang Kami</title>
    <link rel="stylesheet" href="style_index1.css">
    <link rel="stylesheet" href="style_tentangkami.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Poppins:wght@400;500;600&family=Trirong:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="top-promo-bar"></div>
    <header class="header">
        <div class="about-navbar-wrapper">
            <div class="logo">
                <img src="IMAGE CASE/LOGO ARTECASE.jpg" alt="Company Logo">
            </div>
            <nav class="main-nav">
                <ul>
                    <li><a href="homepage.php">Beranda</a></li>
                    <li><a href="produk.php">Produk</a></li>
                    <li><a href="kontak.php">Kontak</a></li>
                    <li><a href="tentangkami.php" class="nav-active">Tentang kami</a></li>
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <li><a href="login.php">Masuk</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
        <div class="header-divider"></div>
</header>
    <section class="section container">
    <h2 class="section-title">Tentang Kami</h2>
    <div class="flex flex-col md:flex-row gap-8">
        <div class="md:flex-1 text-container"> <!-- Tambahkan kelas text-container -->
            <p class="text-lg leading-relaxed">
                Kami adalah Toko Aksesoris Ponsel yang menyediakan berbagai produk berkualitas tinggi untuk memenuhi kebutuhan Anda. Dengan pengalaman bertahun-tahun di industri ini, kami berkomitmen untuk memberikan pelayanan terbaik dan produk yang inovatif.
            </p>
            <p class="text-lg leading-relaxed mt-4">
                Misi kami adalah untuk memberikan pengalaman berbelanja yang menyenangkan dan memuaskan bagi setiap pelanggan. Kami percaya bahwa setiap orang berhak mendapatkan akses ke produk berkualitas dengan harga yang terjangkau.
            </p>
        </div>
        <div class="md:flex-1 relative">
            <img src="IMAGE CASE/image21.jpg" alt="Gambar Tentang Kami" class="w-full h-auto rounded-lg shadow-lg">
        </div>
    </div>
</section>


    <footer class="main-footer-container">
        <div class="container">
            <div class="footer-content-wrapper">
                <div class="footer-column">
                    <h3 class="footer-column-title">Bantuan</h3>
                    <p class="footer-address">Jl. Kalibata 3 no 12/21, Bogor</p>
                    <p class="footer-link-item">ysfradana23@gmail.com</p>
                    <p class="footer-link-item">+62 85881816690</p>
                </div>
                <div class="footer-column">
                    <h3 class="footer-column-title">Akun</h3>
                    <a class="footer-link-item" href="login.php">Masuk</a>
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
        </div>
    </footer>

</body>
</html>
