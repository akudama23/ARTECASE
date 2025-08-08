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

// Cek apakah produk sudah ada di wishlist
$check_sql = "SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ii", $user_id, $product_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Product already in wishlist']);
    exit();
}

// Tambahkan ke wishlist
$insert_sql = "INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)";
$insert_stmt = $conn->prepare($insert_sql);
$insert_stmt->bind_param("ii", $user_id, $product_id);
$result = $insert_stmt->execute();
$insert_stmt->close();

echo json_encode(['success' => $result]);

$check_stmt->close();
$conn->close();
?>