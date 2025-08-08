<?php
session_start();
include 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Store form data for repopulation
    $_SESSION['form_data'] = [
        'username' => htmlspecialchars($username),
        'email' => htmlspecialchars($email)
    ];

    // Validate all fields
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $_SESSION['error'] = "Semua field harus diisi.";
        header("Location: daftar.php");
        exit();
    }

    // Validate username
    if (strlen($username) < 3 || strlen($username) > 50) {
        $_SESSION['error'] = "Username harus antara 3-50 karakter.";
        header("Location: daftar.php");
        exit();
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Format email tidak valid.";
        header("Location: daftar.php");
        exit();
    }

    // Validate password match
    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Konfirmasi password tidak sesuai.";
        header("Location: daftar.php");
        exit();
    }

    // Validate password strength
    if (strlen($password) < 8) {
        $_SESSION['error'] = "Password minimal 8 karakter.";
        header("Location: daftar.php");
        exit();
    }

    // Check if username already exists
    $check_username = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check_username->bind_param("s", $username);
    $check_username->execute();
    $check_username->store_result();

    if ($check_username->num_rows > 0) {
        $_SESSION['error'] = "Username sudah digunakan.";
        header("Location: daftar.php");
        exit();
    }

    // Check if email already exists
    $check_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check_email->bind_param("s", $email);
    $check_email->execute();
    $check_email->store_result();

    if ($check_email->num_rows > 0) {
        $_SESSION['error'] = "Email sudah terdaftar.";
        header("Location: daftar.php");
        exit();
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $hashed_password);

    if ($stmt->execute()) {
        // Get the new user ID
        $user_id = $stmt->insert_id;
        
        // Set session variables
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;
        
        // Clear form data
        unset($_SESSION['form_data']);
        
        $_SESSION['success'] = "Pendaftaran berhasil! Anda sekarang login sebagai $username.";
        header("Location: homepage.php");
        exit();
    } else {
        $_SESSION['error'] = "Terjadi kesalahan saat mendaftar. Silakan coba lagi.";
        header("Location: daftar.php");
        exit();
    }
} else {
    header("Location: daftar.php");
    exit();
}
?>