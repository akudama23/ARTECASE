<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_id'])) {
    $user_id = $_SESSION['user_id'];
    $cart_id = $_POST['cart_id'];

    // Mulai transaksi
    $conn->begin_transaction();

    try {
        // 1. Dapatkan detail item dari keranjang
        $cart_sql = "SELECT product_id, quantity FROM cart WHERE id = ? AND user_id = ?";
        $cart_stmt = $conn->prepare($cart_sql);
        $cart_stmt->bind_param("ii", $cart_id, $user_id);
        $cart_stmt->execute();
        $cart_result = $cart_stmt->get_result();
        
        if ($cart_result->num_rows === 0) {
            throw new Exception("Item tidak ditemukan di keranjang");
        }
        
        $cart_item = $cart_result->fetch_assoc();
        $cart_stmt->close();

        // 2. Cek stok produk
        $stock_sql = "SELECT stok FROM products WHERE id = ?";
        $stock_stmt = $conn->prepare($stock_sql);
        $stock_stmt->bind_param("i", $cart_item['product_id']);
        $stock_stmt->execute();
        $stock_result = $stock_stmt->get_result();
        $stock = $stock_result->fetch_assoc();
        $stock_stmt->close();
        
        if ($stock['stok'] < $cart_item['quantity']) {
            throw new Exception("Stok produk tidak mencukupi");
        }

        // 3. Tambahkan ke tabel checkout (update jika sudah ada)
        $check_sql = "INSERT INTO checkout (user_id, product_id, quantity) 
                     VALUES (?, ?, ?)
                     ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("iii", $user_id, $cart_item['product_id'], $cart_item['quantity']);
        
        if (!$check_stmt->execute()) {
            throw new Exception("Gagal menambahkan ke checkout");
        }
        $check_stmt->close();

        // 4. Hapus dari keranjang
        $delete_sql = "DELETE FROM cart WHERE id = ? AND user_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("ii", $cart_id, $user_id);
        
        if (!$delete_stmt->execute()) {
            throw new Exception("Gagal menghapus dari keranjang");
        }
        $delete_stmt->close();

        // Commit transaksi
        $conn->commit();

        echo json_encode([
            'success' => true, 
            'message' => 'Item berhasil dipindahkan ke checkout'
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'success' => false, 
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Permintaan tidak valid'
    ]);
}
?>