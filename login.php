<?php
session_start();  // Memulai session untuk menyimpan data login
include 'connection.php';  // Menghubungkan ke database

$error = '';  // Variabel untuk menyimpan pesan error
$login_success = false;  // Flag untuk menandai login berhasil

if (isset($_SESSION['user_id'])) {
    header("Location: homepage.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {  // Jika form login dikirim
    $email = $_POST['email'];  // Ambil email dari form
    $password = $_POST['password'];  // Ambil password dari form

    // Cari user berdasarkan email
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {  // Jika user ditemukan
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {  // Verifikasi password
            // Simpan data user di session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['login_success'] = true;  // Tandai login berhasil
            
            // Redirect ke homepage
            header("Location: homepage.php");
            exit();
        } else {
            $error = "Email atau password salah";  // Password tidak cocok
        }
    } else {
        $error = "Email atau password salah";  // User tidak ditemukan
    }

    if (isset($_SESSION['user_id'])) {
        $homepage = 'homepage.php';
    } else {
        $homepage = 'index.php';
    }
}
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1" name="viewport"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Artcase Login</title>
    <link href="style_login1.css" rel="stylesheet"/>
</head>
<body class="body-base">
    <div class="top-promo-bar"></div>
    <header class="header-base">
        <div class="header-content-wrapper">
        <div class="header-logo-group">
            <img src="IMAGE CASE/LOGO ARTECASE.jpg" alt="Company Logo" class="header-logo-img">
        </div>
        <nav class="header-nav-menu">
            <a class="header-nav-link" href="homepage.php">Beranda</a>
            <a class="header-nav-link" href="produk.php">Produk</a>
            <a class="header-nav-link" href="kontak.php">Kontak</a>
            <a class="header-nav-link" href="tentangkami.php">Tentang kami</a>
            <a class="header-nav-link" href="#">Masuk</a>
        </nav>
        </div>
    </header>
<main class="main-content-area container" >
        <div class="main-image-section">
            <img alt="Shopping cart with smartphone and pink shopping bags on light blue background" class="main-image" src="https://storage.googleapis.com/a1aa/image/7ff3eae4-d664-4c38-9cea-e9c2fa0045e5.jpg"/>
        </div>
        <form action="login.php" class="main-login-form" method="POST" style="flex: 1; margin-left: 20px;">
            <h2 class="form-title" style="font-size: 24px; color: #333; margin-bottom: 30px; text-align: left;">Masukkan akun anda!</h2>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="form-group">
                <input class="form-input" name="email" placeholder="Email" required type="email"/>
            </div>
            <div class="form-group">
                <input class="form-input" name="password" placeholder="Password" required type="password"/>
            </div>
            <div class="form-actions">
                <button class="login-button" type="submit">Masuk</button>
                <a class="forgot-password-link" href="daftar.php">Belum Punya Akun ? Daftar Disini</a>
            </div>
        </form>
    </main>
<footer class="footer-base">
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
</body>
</html>