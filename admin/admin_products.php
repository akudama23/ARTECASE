<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Handle product deletion
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM products WHERE id = $id");
    $_SESSION['message'] = "Produk berhasil dihapus";
    header("Location: admin_products.php");
    exit();
}

// Get all products
$products = $conn->query("SELECT * FROM products ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Produk - ArteCase</title>
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
                <h2>Kelola Produk</h2>
                <div class="admin-user">
                    <span>Admin</span>
                    <i class="fas fa-user-circle"></i>
                </div>
            </header>
            
            <div class="admin-content">
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
                <?php endif; ?>
                
                <div class="action-bar">
                    <a href="admin_add_product.php" class="add-btn"><i class="fas fa-plus"></i> Tambah Produk</a>
                </div>
                
                <table class="products-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Gambar</th>
                            <th>Nama Produk</th>
                            <th>Harga</th>
                            <th>Stok</th>
                            <th>Kategori</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($product = $products->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $product['id']; ?></td>
                            <td>
                                <img src="../IMAGE CASE/<?php echo $product['product_image']; ?>" alt="<?php echo $product['product_name']; ?>" class="product-thumbnail" onerror="this.src='IMAGE CASE/no-image.jpg'">
                            </td>
                            <td><?php echo $product['product_name']; ?></td>
                            <td>Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></td>
                            <td><?php echo $product['stok']; ?></td>
                            <td><?php echo $product['category']; ?></td>
                            <td>
                                <a href="admin_edit_product.php?id=<?php echo $product['id']; ?>" class="edit-btn"><i class="fas fa-edit"></i></a>
                                <a href="admin_products.php?delete=<?php echo $product['id']; ?>" class="delete-btn" onclick="return confirm('Yakin ingin menghapus produk ini?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>