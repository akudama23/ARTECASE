<?php
session_start();
include 'connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle remove action
if (isset($_GET['remove'])) {
    $cart_id = $_GET['remove'];
    
    // Delete product from cart using cart.id
    $delete_sql = "DELETE FROM cart WHERE id = ? AND user_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("ii", $cart_id, $user_id);
    $delete_stmt->execute();
    $delete_stmt->close();
    
    header("Location: keranjang.php");
    exit();
}

// Handle quantity update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quantity'])) {
    $cart_id = $_POST['cart_id'];
    $new_quantity = (int)$_POST['quantity'];
    
    // Validate quantity
    if ($new_quantity > 0) {
        $update_sql = "UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("iii", $new_quantity, $cart_id, $user_id);
        $update_stmt->execute();
        $update_stmt->close();
    }
    
    // Return JSON response for AJAX requests
    if (isset($_POST['ajax'])) {
        // Calculate new subtotal
        $subtotal_sql = "SELECT p.price * ? as subtotal 
                         FROM cart c
                         JOIN products p ON c.product_id = p.id
                         WHERE c.id = ? AND c.user_id = ?";
        $subtotal_stmt = $conn->prepare($subtotal_sql);
        $subtotal_stmt->bind_param("iii", $new_quantity, $cart_id, $user_id);
        $subtotal_stmt->execute();
        $subtotal_result = $subtotal_stmt->get_result();
        $subtotal_row = $subtotal_result->fetch_assoc();
        $subtotal = $subtotal_row['subtotal'] ?? 0;
        $subtotal_stmt->close();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'subtotal' => $subtotal,
            'formatted_subtotal' => 'RP. ' . number_format($subtotal, 0, ',', '.')
        ]);
        exit();
    }
    
    header("Location: keranjang.php");
    exit();
}

// Handle move to checkout for selected items
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['move_selected_to_checkout'])) {
    $selected_items = $_POST['selected_items'] ?? [];
    $response = ['success' => false, 'message' => ''];
    
    if (empty($selected_items)) {
        $response['message'] = 'Tidak ada item yang dipilih';
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
    
    // Convert string to array if needed
    if (is_string($selected_items)) {
        $selected_items = explode(',', $selected_items);
    }
    
    // Prepare placeholders for the query
    $placeholders = implode(',', array_fill(0, count($selected_items), '?'));
    $types = str_repeat('i', count($selected_items));
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // 1. Get selected cart items
        $cart_sql = "SELECT product_id, quantity FROM cart 
                    WHERE id IN ($placeholders) AND user_id = ?";
        $cart_stmt = $conn->prepare($cart_sql);
        
        // Bind parameters
        $params = array_merge($selected_items, [$user_id]);
        $cart_stmt->bind_param($types . 'i', ...$params);
        $cart_stmt->execute();
        $cart_result = $cart_stmt->get_result();
        $cart_items = $cart_result->fetch_all(MYSQLI_ASSOC);
        $cart_stmt->close();
        
        if (empty($cart_items)) {
            throw new Exception("Tidak ada item yang valid untuk dipindahkan");
        }
        
        // 2. Check stock for all items
        foreach ($cart_items as $item) {
            $stock_sql = "SELECT stok FROM products WHERE id = ?";
            $stock_stmt = $conn->prepare($stock_sql);
            $stock_stmt->bind_param("i", $item['product_id']);
            $stock_stmt->execute();
            $stock_result = $stock_stmt->get_result();
            $stock = $stock_result->fetch_assoc();
            $stock_stmt->close();
            
            if ($stock['stok'] < $item['quantity']) {
                throw new Exception("Stok produk tidak mencukupi untuk salah satu item");
            }
        }
        
        // 3. Add to checkout table
        foreach ($cart_items as $item) {
            $check_sql = "INSERT INTO checkout (user_id, product_id, quantity) 
                         VALUES (?, ?, ?)
                         ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("iii", $user_id, $item['product_id'], $item['quantity']);
            
            if (!$check_stmt->execute()) {
                throw new Exception("Gagal menambahkan ke checkout");
            }
            $check_stmt->close();
        }
        
        // 4. Remove from cart
        $delete_sql = "DELETE FROM cart WHERE id IN ($placeholders) AND user_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param($types . 'i', ...$params);
        
        if (!$delete_stmt->execute()) {
            throw new Exception("Gagal menghapus dari keranjang");
        }
        $delete_stmt->close();
        
        // Commit transaction
        $conn->commit();
        
        $response['success'] = true;
        $response['message'] = 'Item berhasil dipindahkan ke checkout';
    } catch (Exception $e) {
        $conn->rollback();
        $response['message'] = $e->getMessage();
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Fetch cart items
$cart_sql = "SELECT 
                c.id as cart_id, 
                p.id as product_id, 
                p.product_name, 
                p.description,
                p.price, 
                p.product_image, 
                c.quantity,
                p.price * c.quantity as subtotal
             FROM cart c
             JOIN products p ON c.product_id = p.id
             WHERE c.user_id = ?";
$cart_stmt = $conn->prepare($cart_sql);
$cart_stmt->bind_param("i", $user_id);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();
$cart_items = $cart_result->fetch_all(MYSQLI_ASSOC);

// Calculate total price
$total_sql = "SELECT SUM(p.price * c.quantity) as total 
              FROM cart c
              JOIN products p ON c.product_id = p.id
              WHERE c.user_id = ?";
$total_stmt = $conn->prepare($total_sql);
$total_stmt->bind_param("i", $user_id);
$total_stmt->execute();
$total_result = $total_stmt->get_result();
$total_row = $total_result->fetch_assoc();
$total_price = $total_row['total'] ?? 0;

$cart_stmt->close();
$total_stmt->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toko Aksesoris Ponsel - Keranjang</title>
    <link rel="stylesheet" href="style_index1.css">
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
                    <li><a href="homepage.php">Beranda</a></li>
                    <li><a href="produk.php">Produk</a></li>
                    <li><a href="kontak.php">Kontak</a></li>
                    <li><a href="tentangkami.php">Tentang kami</a></li>
                    <!-- <li><a href="daftar.php">Daftar</a></li> -->
                </ul>
            </nav>
            <div class="nav-utility">
                <a href="keranjang.php" class="cart nav-active">
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
                <div class="section-subtitle">Keranjang</div>
            </div>
            <h2 class="section-title">Barang di Keranjang Anda</h2>
        </div>
        
        <?php if (empty($cart_items)): ?>
            <div class="empty-cart">
                <p>Keranjang Anda kosong.</p>
                <a href="produk.php" class="primary-button">Telusuri Produk</a>
                <a href="checkout.php" class="primary-button" style="margin-top: 10px;">Lanjut ke Checkout</a>
            </div>
        <?php else: ?>
            <form id="checkoutForm" method="POST">
                <div class="cart-items-container">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item">
                            <div class="cart-item-checkbox">
                                <input type="checkbox" name="selected_items[]" value="<?php echo $item['cart_id']; ?>" 
                                       class="item-checkbox"
                                       data-price="<?php echo $item['price']; ?>"
                                       data-quantity="<?php echo $item['quantity']; ?>">
                            </div>
                            
                            <div class="cart-item-image">
                                <?php
                                $imagePath = $item['product_image'];
                                if (!preg_match('/^https?:\/\//i', $imagePath) && !file_exists($imagePath)) {
                                    $imagePath = 'IMAGE CASE/' . $imagePath;
                                }
                                ?>
                                <a href="detail_produk.php?id=<?php echo $item['product_id']; ?>">
                                    <img src="<?php echo htmlspecialchars($imagePath); ?>" 
                                        alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                        onerror="this.src='IMAGE CASE/no-image.jpg';this.alt='Gambar tidak tersedia'">
                                </a>
                            </div>
                                            
                            <div class="cart-item-details">
                                <h3><?php echo htmlspecialchars($item['product_name']); ?></h3>
                                <?php if (!empty($item['description'])): ?>
                                    <p class="product-description"><?php echo htmlspecialchars($item['description']); ?></p>
                                <?php endif; ?>
                                
                                <div class="cart-item-price">
                                    RP. <?php echo number_format($item['price'], 0, ',', '.'); ?>
                                </div>
                                
                                <div class="quantity-control">
                                    <button type="button" class="quantity-btn minus" onclick="updateQuantity(this, -1)">-</button>
                                    <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" 
                                           class="quantity-input" data-cart-id="<?php echo $item['cart_id']; ?>"
                                           onchange="updateCartQuantity(this)">
                                    <button type="button" class="quantity-btn plus" onclick="updateQuantity(this, 1)">+</button>
                                </div>
                            </div>
                            
                            <div class="cart-item-subtotal">
                                <div>Subtotal:</div>
                                <div class="subtotal-amount" id="subtotal-<?php echo $item['cart_id']; ?>">
                                    RP. <?php echo number_format($item['subtotal'], 0, ',', '.'); ?>
                                </div>
                            </div>
                            
                            <div class="cart-item-actions">
                                <a href="#" onclick="confirmRemove(<?php echo $item['cart_id']; ?>, '<?php echo htmlspecialchars($item['product_name']); ?>')" class="remove-btn" title="Hapus dari Keranjang">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="cart-summary">
                    <div class="total-price">
                        <h3>Total Harga: <span id="selected-total">RP. 0</span></h3>
                    </div>
                    <button type="button" id="checkoutSelectedBtn" class="primary-button">Checkout</button>
                </div>
            </form>
        <?php endif; ?>
        <div id="notification" class="notification" style="display: none;"></div>
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
    // Function to update quantity with + and - buttons
    function updateQuantity(button, change) {
        const input = button.parentElement.querySelector('.quantity-input');
        let newValue = parseInt(input.value) + change;
        
        // Ensure quantity doesn't go below 1
        if (newValue < 1) newValue = 1;
        
        input.value = newValue;
        updateCartQuantity(input);
    }
    
    // Function to update cart quantity via AJAX
function updateCartQuantity(input) {
    const cartId = input.dataset.cartId;
    const newQuantity = input.value;
    
    // Show loading indicator on the input
    input.disabled = true;
    
    // Send AJAX request to update quantity
    fetch('keranjang.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `update_quantity=1&cart_id=${cartId}&quantity=${newQuantity}&ajax=1`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update subtotal display
            const subtotalElement = document.getElementById(`subtotal-${cartId}`);
            if (subtotalElement) {
                subtotalElement.textContent = data.formatted_subtotal;
            }
            
            // Update the checkbox data attributes
            const checkbox = input.closest('.cart-item').querySelector('.item-checkbox');
            if (checkbox) {
                checkbox.dataset.quantity = newQuantity;
            }
            
            // Recalculate selected total
            calculateSelectedTotal();
        }
    })
    .finally(() => {
        input.disabled = false;
    });
}
    
    // Function to calculate total of selected items
function calculateSelectedTotal() {
    const checkboxes = document.querySelectorAll('.item-checkbox:checked');
    let selectedTotal = 0;
    
    checkboxes.forEach(checkbox => {
        const price = parseFloat(checkbox.dataset.price);
        const quantity = parseInt(checkbox.dataset.quantity);
        selectedTotal += price * quantity;
    });
    
    document.getElementById('selected-total').textContent = 
        `RP. ${selectedTotal.toLocaleString('id-ID')}`;
}
    
    // Function to confirm product removal
    function confirmRemove(cartId, productName) {
        Swal.fire({
            title: 'Hapus Produk',
            text: `Apakah Anda yakin ingin menghapus ${productName} dari keranjang?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `keranjang.php?remove=${cartId}`;
            }
        });
    }
    
    // Function to handle checkout selected items
    document.getElementById('checkoutSelectedBtn').addEventListener('click', function() {
        const checkboxes = document.querySelectorAll('.item-checkbox:checked');
        
        if (checkboxes.length === 0) {
            Swal.fire({
                title: 'Tidak ada item dipilih',
                text: 'Silakan pilih setidaknya satu item untuk checkout',
                icon: 'warning',
                confirmButtonText: 'OK'
            });
            return;
        }
        
        const selectedItems = Array.from(checkboxes).map(checkbox => checkbox.value);
        
        Swal.fire({
            title: 'Pindahkan ke Checkout',
            text: `Apakah Anda ingin memindahkan ${selectedItems.length} item ke halaman checkout?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#4CAF50',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Pindahkan',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading indicator
                Swal.fire({
                    title: 'Memproses...',
                    html: 'Sedang memindahkan item ke checkout',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                        
                        // Send AJAX request
                        fetch('keranjang.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `move_selected_to_checkout=1&selected_items=${selectedItems.join(',')}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    title: 'Berhasil!',
                                    text: data.message,
                                    icon: 'success',
                                }).then(() => {
                                    window.location.href = 'checkout.php';
                                });
                            } else {
                                Swal.fire({
                                    title: 'Gagal!',
                                    text: data.message,
                                    icon: 'error'
                                });
                            }
                        })
                        .catch(error => {
                            Swal.fire({
                                title: 'Error!',
                                text: 'Terjadi kesalahan saat memproses permintaan',
                                icon: 'error'
                            });
                        });
                    }
                });
            }
        });
    });
    
    // Add event listeners for checkboxes to recalculate total
    document.querySelectorAll('.item-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', calculateSelectedTotal);
    });
    
    // Calculate initial selected total
    calculateSelectedTotal();
    </script>
</body>
</html>