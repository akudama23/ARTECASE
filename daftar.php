<?php
session_start();
include 'connection.php';

// Redirect logged-in users
if (isset($_SESSION['user_id'])) {
    header("Location: homepage.php");
    exit();
}

// Get form data from session if exists
$form_data = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : [
    'username' => '',
    'email' => ''
];

// Clear form data after use
unset($_SESSION['form_data']);

// Get error/success messages
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';

// Clear messages after displaying
unset($_SESSION['error']);
unset($_SESSION['success']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toko Aksesoris Ponsel - Daftar</title>
    <link rel="stylesheet" href="style_index1.css">
    <link rel="stylesheet" href="style_daftar.css"> 
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Poppins:wght@400;500;600&family=Trirong:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
            text-align: center;
        }
        .alert-success {
            color: #3c763d;
            background-color: #dff0d8;
            border-color: #d6e9c6;
        }
        .alert-error {
            color: #a94442;
            background-color: #f2dede;
            border-color: #ebccd1;
        }
    </style>
</head>
<body>
    <div class="top-promo-bar"></div>
    <header class="header-daftar">
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
                    <li><a href="daftar.php" class="nav-active">Daftar</a></li>
                </ul>
            </nav>
        </div>
        <div class="header-divider"></div>
    </header>

    <section class="section container">
        <h2 class="section-title">Daftar Akun Baru</h2>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="flex-container">
            <div class="image-container">
                <img alt="Shopping cart with smartphone and pink shopping bags on light blue background" class="main-image" src="https://storage.googleapis.com/a1aa/image/7ff3eae4-d664-4c38-9cea-e9c2fa0045e5.jpg" style="max-width: 600px; height: auto;"/>
            </div>
            <form action="proses_daftar.php" method="POST" class="form-daftar">
                <div class="form-group">
                    <label for="username">Nama Pengguna</label>
                    <input type="text" id="username" name="username" required 
                           value="<?php echo htmlspecialchars($form_data['username']); ?>">
                    <small>3-50 karakter</small>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo htmlspecialchars($form_data['email']); ?>">
                </div>
                <div class="form-group">
                    <label for="password">Kata Sandi</label>
                    <input type="password" id="password" name="password" required minlength="8">
                    <small>Minimal 8 karakter</small>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Konfirmasi Kata Sandi</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                </div>
                <button type="submit" class="btn">Daftar</button>
            </form>
        </div>
        <p class="text-center mt-4">Sudah punya akun? <a href="login.php">Masuk di sini</a></p>
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