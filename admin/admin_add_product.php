<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_name = $_POST['product_name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $category = $_POST['category'];
    $stok = $_POST['stok'];
    
    // Handle file upload
    $target_dir = "../IMAGE CASE/";
     if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    $target_file = $target_dir . basename($_FILES["product_image"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    // Check if image file is a actual image or fake image
    $check = getimagesize($_FILES["product_image"]["tmp_name"]);
    if ($check !== false) {
        $uploadOk = 1;
    } else {
        $_SESSION['error'] = "File bukan gambar.";
        $uploadOk = 0;
    }
    
    // Check file size
    if ($_FILES["product_image"]["size"] > 5000000) {
        $_SESSION['error'] = "Maaf, file terlalu besar.";
        $uploadOk = 0;
    }
    
    // Allow certain file formats
    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
        $_SESSION['error'] = "Maaf, hanya file JPG, JPEG, PNG & GIF yang diperbolehkan.";
        $uploadOk = 0;
    }
    
    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 1) {
        if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $target_file)) {
            $image_name = basename($_FILES["product_image"]["name"]);
            
            $stmt = $conn->prepare("INSERT INTO products (product_name, description, price, product_image, category, stok) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdssi", $product_name, $description, $price, $image_name, $category, $stok);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = "Produk berhasil ditambahkan";
                header("Location: admin_products.php");
                exit();
            } else {
                $_SESSION['error'] = "Gagal menambahkan produk: " . $conn->error;
            }
        } else {
            $_SESSION['error'] = "Maaf, terjadi kesalahan saat mengupload file.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Produk - ArteCase</title>
    <link rel="stylesheet" href="style_admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="admin-sidebar">
            <div class="sidebar-header">
                <img src="/IMAGE CASE/LOGO ARTECASE.jpg" alt="ArteCase Logo">
                <h3>Admin Panel</h3>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li class="active"><a href="admin_products.php"><i class="fas fa-box-open"></i> Produk</a></li>
                    <li><a href="admin_orders.php"><i class="fas fa-shopping-cart"></i> Order</a></li>
                    <li><a href="admin_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="admin-main">
            <header class="admin-header">
                <h2>Tambah Produk</h2>
                <div class="admin-user">
                    <span>Admin</span>
                    <i class="fas fa-user-circle"></i>
                </div>
            </header>
            
            <div class="admin-content">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data" class="product-form">
                    <div class="form-group">
                        <label for="product_name">Nama Produk</label>
                        <input type="text" id="product_name" name="product_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Deskripsi</label>
                        <textarea id="description" name="description" rows="4" required></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="price">Harga</label>
                            <input type="number" id="price" name="price" min="0" step="1000" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="stok">Stok</label>
                            <input type="number" id="stok" name="stok" min="0" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="category">Kategori</label>
                        <select id="category" name="category" required>
                            <option value="">Pilih Kategori</option>
                            <option value="Softcase">Softcase</option>
                            <option value="Headset">Headset</option>
                            <option value="Smartwatch">Smartwatch</option>
                            <option value="Earbuds">Earbuds</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="product_image">Gambar Produk</label>
                        <input type="file" id="product_image" name="product_image" accept="image/*" required>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="save-btn">Simpan Produk</button>
                        <a href="admin_products.php" class="cancel-btn">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>