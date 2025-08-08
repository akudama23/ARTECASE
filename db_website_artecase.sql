-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.0.30 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.1.0.6537
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Dumping database structure for db_website_artecase
CREATE DATABASE IF NOT EXISTS `db_website_artecase` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `db_website_artecase`;

-- 1. Buat tabel users terlebih dahulu karena banyak tabel lain bergantung padanya
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `confirm_password` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `kyc_verified` tinyint(1) DEFAULT '0',
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 2. Buat tabel admin (tidak bergantung pada tabel lain)
CREATE TABLE IF NOT EXISTS `admin` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `password` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 3. Buat tabel products sebelum tabel yang membutuhkannya (cart, wishlist, orders, dll)
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_name` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `price` DECIMAL(10,2) NOT NULL,
  `product_image` VARCHAR(255),
  `category` VARCHAR(50),
  `stok` INT DEFAULT 10,
  `sold` INT DEFAULT 0
);

-- 4. Buat tabel user_addresses (bergantung pada users)
CREATE TABLE IF NOT EXISTS `user_addresses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `recipient_name` varchar(100) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `address_line1` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `address_line2` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `province` varchar(100) NOT NULL,
  `postal_code` varchar(10) NOT NULL,
  `is_primary` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `FK_user_addresses_users` (`user_id`),
  CONSTRAINT `FK_user_addresses_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 5. Buat tabel orders (bergantung pada users dan user_addresses)
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `order_number` varchar(20) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','completed','cancelled') DEFAULT 'pending',
  `payment_method` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `payment_proof` varchar(225) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `address_id` int DEFAULT NULL,
  `products` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `payment_deadline` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `FK_orders_user` (`user_id`),
  KEY `FK_orders_user_addresses` (`address_id`),
  CONSTRAINT `FK_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_orders_user_addresses` FOREIGN KEY (`address_id`) REFERENCES `user_addresses` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 6. Buat tabel order_items (bergantung pada orders dan products)
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 7. Buat tabel wishlist (bergantung pada users dan products)
CREATE TABLE IF NOT EXISTS `wishlist` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `product_id` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `FK_wishlist_products` (`product_id`),
  KEY `FK_wishlist_users` (`user_id`) USING BTREE,
  CONSTRAINT `FK_wishlist_products` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `FK_wishlist_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 8. Buat tabel cart (bergantung pada users dan products)
CREATE TABLE IF NOT EXISTS `cart` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL DEFAULT '0',
  `product_id` int NOT NULL DEFAULT '0',
  `create_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `quantity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `unique_wishlist` (`user_id`,`product_id`) USING BTREE,
  KEY `product_id` (`product_id`),
  CONSTRAINT `FK_cart_products` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=191 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 9. Buat tabel checkout (bergantung pada users dan products)
CREATE TABLE IF NOT EXISTS `checkout` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `FK_checkout_cart` (`user_id`,`product_id`),
  KEY `FK_checkout_products` (`product_id`),
  CONSTRAINT `FK_checkout_products` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_checkout_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=104 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 10. Buat tabel notifications (bergantung pada users)
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


-- Data produk yang sudah digabung dan dirapikan
INSERT INTO products (product_name, description, price, product_image, category, stok, sold) VALUES
('Softcase Glitter Quicksand peach blossom', 'Casing Softcase Samsung S24 Ultra dengan glitter quicksand warm peach blossom', 45000, 'image7.jpg', 'Softcase', 10, 10),
('Softcase snow white butterfly', 'Casing Softcase Samsung S24 Ultra putih dengan motif kupu-kupu yang cantik', 48000, 'image5.jpg', 'Softcase', 10, 2),
('Softcase Chines Dragon yin yang', 'Casing Softcase Samsung S24 Ultra dengan motif naga Cina dan simbol yin yang', 55000, 'image8.jpg', 'Softcase', 10, 5),
('Softcase white nature stone', 'Casing Softcase Samsung S24 Ultra putih dengan motif batu alam yang natural', 33000, 'image11.jpg', 'Softcase', 10, 1),
('Softcase blue and white flowers', 'Casing Softcase Samsung S24 Ultra biru dengan motif bunga-bunga putih yang indah', 58000, 'image22.jpg', 'Softcase', 12, 6),
('Softcase mini Astronaut', 'Casing Softcase Samsung S24 Ultra dengan desain astronaut mini yang unik dan futuristic', 46500, 'image6.jpg', 'Softcase', 10, 8),
('REXUS THUNDERVOX HX20 RGB Gaming Headset', 'Headset gaming dengan lighting RGB dan sound surround berkualitas', 380000, 'image14.jpg', 'Headset', 10, 2),
('dbE GM190 Gaming Headset', 'Headset gaming ekonomis dengan kualitas audio yang baik', 225000, 'image9.jpg', 'Headset', 10, 1),
('Xiaomi Redmi watch 2 lite', 'Smartwatch Xiaomi dengan fitur lengkap dan baterai tahan lama', 390000, 'image2.jpg', 'Smartwatch', 10, 14),
('Redmi Watch 5 Lite Stylish Smartwatch Black', 'Smartwatch stylish dengan tampilan premium dan fitur kesehatan', 490000, 'image17.jpg', 'Smartwatch', 10, 5),
('Xiaomi Redmi Buds 6 Lite', 'Earbuds nirkabel dengan koneksi stabil dan suara jernih', 350000, 'image1.jpg', 'Earbuds', 12, 3),
('Softcase cat couple', 'Casing Softcase S24 Ultra lucu dengan gambar pasangan kucing yang menggemaskan', 50000, 'image12.jpg', 'Softcase', 13, 15),
('Xiaomi Buds 5 Pro', 'Wireless earbuds with AI Active Noise Cancelling dan HIFI sound quality', 450000, 'image27.jpg', 'Earbuds', 11, 0),
('Redmi Airdots 3', 'True wireless earbuds dengan AptX Adaptive Bluetooth technology', 300000, 'image24.jpg', 'Earbuds', 12, 0),
('Redmi Buds 3 Lite', 'Bluetooth 5.2 earphones dengan IP54 rating dan 18 jam battery life', 250000, 'image32.jpg', 'Earbuds', 10, 0),
('FASTECH WHG04 Wireless Gaming Headset', 'Tri-mode gaming headphones dengan 40mm drivers, 22hrs playback, dan lightweight design', 350000, 'image33.jpg', 'Headset', 11, 0),
('Logitech G435 LIGHTSPEED Wireless', 'Lightweight wireless gaming headset dengan premium sound quality', 600000, 'image34.jpg', 'Headset', 11, 0),
('Logitech G432 Gaming Headset', 'Wired gaming headset dengan immersive sound untuk detailed gameplay', 400000, 'image25.jpg', 'Headset', 11, 0),
('Samsung Galaxy Watch 4', 'Smartwatch dasar dengan konektivitas Bluetooth dan fitur esensial', 235000, 'image28.jpg', 'Smartwatch', 4, 0),
('XIAOMI Watch 10', 'XIAOMI Watch 10 Original Smartwatch 2.2" Full Touch Screen dengan Wireless Charge', 223000, 'image47.jpg', 'Smartwatch', 10, 0),
('Redmi Watch 5 Active', 'Smartwatch yang dirancang untuk pengguna aktif dan peduli kesehatan', 500000, 'image35.jpg', 'Smartwatch', 10, 0),
('MoEcase Eksklusif Untuk Samsung S24 Ultra Stitch', 'Moscase Eksklusif Untuk Samsung S24 Ultra PhD Softcase + Kaca Case Stitch', 50000, 'image36.jpg', 'Softcase', 10, 0),
('Magnetic Magsafe Cover for Galaxy S Series', 'Magnetic cover resmi dengan perlindungan lensa kamera untuk Galaxy S series', 50000, 'image25.jpg', 'Softcase', 15, 8),
('Airbag Case For Samsung Galaxy S24 Ultra', 'Casing untuk Samsung S Series dengan double protection premium', 60000, 'image43.jpg', 'Softcase', 12, 6),
('Premium Glossy Softcase for Samsung Series', 'Softcase glossy berkualitas tinggi dengan perlindungan penuh', 80000, 'image39.jpg', 'Softcase', 20, 10),
('Leather Magsafe Case for Samsung Galaxy', 'Two Tone Leather Magnetic Case untuk Samsung Galaxy S24 Ultra', 90000, 'image46.jpg', 'Softcase', 10, 3),
('Case Samsung Galaxy S24 Ultra', 'Magnetic Stand Case untuk Samsung Galaxy S Series', 50000, 'image44.jpg', 'Softcase', 11, 4),
('Leather Case for Samsung', 'Syndicate TPU Leather Case untuk Samsung S24', 60000, 'image45.jpg', 'Softcase', 15, 7),
('Glossy Softcase for Samsung S24 Ultra FE [Gundam]', 'Softcase glossy premium dengan desain Gundam untuk Samsung S24 Ultra FE', 50000, 'image39.jpg', 'Softcase', 12, 5),
('Hybrid Case Samsung Galaxy S24 Ultra FE Double Grey', 'Hybrid case dengan desain double grey untuk Samsung S24 Ultra FE', 80000, 'image40.jpg', 'Softcase', 8, 3),
('Samsung Galaxy S24 Ultra Focus Carbon Softcase', 'Softcase original dengan desain carbon focus untuk Samsung S24 Ultra', 90000, 'image41.jpg', 'Softcase', 10, 6),
('Hybrid Case Samsung Galaxy S24 Ultra FE Art Texture', 'Hybrid case dengan tekstur artistic untuk Samsung S24 Ultra FE', 60000, 'image42.jpg', 'Softcase', 9, 2),
('Casing Softcase Butterfly', 'Silikon Casing Softcase Butterfly Mirror Phone Case', 30000, 'image48.jpg', 'Softcase', 12, 0),
('Softcase IMD Hologram', 'Softcase Silikon TPU PhD Hologram untuk Xiaomi Redmi Note 14 4G', 33000, 'image49.jpg', 'Softcase', 12, 0),
('Casing Soft TY 0513', 'Casing Silikon Softcase untuk Xiaomi Redmi Note 14', 29000, 'image50.jpg', 'Softcase', 10, 0);

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
