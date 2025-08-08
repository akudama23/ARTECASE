<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akun Pengguna - Toko Aksesoris Ponsel</title>
    <link rel="stylesheet" href="style_index1.css">
    <link rel="stylesheet" href="style_akun.css">
    <link rel="stylesheet" href="style_my_orders.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Poppins:wght@400;500;600&family=Trirong:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Add any additional styles here */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .section-title {
            font-size: 24px;
            font-weight: 600;
        }
        
        .account-actions {
            display: flex;
            gap: 10px;
        }
        
        .account-button {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        
        .profile-button {
            background-color: #f0f0f0;
            color: #333;
        }
        
        .orders-button {
            background-color: #f0f0f0;
            color: #333;
        }
        
        .logout-button {
            background-color: #f44336;
            color: white;
        }
        
        .account-button:hover {
            opacity: 0.9;
        }
        
        .profile-button.active {
            background-color: #4CAF50;
            color: white;
        }
        
        .orders-button.active {
            background-color: #2196F3;
            color: white;
        }
        
        .hidden {
            display: none;
        }
    </style>
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
                    <li><a href="akun.php" class="nav-active">Akun</a></li>
                </ul>
            </nav>
            <div class="nav-utility">
                <a href="keranjang.php" class="cart">
                    <i class="fa-solid fa-cart-shopping"></i>
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

    <?php
    session_start();
    
    // Koneksi ke database
    require_once 'connection.php';
    
    // Handle logout
    if (isset($_GET['logout'])) {
        session_destroy();
        header("Location: index.php");
        exit();
    }
    
    // Check if user is logged in (you should implement proper authentication)
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
    
    $userId = $_SESSION['user_id'];
    
    // Check if we're viewing orders or profile
    $view = isset($_GET['view']) ? $_GET['view'] : 'profile';
    
    // Initialize variables
    $editMode = false;
    $profileData = [
        'id' => $userId,
        'username' => '',
        'email' => '',
        'phone' => '',
        'profile_picture' => 'default_profile.jpg',
        'recipient_name' => '',
        'phone_number' => '',
        'address_line1' => '',
        'city' => '',
        'province' => '',
        'postal_code' => ''
    ];
    
    // Fetch user data from database
    $userQuery = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($userQuery);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $userResult = $stmt->get_result();
    
    if ($userResult->num_rows > 0) {
        $userData = $userResult->fetch_assoc();
        $profileData['username'] = $userData['username'];
        $profileData['email'] = $userData['email'];
        $profileData['phone'] = $userData['phone'];
        $profileData['profile_picture'] = $userData['profile_picture'] ?? 'default_profile.jpg';
    }
    
    // Fetch address data from database
    $addressQuery = "SELECT * FROM user_addresses WHERE user_id = ? AND is_primary = TRUE LIMIT 1";
    $stmt = $conn->prepare($addressQuery);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $addressResult = $stmt->get_result();
    
    if ($addressResult->num_rows > 0) {
        $addressData = $addressResult->fetch_assoc();
        $profileData['recipient_name'] = $addressData['recipient_name'];
        $profileData['phone_number'] = $addressData['phone_number'];
        $profileData['address_line1'] = $addressData['address_line1'];
        $profileData['city'] = $addressData['city'];
        $profileData['province'] = $addressData['province'];
        $profileData['postal_code'] = $addressData['postal_code'];
    }
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['edit'])) {
            $editMode = true;
        } elseif (isset($_POST['save'])) {
            $editMode = false;
            
            // Get form data
            $username = $_POST['username'];
            $email = $_POST['email'];
            $phone = $_POST['phone'];
            $recipient_name = $_POST['recipient_name'];
            $phone_number = $_POST['phone_number'];
            $address_line1 = $_POST['address_line1'];
            $city = $_POST['city'];
            $province = $_POST['province'];
            $postal_code = $_POST['postal_code'];
            
            // Handle profile picture upload
           if (!empty($_FILES['profile_image']['name'])) {
                $targetDir = "uploads/profile/";
                if (!file_exists($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }
                
                // Generate unique filename to prevent overwriting
                $fileExtension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
                $fileName = uniqid() . '.' . $fileExtension;
                $targetFilePath = $targetDir . $fileName;
                
                $allowTypes = array('jpg', 'png', 'jpeg', 'gif');
                if (in_array(strtolower($fileExtension), $allowTypes)) {
                    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetFilePath)) {
                        // Delete old profile picture if it's not the default
                        if ($profileData['profile_picture'] !== 'default_profile.jpg' && 
                            file_exists($profileData['profile_picture'])) {
                            unlink($profileData['profile_picture']);
                        }
                        $profileData['profile_picture'] = $targetFilePath;
                    }
                }
            }
            
            // Update user data
            $updateUserQuery = "UPDATE users SET 
                              username = ?,
                              email = ?,
                              phone = ?,
                              profile_picture = ?
                              WHERE id = ?";
            $stmt = $conn->prepare($updateUserQuery);
            $stmt->bind_param("ssssi", $username, $email, $phone, $profileData['profile_picture'], $userId);
            $stmt->execute();
            
            
            // Update or insert address data
            if ($addressResult->num_rows > 0) {
                $updateAddressQuery = "UPDATE user_addresses SET
                                     recipient_name = ?,
                                     phone_number = ?,
                                     address_line1 = ?,
                                     city = ?,
                                     province = ?,
                                     postal_code = ?
                                     WHERE user_id = ? AND is_primary = TRUE";
                $stmt = $conn->prepare($updateAddressQuery);
                $stmt->bind_param("ssssssi", $recipient_name, $phone_number, $address_line1, $city, $province, $postal_code, $userId);
            } else {
                $insertAddressQuery = "INSERT INTO user_addresses 
                                     (user_id, recipient_name, phone_number, address_line1, city, province, postal_code, is_primary)
                                     VALUES (?, ?, ?, ?, ?, ?, ?, TRUE)";
                $stmt = $conn->prepare($insertAddressQuery);
                $stmt->bind_param("issssss", $userId, $recipient_name, $phone_number, $address_line1, $city, $province, $postal_code);
            }
            $stmt->execute();
            
            // Refresh data
            header("Location: akun.php?view=profile");
            exit();
        }
    }
    
    // Fetch orders for the orders view
    if ($view === 'orders') {
        $query = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $orders = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
    ?>

    <section class="section container">
        <div class="section-header">
            <h1 class="section-title">Akun Saya</h1>
            <div class="account-actions">
                <a href="akun.php?view=profile" class="account-button profile-button <?php echo $view === 'profile' ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i> Profil
                </a>
                <a href="akun.php?view=orders" class="account-button orders-button <?php echo $view === 'orders' ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-bag"></i> Pesanan Saya
                </a>
                <a href="?logout" class="account-button logout-button">
                    <i class="fas fa-sign-out-alt"></i> Keluar
                </a>
            </div>
        </div>
        
        <!-- Profile View -->
        <div id="profile-view" class="<?php echo $view === 'profile' ? '' : 'hidden'; ?>">
            <div class="profile-container">
                <!-- Left panel - Profile Picture -->
                <div class="profile-left">
                    <p class="profile-id">ID: <?php echo $profileData['id']; ?></p>
                    <img alt="Profile Picture" class="profile-image" src="<?php echo $profileData['profile_picture']; ?>"/>
                    
                    <h1 class="profile-name">
                        <?php if ($editMode): ?>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($profileData['username'] ?? ''); ?>" class="form-input">
                        <?php else: ?>
                            <?php echo htmlspecialchars($profileData['recipient_name'] ?? ''); ?>
                        <?php endif; ?>
                    </h1>

                    <p class="profile-location">
                        <?php echo htmlspecialchars($profileData['city'] ?? ''); ?>
                    </p>
                </div>
                
                <!-- Right panel - Profile Details -->
                <div class="profile-right">
                    <h2 class="profile-title">User Profile</h2>
                    <form method="post" enctype="multipart/form-data">
                        <div class="profile-details">
                            <?php if ($editMode): ?>
                                <!-- Profile picture upload (moved inside the main form) -->
                                <div class="detail-item">
                                    <p class="detail-label">Foto Profil:</p>
                                    <input type="file" id="profile_image" name="profile_image" accept="image/*">
                                </div>
                                
                                <div class="detail-item">
                                    <p class="detail-label">Nama Lengkap</p>
                                    <input type="text" name="recipient_name" value="<?php echo htmlspecialchars($profileData['recipient_name']); ?>" class="form-input">
                                </div>
                                <div class="detail-item">
                                    <p class="detail-label">Email</p>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($profileData['email']); ?>" class="form-input">
                                </div>
                                <div class="detail-item">
                                    <p class="detail-label">Nomor Handphone</p>
                                    <input type="tel" name="phone_number" value="<?php echo htmlspecialchars($profileData['phone_number']); ?>" class="form-input">
                                </div>
                                <div class="detail-item">
                                    <p class="detail-label">Alamat</p>
                                    <input type="text" name="address_line1" value="<?php echo htmlspecialchars($profileData['address_line1']); ?>" class="form-input">
                                </div>
                                <div class="detail-item">
                                    <p class="detail-label">Kota</p>
                                    <input type="text" name="city" value="<?php echo htmlspecialchars($profileData['city']); ?>" class="form-input">
                                </div>
                                <div class="detail-item">
                                    <p class="detail-label">Provinsi</p>
                                    <input type="text" name="province" value="<?php echo htmlspecialchars($profileData['province']); ?>" class="form-input">
                                </div>
                                <div class="detail-item">
                                    <p class="detail-label">Kode Pos</p>
                                    <input type="text" name="postal_code" value="<?php echo htmlspecialchars($profileData['postal_code']); ?>" class="form-input">
                                </div>
                            <?php else: ?>
                                <div class="detail-item">
                                    <p class="detail-label">Nomor Handphone</p>
                                    <p class="detail-value"><?php echo htmlspecialchars(($profileData['phone_number'] ?: $profileData['phone']) ?? ''); ?></p>
                                </div>
                                <div class="detail-item">
                                    <p class="detail-label">Email</p>
                                    <p class="detail-value"><?php echo htmlspecialchars($profileData['email'] ?? ''); ?></p>
                                </div>
                                <div class="detail-item">
                                    <p class="detail-label">Alamat</p>
                                    <p class="detail-value"><?php echo htmlspecialchars($profileData['address_line1'] ?? ''); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="action-buttons">
                            <?php if ($editMode): ?>
                                <button type="submit" name="save" class="edit-button">Simpan Perubahan</button>
                                <a href="akun.php?view=profile" class="cancel-button">Batal</a>
                            <?php else: ?>
                                <button type="submit" name="edit" class="edit-button">Edit Profile</button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Orders View -->
        <div id="orders-view" class="<?php echo $view === 'orders' ? '' : 'hidden'; ?>">
            <?php if (!empty($orders)): ?>
                <div class="orders-container">
                    <?php foreach ($orders as $order): ?>
                        <?php
                        // Fetch order items for this order
                        $items_sql = "SELECT oi.*, p.product_name, p.product_image, p.price
                                     FROM order_items oi
                                     JOIN products p ON oi.product_id = p.id
                                     WHERE oi.order_id = ?";
                        $items_stmt = $conn->prepare($items_sql);
                        $items_stmt->bind_param("i", $order['id']);
                        $items_stmt->execute();
                        $items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                        $items_stmt->close();
                        ?>
                        
                        <div class="order-card">
                            <div class="order-header">
                                <div class="order-info">
                                    <span class="order-id">Order #<?php echo htmlspecialchars($order['order_number']); ?></span>
                                    <span class="order-date"><?php echo date('d F Y', strtotime($order['created_at'])); ?></span>
                                </div>
                                <div class="order-status <?php echo strtolower(htmlspecialchars($order['status'])); ?>">
                                    <?php echo htmlspecialchars($order['status']); ?>
                                </div>
                            </div>
                            
                            <div class="order-items">
                                <?php foreach ($items as $item): ?>
                                    <div class="order-item">
                                        <img src="IMAGE CASE/<?php echo htmlspecialchars($item['product_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                             class="item-image"
                                             onerror="this.src='IMAGE CASE/no-image.jpg'">
                                        <div class="item-details">
                                            <h3 class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></h3>
                                            <p class="item-price">Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></p>
                                            <p class="item-quantity">Quantity: <?php echo $item['quantity']; ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="order-footer">
                                <div class="order-total">
                                    <span>Total:</span>
                                    <span class="total-amount">Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></span>
                                </div>
                                <div class="order-actions">
                                    <a href="order_receipt.php?order_id=<?php echo $order['id']; ?>" class="track-button">View Receipt</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-orders">
                    <i class="fas fa-box-open"></i>
                    <p>Anda belum melakukan pemesanan apapun.</p>
                    <a href="homepage.php" class="shop-button">Mulai Belanja</a>
                </div>
            <?php endif; ?>
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