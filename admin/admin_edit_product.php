<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$id = $_GET['id'];
$product = $conn->query("SELECT * FROM products WHERE id = $id")->fetch_assoc();

if (!$product) {
    header("Location: admin_products.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_name = $_POST['product_name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $category = $_POST['category'];
    $stok = $_POST['stok'];
    
    // Handle file upload if new image is provided
    if (!empty($_FILES["product_image"]["name"])) {
        $target_dir = "IMAGE CASE/";
        $target_file = $target_dir . basename($_FILES["product_image"]["name"]);
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        $check = getimagesize($_FILES["product_image"]["tmp_name"]);
        if ($check !== false) {
            $uploadOk = 1;
        } else {
            $_SESSION['error'] = "File bukan gambar.";
            $uploadOk = 0;
        }
        
        if ($_FILES["product_image"]["size"] > 5000000) {
            $_SESSION['error'] = "Maaf, file terlalu besar.";
            $uploadOk = 0;
        }
        
        if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
            $_SESSION['error'] = "Maaf, hanya file JPG, JPEG, PNG & GIF yang diperbolehkan.";
            $uploadOk = 0;
        }
        
        if ($uploadOk == 1) {
            if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $target_file)) {
                $image_name = basename($_FILES["product_image"]["name"]);
            } else {
                $_SESSION['error'] = "Maaf, terjadi kesalahan saat mengupload file.";
            }
        }
    } else {
        $image_name = $product['product_image'];
    }
    
    if (!isset($_SESSION['error'])) {
        $stmt = $conn->prepare("UPDATE products SET product_name = ?, description = ?, price = ?, product_image = ?, category = ?, stok = ? WHERE id = ?");
        $stmt->bind_param("ssdssii", $product_name, $description, $price, $image_name, $category, $stok, $id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Produk berhasil diperbarui";
            header("Location: admin_products.php");
            exit();
        } else {
            $_SESSION['error'] = "Gagal memperbarui produk: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Produk - ArteCase</title>
    <link rel="stylesheet" href="style_admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="admin-sidebar">
            <div class="sidebar-header">
                <img src="../IMAGE CASE/LOGO ARTECASE.jpg" alt="ArteCase Logo">
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
                <h2>Edit Produk</h2>
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
                        <input type="text" id="product_name" name="product_name" value="<?php echo htmlspecialchars($product['product_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Deskripsi</label>
                        <textarea id="description" name="description" rows="4" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="price">Harga</label>
                            <input type="number" id="price" name="price" min="0" step="1000" value="<?php echo $product['price']; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="stok">Stok</label>
                            <input type="number" id="stok" name="stok" min="0" value="<?php echo $product['stok']; ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="category">Kategori</label>
                        <select id="category" name="category" required>
                            <option value="Softcase" <?php echo $product['category'] == 'Softcase' ? 'selected' : ''; ?>>Softcase</option>
                            <option value="Headset" <?php echo $product['category'] == 'Headset' ? 'selected' : ''; ?>>Headset</option>
                            <option value="Smartwatch" <?php echo $product['category'] == 'Smartwatch' ? 'selected' : ''; ?>>Smartwatch</option>
                            <option value="Earbuds" <?php echo $product['category'] == 'Earbuds' ? 'selected' : ''; ?>>Earbuds</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="product_image">Gambar Produk</label>
                        <input type="file" id="product_image" name="product_image" accept="image/*">
                        <div class="current-image">
                            <p>Gambar saat ini:</p>
                            <img src="../IMAGE CASE/<?php echo $product['product_image']; ?>" alt="Current Product Image" style="max-width: 150px; margin-top: 10px;">
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="save-btn">Simpan Perubahan</button>
                        <a href="admin_products.php" class="cancel-btn">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>