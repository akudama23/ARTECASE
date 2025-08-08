<?php
session_start();
include 'connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
} else {
    error_log("Koneksi database berhasil");
}

// Handle remove action
if (isset($_POST['remove_item'])) {
    $checkout_id = $_POST['checkout_id'];
    
    // Mulai transaksi untuk memastikan konsistensi data
    $conn->begin_transaction();
    
    try {
        // Dapatkan product_id dan quantity dari item yang akan dihapus
        $get_product_sql = "SELECT product_id, quantity FROM checkout WHERE id = ? AND user_id = ?";
        $get_product_stmt = $conn->prepare($get_product_sql);
        $get_product_stmt->bind_param("ii", $checkout_id, $user_id);
        $get_product_stmt->execute();
        $product_result = $get_product_stmt->get_result();
        
        if ($product_result->num_rows === 0) {
            throw new Exception("Item tidak ditemukan atau tidak dimiliki oleh pengguna ini");
        }
        
        $product_data = $product_result->fetch_assoc();
        $product_id = $product_data['product_id'];
        $quantity = $product_data['quantity'];
        $get_product_stmt->close();

        // Dalam blok try, setelah begin_transaction()
        error_log("Memulai proses penghapusan item checkout ID: $checkout_id untuk user ID: $user_id");

        // Setelah mendapatkan data produk
        error_log("Produk ditemukan: ID $product_id, Quantity: $quantity");

        // Setelah penghapusan berhasil
        error_log("Item checkout berhasil dihapus. Mengembalikan stok...");
        
        // Hapus item dari checkout
        $delete_sql = "DELETE FROM checkout WHERE id = ? AND user_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("ii", $checkout_id, $user_id);
        
        if (!$delete_stmt->execute()) {
            throw new Exception("Gagal menghapus item dari checkout");
        }
        $delete_stmt->close();
        
        // Kembalikan stok produk
        $update_stock_sql = "UPDATE products SET stok = stok + ? WHERE id = ?";
        $update_stock_stmt = $conn->prepare($update_stock_sql);
        $update_stock_stmt->bind_param("ii", $quantity, $product_id);
        $update_stock_stmt->execute();
        $update_stock_stmt->close();
        
        $conn->commit();
        
        $_SESSION['success'] = "Produk berhasil dihapus dari checkout";
        // Redirect dengan JavaScript untuk menghindari header issues
        echo "<script>window.location.href = 'checkout.php';</script>";
        exit();
        
    } catch (Exception $e) {
    $conn->rollback();
    error_log("Error menghapus item checkout: " . $e->getMessage());
    $_SESSION['error'] = "Gagal menghapus item: " . $e->getMessage();
    // Redirect dengan JavaScript untuk menghindari header issues
    echo "<script>
        Swal.fire({
            title: 'Error!',
            text: '".addslashes($_SESSION['error'])."',
            icon: 'error',
            confirmButtonText: 'OK'
        }).then(() => {
            window.location.href = 'checkout.php';
        });
    </script>";
    exit();
    }
}

// Fetch checkout items with complete product details
$checkout_sql = "SELECT 
                c.id as checkout_id, 
                p.id as product_id, 
                p.product_name, 
                p.description,
                p.price, 
                p.product_image, 
                c.quantity,
                p.price * c.quantity as subtotal
             FROM checkout c
             JOIN products p ON c.product_id = p.id
             WHERE c.user_id = ?";
$checkout_stmt = $conn->prepare($checkout_sql);
$checkout_stmt->bind_param("i", $user_id);
$checkout_stmt->execute();
$checkout_result = $checkout_stmt->get_result();
$checkout_items = $checkout_result->fetch_all(MYSQLI_ASSOC);

// Calculate total price
$total_price = 0;
foreach ($checkout_items as $item) {
    $total_price += $item['subtotal'];
}

// Fetch user addresses
$address_sql = "SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_primary DESC";
$address_stmt = $conn->prepare($address_sql);
$address_stmt->bind_param("i", $user_id);
$address_stmt->execute();
$address_result = $address_stmt->get_result();
$addresses = $address_result->fetch_all(MYSQLI_ASSOC);

$checkout_stmt->close();
$address_stmt->close();

// Handle checkout submission
if (isset($_POST['place_order'])) {
    // Get user's primary address
    $address_sql = "SELECT * FROM user_addresses WHERE user_id = ? AND is_primary = TRUE LIMIT 1";
    $address_stmt = $conn->prepare($address_sql);
    $address_stmt->bind_param("i", $user_id);
    $address_stmt->execute();
    $address_result = $address_stmt->get_result();
    $address = $address_result->fetch_assoc();
    $address_stmt->close();
    
    if (!$address) {
        $_SESSION['error'] = "Tolong pilih alamat utama Anda";
        header("Location: checkout.php");
        exit();
    }

    //handle payment_deadline
    $payment_deadline = date('Y-m-d H:i:s', strtotime('+1 hour')); // Tambah 1 jam dari sekarang
    // $order_sql = "INSERT INTO orders (user_id, order_number, total_amount, status, payment_method, payment_proof, address_id, created_at, updated_at, payment_deadline) 
    //         VALUES (?, ?, ?, 'pending', ?, NULL, ?, NOW(), NOW(), ?)";
    // $stmt = $conn->prepare($order_sql);
    // $stmt->bind_param("s", $payment_deadline);
    
    // Calculate total from checkout items
    $total_amount = 0;
    foreach ($checkout_items as $item) {
        $total_amount += $item['subtotal'];
    }
    
    // Create order
    $payment_method = $_POST['payment_method'];
    $order_number = 'ORD-' . time() . '-' . rand(1000, 9999);

    $order_sql = "INSERT INTO orders (user_id, order_number, total_amount, status, payment_method, payment_proof, address_id, created_at, updated_at, payment_deadline)
        VALUES (?, ?, ?, 'pending', ?, NULL, ?, NOW(), NOW(), DATE_ADD(NOW(), INTERVAL 1 HOUR))";
    $order_stmt = $conn->prepare($order_sql);
    $order_stmt->bind_param("isdsi", $user_id, $order_number, $total_amount, $payment_method, $address['id']);
    $order_stmt->execute();
    $order_id = $order_stmt->insert_id;
    $order_stmt->close();
    
    // Add order items
    foreach ($checkout_items as $item) {
        $order_item_sql = "INSERT INTO order_items (order_id, product_id, quantity, price, created_at)
                           VALUES (?, ?, ?, ?, NOW())";
        $order_item_stmt = $conn->prepare($order_item_sql);
        $order_item_stmt->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
        $order_item_stmt->execute();
        $order_item_stmt->close();
    }
    
    // Clear checkout items after order is placed
    $delete_checkout_sql = "DELETE FROM checkout WHERE user_id = ?";
    $delete_checkout_stmt = $conn->prepare($delete_checkout_sql);
    $delete_checkout_stmt->bind_param("i", $user_id);
    $delete_checkout_stmt->execute();
    $delete_checkout_stmt->close();
    
    header("Location: order_confirmation.php?order_id=$order_id");
    exit();
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toko Aksesoris Ponsel - Checkout</title>
    <link rel="stylesheet" href="style_index1.css">
    <link rel="stylesheet" href="style_checkout.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Poppins:wght@400;500;600&family=Trirong:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
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
        <div class="section-header">
            <div class="section-tag">
                <div class="tag-color"></div>
                <div class="section-subtitle">Checkout</div>
            </div>
            <h2 class="section-title">Lengkapi Pesanan Anda</h2>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
        <script>
            Swal.fire({
                title: 'Sukses!',
                text: '<?php echo $_SESSION['success']; ?>',
                icon: 'success',
                confirmButtonText: 'OK'
            }).then(() => {
                window.location.href = 'checkout.php';
            });
        </script>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="checkout-container">
            <div class="checkout-items">
                <h3 class="checkout-subtitle">Produk yang Dipesan</h3>
                
                <?php if (empty($checkout_items)): ?>
                    <div class="empty-cart">
                        <p>Tidak ada item di checkout.</p>
                        <a href="produk.php" class="primary-button">Telusuri Produk</a>
                    </div>
                <?php else: ?>
                        <?php foreach ($checkout_items as $item): ?>
                            <div class="checkout-item">
                                <div class="item-image">
                                    <?php
                                    $imagePath = $item['product_image'];
                                    if (!preg_match('/^https?:\/\//i', $imagePath) && !file_exists($imagePath)) {
                                        $imagePath = 'IMAGE CASE/' . $imagePath;
                                    }
                                    ?>
                                    <img src="<?php echo htmlspecialchars($imagePath); ?>"
                                        alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                        onerror="this.src='IMAGE CASE/no-image.jpg';this.alt='Gambar tidak tersedia'">
                                </div>
                                
                                <div class="item-details">
                                    <h4><?php echo htmlspecialchars($item['product_name']); ?></h4>
                                    <div class="item-price">Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></div>
                                </div>
                                
                                
                                <div class="item-subtotal">
                                    <div>Subtotal:</div>
                                    <div class="subtotal-amount">Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?></div>   
                                </div>
                                <form method="POST" action="checkout.php" class="remove-form1">
                                    <input type="hidden" name="checkout_id" value="<?php echo $item['checkout_id']; ?>">
                                    <button type="submit" name="remove_item" class="remove-btn">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                <?php endif; ?>
            </div>
             
            <div class="checkout-summary">
                <h3 class="checkout-subtitle">Ringkasan Pesanan</h3>
                
                <div class="summary-section">
                    <div class="summary-row">
                        <span>Subtotal Produk</span>
                        <span>Rp <?php echo number_format($total_price, 0, ',', '.'); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Biaya Pengiriman</span>
                        <span>Rp 15.000</span>
                    </div>
                    <div class="summary-row total">
                        <span>Total Pembayaran</span>
                        <span>Rp <?php echo number_format($total_price + 15000, 0, ',', '.'); ?></span>
                    </div>
                </div>
                
                <form method="POST" action="checkout.php" class="checkout-form">
                    <div class="form-section">
                        <h4>Alamat Pengiriman</h4>
                        <?php if (empty($addresses)): ?>
                            <p class="no-address">Anda belum memiliki alamat. <a href="akun.php">Tambahkan alamat</a></p>
                        <?php else: ?>
                            <div class="address-select">
                                <?php foreach ($addresses as $address): ?>
                                    <div class="address-option <?php echo $address['is_primary'] ? 'primary' : ''; ?>">
                                        <input type="radio" name="address_id" id="address_<?php echo $address['id']; ?>" 
                                               value="<?php echo $address['id']; ?>" <?php echo $address['is_primary'] ? 'checked' : ''; ?>>
                                        <label for="address_<?php echo $address['id']; ?>">
                                            <strong><?php echo htmlspecialchars($address['recipient_name']); ?></strong><br>
                                            <?php echo htmlspecialchars($address['phone_number']); ?><br>
                                            <?php echo htmlspecialchars($address['address_line1']); ?><br>
                                            <?php if (!empty($address['address_line2'])) echo htmlspecialchars($address['address_line2']) . '<br>'; ?>
                                            <?php echo htmlspecialchars($address['city'] . ', ' . $address['province'] . ' ' . $address['postal_code']); ?>
                                            <?php if ($address['is_primary']): ?>
                                                <span class="primary-badge">Utama</span>
                                            <?php endif; ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-section">
                        <h4>Metode Pembayaran</h4>
                        <div class="payment-methods">
                            <div class="payment-option">
                                <input type="radio" name="payment_method" id="cod" value="COD" checked>
                                <label for="cod">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <span>Bayar di Tempat (COD)</span>
                                </label>
                            </div>
                            <div class="payment-option">
                                <input type="radio" name="payment_method" id="bank_transfer" value="Bank Transfer">
                                <label for="bank_transfer">
                                    <i class="fas fa-university"></i>
                                    <span>Transfer Bank</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" name="place_order" class="checkout-button" <?php echo empty($checkout_items) || empty($addresses) ? 'disabled' : ''; ?>>
                        Buat Pesanan
                    </button>
                </form>
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