<?php
session_start();

// Redirect berdasarkan status login
if (isset($_SESSION['user_id'])) {
    // Jika sudah login, pastikan kembali ke homepage.php
    $homepage = 'index.php';
} else {
    // Jika belum login, pastikan kembali ke index.php
    $homepage = 'hompage.php';
}

// Tangkap data form jika ada
$name = $email = $phone = $message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $phone = htmlspecialchars($_POST['phone']);
    $message = htmlspecialchars($_POST['message']);
    
    // Redirect ke Gmail dengan data form
    $gmail_url = "https://mail.google.com/mail/?view=cm&fs=1&to=ysfradana23@gmail.com" .
                 "&su=" . urlencode("Pesan dari $name") .
                 "&body=" . urlencode("Nama: $name\nEmail: $email\nTelepon: $phone\n\nPesan:\n$message");
    header("Location: $gmail_url");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toko Aksesoris Ponsel - Kontak</title>
    <link rel="stylesheet" href="style_index1.css">
    <link rel="stylesheet" href="style_kontak.css"> 
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Poppins:wght@400;500;600&family=Trirong:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="top-promo-bar"></div>
    <header class="header">
        <div class="container-navbar-wrapper">
            <div class="logo img-kontak">
                <img src="IMAGE CASE/LOGO ARTECASE.jpg" alt="Company Logo">
            </div>
            <nav class="main-nav">
                <ul>
                    <li><a href="homepage.php">Beranda</a></li>
                    <li><a href="produk.php">Produk</a></li>
                    <li><a href="kontak.php" class="nav-active">Kontak</a></li>
                    <li><a href="tentangkami.php">Tentang kami</a></li>
                    <?php if (!isset($_SESSION['user_id'])): ?>
                      <li><a href="login.php">Masuk</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
        <div class="header-divider"></div>
    </header>

    <section class="section container">
        <h2 class="section-title">Kontak Kami</h2>
        <nav class="mb-10 text-sm text-gray-600 font-normal flex flex-wrap gap-1">
            <span>Beranda</span>
            <span>/</span>
            <span class="font-semibold text-black">Kontak</span>
        </nav>

        <main class="grid grid-cols-1 md:grid-cols-12 gap-8">
            <section class="md:col-span-4 bg-white shadow-md rounded-md p-6 flex flex-col gap-8">
                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <div class="bg-red-600 text-white w-9 h-9 rounded-full flex items-center justify-center">
                            <i class="fas fa-phone-alt text-sm"></i>
                        </div>
                        <h2 class="font-semibold text-black text-base">Hubungi Kami</h2>
                    </div>
                    <p class="text-sm mb-2 leading-relaxed">
                        Kami melayani 24/7, 7 hari seminggu
                    </p>
                    <p class="text-sm font-semibold mb-4">HP: +62 85881816690</p>
                    <hr class="border-gray-300" />
                </div>

                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <div class="bg-red-600 text-white w-9 h-9 rounded-full flex items-center justify-center">
                            <i class="fas fa-envelope text-sm"></i>
                        </div>
                        <h2 class="font-semibold text-black text-base">Email Kami</h2>
                    </div>
                    <p class="text-xs mb-2 leading-relaxed">
                        Isi formulir kami dan kami akan menghubungi Anda dalam waktu 24 jam.
                    </p>
                    <p class="text-xs mb-1">Emails: ysfradana23@gmail.com</p>
                    <p class="text-xs">Emails: ysfradana23@gmail.com</p>
                </div>
            </section>

            <section class="md:col-span-8 bg-white shadow-md rounded-md p-6 flex flex-col gap-4">
                <form class="w-full flex flex-col gap-4" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <input
                            type="text"
                            name="name"
                            placeholder="Nama *"
                            value="<?php echo $name; ?>"
                            class="flex-1 bg-gray-100 text-xs text-gray-400 placeholder:text-gray-400 rounded-md px-4 py-3 focus:outline-none"
                            required
                        />
                        <input
                            type="email"
                            name="email"
                            placeholder="Email *"
                            value="<?php echo $email; ?>"
                            class="flex-1 bg-gray-100 text-xs text-gray-400 placeholder:text-gray-400 rounded-md px-4 py-3 focus:outline-none"
                            required
                        />
                        <input
                            type="tel"
                            name="phone"
                            placeholder="Nomor HP *"
                            value="<?php echo $phone; ?>"
                            class="flex-1 bg-gray-100 text-xs text-gray-400 placeholder:text-gray-400 rounded-md px-4 py-3 focus:outline-none"
                            required
                        />
                    </div>
                    <textarea
                        name="message"
                        placeholder="Pesan Kamu"
                        rows="6"
                        class="bg-gray-100 text-xs text-gray-400 placeholder:text-gray-400 rounded-md px-4 py-3 resize-none focus:outline-none"
                    ><?php echo $message; ?></textarea>
                    <div class="flex justify-end">
                        <button
                            type="submit"
                            class="bg-red-600 text-white text-xs font-normal rounded-md px-6 py-3 hover:bg-red-700 transition-colors"
                        >
                            Kirim Pesan
                        </button>
                    </div>
                </form>
            </section>
        </main>
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
    </footer>

</body>
</html>