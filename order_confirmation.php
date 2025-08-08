<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['order_id'])) {
    header("Location: homepage.php");
    exit();
}

$order_id = $_GET['order_id'];
$user_id = $_SESSION['user_id'];

// Handle payment proof upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['payment_proof'])) {
    $target_dir = "payment_proofs/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION);
    $file_name = "proof_" . $order_id . "_" . time() . "." . $file_extension;
    $target_file = $target_dir . $file_name;
    
    if (move_uploaded_file($_FILES['payment_proof']['tmp_name'], $target_file)) {
        $update_sql = "UPDATE orders SET payment_proof = ? WHERE id = ? AND user_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("sii", $file_name, $order_id, $user_id);
        $update_stmt->execute();
        $update_stmt->close();
        
        $_SESSION['success'] = "Bukti pembayaran berhasil diupload";
        header("Location: order_confirmation.php?order_id=" . $order_id);
        exit();
    } else {
        $_SESSION['error'] = "Gagal mengupload bukti pembayaran";
    }
}

// Get order details with payment deadline
$order_sql = "SELECT o.*, ua.*, 
              TIMESTAMPDIFF(SECOND, NOW(), o.payment_deadline) as seconds_remaining,
              o.payment_deadline > NOW() as is_payment_valid
              FROM orders o
              JOIN user_addresses ua ON o.address_id = ua.id
              WHERE o.id = ? AND o.user_id = ?";
$order_stmt = $conn->prepare($order_sql);
$order_stmt->bind_param("ii", $order_id, $user_id);
$order_stmt->execute();
$order = $order_stmt->get_result()->fetch_assoc();

// Get order items
$items_sql = "SELECT oi.*, p.product_name, p.product_image
              FROM order_items oi
              JOIN products p ON oi.product_id = p.id
              WHERE oi.order_id = ?";
$items_stmt = $conn->prepare($items_sql);
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if ($order['payment_method'] === 'Bank Transfer' && 
    empty($order['payment_proof']) && 
    !$order['is_payment_valid'] && 
    $order['status'] == 'pending') {
    
    // Update status order menjadi cancelled
    $update_sql = "UPDATE orders SET status = 'cancelled' WHERE id = ? AND status = 'pending'";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("i", $order_id);
    $update_stmt->execute();
    $update_stmt->close();
    
    // Refresh data order
    $order_stmt->execute();
    $order = $order_stmt->get_result()->fetch_assoc();
    
    // Tambahkan notifikasi
    $notif_sql = "INSERT INTO notifications (user_id, message) VALUES (?, 'Pesanan #".$order['order_number']." dibatalkan karena melewati batas waktu pembayaran')";
    $notif_stmt = $conn->prepare($notif_sql);
    $notif_stmt->bind_param("i", $user_id);
    $notif_stmt->execute();
    $notif_stmt->close();
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - ArteCase</title>
    <link rel="stylesheet" href="style_index1.css">
    <link rel="stylesheet" href="style_order_confirmation.css">
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
                    <li><a href="kontak.php">Kontak</a></li>
                    <li><a href="tentangkami.php">Tentang kami</a></li>
                    <li><a href="daftar.php">Daftar</a></li>
                </ul>
            </nav>
            <div class="nav-utility">
                <a href="keranjang.php" class="cart">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <span class="cart-count">
                        <?php 
                        $count_sql = "SELECT COUNT(*) as count FROM cart WHERE user_id = ?";
                        $count_stmt = $conn->prepare($count_sql);
                        $count_stmt->bind_param("i", $user_id);
                        $count_stmt->execute();
                        $count_result = $count_stmt->get_result();
                        $count_row = $count_result->fetch_assoc();
                        echo $count_row['count'];
                        $count_stmt->close();
                        ?>
                    </span>
                </a>
                <a href="wishlist.php">
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
        <div class="confirmation-container">
            <div class="confirmation-icon">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <h1>Terima Kasih Atas Pesanan Anda!</h1>
            <p>Pesanan Anda telah diterima dan sedang diproses.</p>

            <!-- Timer Pembayaran -->
            <?php if ($order['payment_method'] === 'Bank Transfer' && empty($order['payment_proof'])): ?>
            <div class="payment-timer">
                <h3>Selesaikan Pembayaran Dalam:</h3>
                <div id="countdown-timer" class="timer-display">
                   <?php 
                    if ($order['is_payment_valid']) {
                        $hours = floor($order['seconds_remaining'] / 3600);
                        $minutes = floor(($order['seconds_remaining'] % 3600) / 60);
                        $seconds = $order['seconds_remaining'] % 60;
                        echo sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
                    } 
                    if (empty($order['payment_deadline']) || strtotime($order['payment_deadline']) <= time()) {
                        $new_deadline = date('Y-m-d H:i:s', strtotime('+1 hour'));
                        $update_sql = "UPDATE orders SET payment_deadline = ? WHERE id = ?";
                        $update_stmt = $conn->prepare($update_sql);
                        $update_stmt->bind_param("si", $new_deadline, $order_id);
                        $update_stmt->execute();
                        $update_stmt->close();
                        
                        // Refresh data order
                        $order_stmt->execute();
                        $order = $order_stmt->get_result()->fetch_assoc();
                    } else {
                        echo "00:00:00";
                    }
                    ?>
                </div>
                <p class="timer-warning">Setelah waktu habis, pesanan akan dibatalkan secara otomatis.</p>
            </div>
            <?php elseif ($order['payment_method'] === 'Bank Transfer' && empty($order['payment_proof']) && !$order['is_payment_valid']): ?>
            <div class="payment-expired">
                <h3>Waktu Pembayaran Telah Habis</h3>
                <p>Maaf, waktu untuk mengunggah bukti pembayaran telah berakhir. Pesanan Anda telah dibatalkan.</p>
            </div>
            <?php endif; ?>
            
            <div class="order-details1">
                <h2>Detail Pesanan</h2>
                <p><strong>Nomor Pesanan:</strong> <?php echo htmlspecialchars($order['order_number']); ?></p>
                <p><strong>Tanggal:</strong> <?php echo date('d F Y', strtotime($order['created_at'])); ?></p>
                <p><strong>Total:</strong> RP. <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></p>
                <p><strong>Metode Pembayaran:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
                
                <?php if ($order['payment_method'] === 'Bank Transfer'): ?>
                    <div class="bank-details">
                        <h3>Instruksi Pembayaran</h3>
                        <p>Silakan transfer ke rekening berikut:</p>
                        <p><strong>Bank Mandiri</strong></p>
                        <p>No. Rekening: 1330020834214</p>
                        <p>Atas Nama: Yusuf Radana</p>
                        <p>Jumlah: Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></p>
                    </div>
                    
                    <?php if (empty($order['payment_proof'])): ?>
                        <div class="payment-proof-upload">
                            <h3>Upload Bukti Pembayaran</h3>
                            <form method="POST" enctype="multipart/form-data">
                                <input type="file" name="payment_proof" accept="image/*,.pdf" required>
                                <button type="submit" class="primary-button">Upload Bukti</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="payment-proof-uploaded">
                            <h3>Bukti Pembayaran Terupload</h3>
                            <p>Terima kasih telah mengupload bukti pembayaran.</p>
                            <a href="payment_proofs/<?php echo htmlspecialchars($order['payment_proof']); ?>" target="_blank" class="primary-button">Lihat Bukti</a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <h3>Alamat Pengiriman</h3>
                <p><?php echo htmlspecialchars($order['recipient_name']); ?></p>
                <p><?php echo htmlspecialchars($order['phone_number']); ?></p>
                <p><?php echo htmlspecialchars($order['address_line1']); ?></p>
                <?php if (!empty($order['address_line2'])): ?>
                    <p><?php echo htmlspecialchars($order['address_line2']); ?></p>
                <?php endif; ?>
                <p><?php echo htmlspecialchars($order['city'] . ', ' . $order['province'] . ' ' . $order['postal_code']); ?></p>
            </div>
            <div class="order-actions1">
                <?php if ($order['payment_method'] === 'Bank Transfer' && empty($order['payment_proof'])): ?>
                    <button class="order-button1" disabled>Lihat Pesanan Saya</button>
                    <p class="button-note">Silakan upload bukti pembayaran terlebih dahulu</p>
                <?php else: ?>
                    <a href="akun.php?view=orders" class="order-button1">Lihat Pesanan Saya</a>
                <?php endif; ?>
                <?php if ($order['payment_method'] === 'Bank Transfer' && empty($order['payment_proof']) && !$order['is_payment_valid']): ?>
                <p class="button-note">Pesanan telah dibatalkan karena melewati batas waktu pembayaran</p>
                <?php endif; ?>
            </div>
    </div>
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
    <?php if ($order['payment_method'] === 'Bank Transfer' && empty($order['payment_proof']) && $order['is_payment_valid']): ?>
    function updateTimer() {
        const timerElement = document.getElementById('countdown-timer');
        let seconds = <?php echo $order['seconds_remaining']; ?>;
        
        const timerInterval = setInterval(() => {
            seconds--;
            
            if (seconds <= 0) {
                clearInterval(timerInterval);
                timerElement.innerHTML = "00:00:00";
                // Refresh halaman untuk menampilkan pesan waktu habis
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
                return;
            }
            
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const remainingSeconds = seconds % 60;
            
            timerElement.innerHTML = 
                `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
        }, 1000);
    }
    
    document.addEventListener('DOMContentLoaded', updateTimer);
    <?php endif; ?>
</script>
</body>
</html>