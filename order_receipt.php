<?php
session_start();
include 'connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if order_id is provided
if (!isset($_GET['order_id'])) {
    header("Location: my_orders.php");
    exit();
}

$order_id = $_GET['order_id'];
$user_id = $_SESSION['user_id'];

// Fetch order details
$order_sql = "SELECT o.*, ua.* 
              FROM orders o
              JOIN user_addresses ua ON o.address_id = ua.id
              WHERE o.id = ? AND o.user_id = ?";
$order_stmt = $conn->prepare($order_sql);
$order_stmt->bind_param("ii", $order_id, $user_id);
$order_stmt->execute();
$order = $order_stmt->get_result()->fetch_assoc();

if (!$order) {
    $_SESSION['error'] = "Pesanan tidak ditemukan";
    header("Location: my_orders.php");
    exit();
}

// Fetch order items
$items_sql = "SELECT oi.*, p.product_name, p.product_image
              FROM order_items oi
              JOIN products p ON oi.product_id = p.id
              WHERE oi.order_id = ?";
$items_stmt = $conn->prepare($items_sql);
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Generate random tracking number
$tracking_number = 'TRK-' . strtoupper(substr(md5(uniqid()), 0, 10));
$shipping_date = date('Y-m-d', strtotime($order['created_at'] . ' +1 day'));
$estimated_delivery = date('Y-m-d', strtotime($shipping_date . ' +3 days'));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resi Pengiriman - ArteCase</title>
    <link rel="stylesheet" href="style_index1.css">
    <link rel="stylesheet" href="style_receipt.css">
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
        <div class="receipt-container">
            <div class="receipt-header">
                <h1>Resi Pengiriman</h1>
                <div class="receipt-meta">
                    <div class="meta-item">
                        <span>Nomor Pesanan:</span>
                        <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
                    </div>
                    <div class="meta-item">
                        <span>Tanggal Pesanan:</span>
                        <strong><?php echo date('d F Y', strtotime($order['created_at'])); ?></strong>
                    </div>
                    <div class="meta-item">
                        <strong class="status-<?php echo strtolower($order['status']); ?>">
                            <?php 
                            if ($order['status'] == 'cancelled' && $order['payment_method'] === 'Bank Transfer') {
                                echo "Dibatalkan (Waktu Pembayaran Habis)";
                            } else {
                                echo htmlspecialchars($order['status']);
                            }
                            ?>
                        </strong>
                    </div>
                </div>
            </div>

            <div class="receipt-body">
                <div class="shipping-info">
                    <h2>Informasi Pengiriman</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <span>Nomor Resi:</span>
                            <strong><?php echo $tracking_number; ?></strong>
                        </div>
                        <div class="info-item">
                            <span>Tanggal Pengiriman:</span>
                            <strong><?php echo date('d F Y', strtotime($shipping_date)); ?></strong>
                        </div>
                        <div class="info-item">
                            <span>Estimasi Tiba:</span>
                            <strong><?php echo date('d F Y', strtotime($estimated_delivery)); ?></strong>
                        </div>
                        <div class="info-item">
                            <span>Kurir:</span>
                            <strong>JNE REGULER</strong>
                        </div>
                    </div>
                </div>

                <div class="address-section">
                    <div class="address-card">
                        <h3>Alamat Pengiriman</h3>
                        <p><strong><?php echo htmlspecialchars($order['recipient_name']); ?></strong></p>
                        <p><?php echo htmlspecialchars($order['phone_number']); ?></p>
                        <p><?php echo htmlspecialchars($order['address_line1']); ?></p>
                        <?php if (!empty($order['address_line2'])): ?>
                            <p><?php echo htmlspecialchars($order['address_line2']); ?></p>
                        <?php endif; ?>
                        <p><?php echo htmlspecialchars($order['city'] . ', ' . $order['province'] . ' ' . $order['postal_code']); ?></p>
                    </div>
                </div>

                <div class="items-section">
                    <h2>Detail Produk</h2>
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Harga</th>
                                <th>Jumlah</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="product-info"></div>
                                        <?php 
                                            $image_file = htmlspecialchars($item['product_image']);
                                            $image_path = 'IMAGE CASE/' . $image_file;
                                            $fallback_image = 'IMAGE CASE/no-image.jpg';
                                            
                                            // Cek apakah file gambar ada
                                            if (!empty($image_file) && file_exists($image_path)) {
                                                $display_image = $image_path;
                                            } else {
                                                $display_image = $fallback_image;
                                            }
                                            ?>
                                            <img src="<?php echo $display_image; ?>" 
                                                alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                                style="width: 80px; height: auto;">
                                            <span><?php echo htmlspecialchars($item['product_name']); ?></span>
                                        </div>
                                    </td>
                                    <td>Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td>Rp <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="summary-section">
                    <div class="summary-row">
                        <span>Subtotal Produk</span>
                        <span>Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Biaya Pengiriman</span>
                        <span>Rp 15.000</span>
                    </div>
                    <div class="summary-row total">
                        <span>Total Pembayaran</span>
                        <span>Rp <?php echo number_format($order['total_amount'] + 15000, 0, ',', '.'); ?></span>
                    </div>
                </div>

                <div class="payment-info">
                    <h2>Informasi Pembayaran</h2>
                    <p>Metode Pembayaran: <strong><?php echo htmlspecialchars($order['payment_method']); ?></strong></p>
                    <?php if ($order['payment_method'] === 'Bank Transfer'): ?>
                        <p>Silakan transfer ke rekening berikut:</p>
                        <div class="bank-details">
                            <p><strong>Bank Mandiri</strong></p>
                            <p>No. Rekening: 1330020834214</p>
                            <p>Atas Nama: Yusuf Radana</p>
                            <p>Jumlah: Rp <?php echo number_format($order['total_amount'] + 15000, 0, ',', '.'); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="payment_proof">
                            <?php if (!empty($order['payment_proof'])): ?>
                    <div class="payment-proof">
                        <h3>Bukti Pembayaran</h3>
                        <a href="payment_proofs/<?php echo htmlspecialchars($order['payment_proof']); ?>" target="_blank">
                            <img src="payment_proofs/<?php echo htmlspecialchars($order['payment_proof']); ?>" alt="Bukti Pembayaran" style="max-width: 300px;">
                        </a>
                    </div>
                <?php endif; ?>
                        </div>
                </div>
            </div>

            <div class="receipt-footer">
                <p>Terima kasih telah berbelanja di ArteCase. Jika Anda memiliki pertanyaan, silakan hubungi kami.</p>
                <div class="action-buttons">
                    <a href="akun.php?view=orders" class="secondary-button">Kembali ke Pesanan Saya</a>
                    <button onclick="window.print()" class="primary-button">Cetak Resi</button>
                </div>
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
</body>
</html>