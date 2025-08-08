<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Get today's sales
$today = date('Y-m-d');
$sales_today = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE DATE(created_at) = '$today'")->fetch_assoc()['total'];
$sales_today = $sales_today ? $sales_today : 0;

// Get total products
$total_products = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];

// Get total orders
$total_orders = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];

// Get low stock products
$low_stock = $conn->query("SELECT COUNT(*) as count FROM products WHERE stok < 5")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ArteCase</title>
    <link rel="stylesheet" href="style_admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Add receipt styles -->
    <link rel="stylesheet" href="style_receipt.css">
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
                    <li class="active"><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="admin_products.php"><i class="fas fa-box-open"></i> Produk</a></li>
                    <li><a href="admin_orders.php"><i class="fas fa-shopping-cart"></i> Order</a></li>
                    <li><a href="admin_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="admin-main">
            <header class="admin-header">
                <h2>Dashboard</h2>
                <div class="admin-user">
                    <span>Admin</span>
                    <i class="fas fa-user-circle"></i>
                </div>
            </header>
            
            <div class="admin-content">
                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: #4CAF50;">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Penjualan Hari Ini</h3>
                            <p>Rp <?php echo number_format($sales_today, 0, ',', '.'); ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: #2196F3;">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Total Produk</h3>
                            <p><?php echo $total_products; ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: #FF9800;">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Total Order</h3>
                            <p><?php echo $total_orders; ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: #F44336;">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Stok Rendah</h3>
                            <p><?php echo $low_stock; ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Orders -->
                <div class="recent-orders">
                    <h3>Order Terbaru</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>ID Order</th>
                                <th>Tanggal</th>
                                <th>Customer</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $orders = $conn->query("SELECT o.id, o.order_number, o.total_amount, o.status, o.created_at, u.username 
                                                   FROM orders o JOIN users u ON o.user_id = u.id 
                                                   ORDER BY o.created_at DESC LIMIT 5");
                            while ($order = $orders->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?php echo $order['order_number']; ?></td>
                                <td><?php echo date('d M Y', strtotime($order['created_at'])); ?></td>
                                <td><?php echo $order['username']; ?></td>
                                <td>Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></td>
                                <td><span class="status-badge <?php echo strtolower($order['status']); ?>"><?php echo $order['status']; ?></span></td>
                                <td>
                                    <a href="admin_receipt.php?id=<?php echo $order['id']; ?>" class="view-btn">Detail</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>