<?php
session_start();
include 'connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Anda harus login terlebih dahulu']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $_SESSION['user_id'];
$product_id = $data['product_id'];
$quantity = isset($data['quantity']) ? (int)$data['quantity'] : 1;

// Validasi quantity
if ($quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Quantity must be at least 1']);
    exit();
}

// Cek apakah produk ada di database
$product_check = $conn->prepare("SELECT id FROM products WHERE id = ?");
$product_check->bind_param("i", $product_id);
$product_check->execute();
$product_check->store_result();

if ($product_check->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit();
}
$product_check->close();

// Cek apakah produk sudah ada di keranjang
$check_sql = "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ii", $user_id, $product_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    // Update quantity jika produk sudah ada
    $row = $check_result->fetch_assoc();
    $new_quantity = $row['quantity'] + $quantity;
    
    $update_sql = "UPDATE cart SET quantity = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ii", $new_quantity, $row['id']);
    $result = $update_stmt->execute();
    $update_stmt->close();
} else {
    // Tambahkan produk baru ke keranjang
    $insert_sql = "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("iii", $user_id, $product_id, $quantity);
    $result = $insert_stmt->execute();
    $insert_stmt->close();
}

// Hitung total item di keranjang
$count_sql = "SELECT COUNT(*) as count FROM cart WHERE user_id = ?";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param("i", $user_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$count_row = $count_result->fetch_assoc();

echo json_encode([
    'success' => $result,
    'cart_count' => $count_row['count']
]);

$check_stmt->close();
$count_stmt->close();
$conn->close();
?>