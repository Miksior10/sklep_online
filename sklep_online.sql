-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 08, 2025 at 06:50 PM
-- Wersja serwera: 10.4.32-MariaDB
-- Wersja PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sklep_online`
--

DELIMITER $$
--
-- Procedury
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `delete_user` (IN `user_id` INT)   BEGIN
        DECLARE EXIT HANDLER FOR SQLEXCEPTION
        BEGIN
            ROLLBACK;
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Wystąpił błąd podczas usuwania użytkownika';
        END;
        
        START TRANSACTION;
        
        -- Pobierz ID zamówień użytkownika
        SET @order_ids = NULL;
        SELECT GROUP_CONCAT(id) INTO @order_ids FROM orders WHERE user_id = user_id;
        
        -- Usuń powiązane wpisy, jeśli istnieją zamówienia
        IF @order_ids IS NOT NULL THEN
            -- Usuń wpisy z order_status_history
            SET @sql = CONCAT('DELETE FROM order_status_history WHERE order_id IN (', @order_ids, ')');
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
            
            -- Usuń wpisy z order_items
            SET @sql = CONCAT('DELETE FROM order_items WHERE order_id IN (', @order_ids, ')');
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
            
            -- Usuń wpisy z shipping_addresses
            SET @sql = CONCAT('DELETE FROM shipping_addresses WHERE order_id IN (', @order_ids, ')');
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
            
            -- Usuń wpisy z payments
            SET @sql = CONCAT('DELETE FROM payments WHERE order_id IN (', @order_ids, ')');
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
            
            -- Usuń wpisy z vouchers powiązane z zamówieniami
            SET @sql = CONCAT('DELETE FROM vouchers WHERE order_id IN (', @order_ids, ')');
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        END IF;
        
        -- Usuń zamówienia użytkownika
        DELETE FROM orders WHERE user_id = user_id;
        
        -- Usuń vouchery przypisane bezpośrednio do użytkownika
        DELETE FROM vouchers WHERE user_id = user_id;
        
        -- Na koniec usuń samego użytkownika
        DELETE FROM users WHERE id = user_id;
        
        COMMIT;
    END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `color` varchar(7) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `featured_products`
--

CREATE TABLE `featured_products` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  `added_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `shipping_method` varchar(50) NOT NULL,
  `shipping_cost` decimal(10,2) NOT NULL,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `shipping_address_id` int(11) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_method` varchar(20) DEFAULT 'card',
  `discount` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total_amount`, `shipping_method`, `shipping_cost`, `discount_amount`, `shipping_address_id`, `status`, `order_date`, `created_at`, `payment_method`, `discount`) VALUES
(33, 18, 5964.98, 'courier', 14.99, 50.00, NULL, 'new', '2025-04-08 14:39:56', '2025-04-08 14:39:56', 'card', 0.00),
(34, 18, 11964.97, 'courier', 14.99, 50.00, NULL, 'new', '2025-04-08 14:40:14', '2025-04-08 14:40:14', 'card', 0.00);

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(39, 33, 56, 1, 5999.99),
(40, 34, 56, 2, 5999.99);

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `order_status`
--

CREATE TABLE `order_status` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `status` varchar(20) NOT NULL,
  `status_date` datetime NOT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `order_status_history`
--

CREATE TABLE `order_status_history` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `admin_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `cardholder_name` varchar(255) NOT NULL,
  `card_number` varchar(255) NOT NULL,
  `card_expiry` varchar(255) NOT NULL,
  `card_cvv` varchar(255) NOT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `amount` decimal(10,2) NOT NULL,
  `status` varchar(20) DEFAULT 'completed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `order_id`, `payment_method`, `cardholder_name`, `card_number`, `card_expiry`, `card_cvv`, `payment_date`, `amount`, `status`) VALUES
(18, 33, 'card', 'Maks Nowak', 'LzB2QzhCZWNjelg5cldqMVB1SHZyUSsrQnc5MGdZcGZuQUNOZUd6UnRZZz06Okjj/YNhIIQzxqVV2gJZhEE=', '11/26', '', '2025-04-08 14:39:56', 5964.98, 'completed'),
(19, 34, 'card', 'Maks Nowak', 'UnoySEFVQ2kreUg0RVBqSVR6eDA1TEEwMjlTbFpkTFNoaUdPL21rbjFXVT06Og/6J/4sZmpCAeiHpz3tO+A=', '11/26', '', '2025-04-08 14:40:14', 11964.97, 'completed');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `stock` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `category` varchar(50) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `memory` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `image_url`, `stock`, `created_at`, `category`, `category_id`, `is_featured`, `memory`) VALUES
(56, 'iPhone 14 Pro', 'Najnowszy smartfon Apple z doskonałym aparatem', 5999.99, 'uploads/products/product_67f54eccaa3c1.png', 6, '2024-03-12 10:06:50', 'Smartphone', NULL, 0, 8),
(57, 'Samsung Galaxy S23', 'Flagowy smartfon Samsung z ekranem AMOLED', 4499.99, 'galaxy_s23.jpg', 15, '2024-03-12 10:06:50', 'Smartphone', NULL, 0, NULL),
(58, 'MacBook Pro M2', 'Laptop Apple z procesorem M2', 7999.99, 'macbook_m2.jpg', 8, '2024-03-12 10:06:50', 'Laptop', NULL, 0, NULL),
(59, 'PlayStation 5', 'Konsola do gier nowej generacji', 2499.99, 'uploads/products/product_67ed9d008a20c.png', 19, '2024-03-12 10:06:50', 'Gaming', NULL, 0, NULL),
(60, 'Xbox Series X', 'Najpotężniejsza konsola Microsoft', 2399.99, 'uploads/products/product_67ed9e40d341d.png', 24, '2024-03-12 10:06:50', 'Gaming', NULL, 0, NULL),
(61, 'Nintendo Switch OLED', 'Przenośna konsola z wyświetlaczem OLED', 1499.99, 'switch_oled.jpg', 30, '2024-03-12 10:06:50', 'Gaming', NULL, 0, NULL),
(62, 'AirPods Pro', 'Bezprzewodowe słuchawki z redukcją szumów', 999.99, 'airpods_pro.jpg', 40, '2024-03-12 10:06:50', 'Inne', NULL, 0, NULL),
(63, 'iPad Air', 'Lekki i wydajny tablet', 2999.99, 'ipad_air.jpg', 12, '2024-03-12 10:06:50', 'Tablet', NULL, 0, NULL),
(64, 'Apple Watch Series 8', 'Smartwatch z funkcjami zdrowotnymi', 1999.99, 'apple_watch.jpg', 14, '2024-03-12 10:06:50', 'Inne', NULL, 0, NULL),
(65, 'DJI Mini 3 Pro', 'Kompaktowy dron z kamerą 4K', 3499.99, 'dji_mini3.jpg', 4, '2024-03-12 10:06:50', 'Inne', NULL, 0, NULL),
(66, 'Smartfon Premium', 'Najnowszy model z doskonałym aparatem i wydajnym procesorem', 3999.99, 'premium_phone.jpg', 15, '2024-03-12 10:14:57', 'Smartphone', NULL, 0, NULL),
(67, 'Laptop Ultra', 'Lekki i wydajny laptop do pracy i rozrywki', 4599.99, 'ultra_laptop.jpg', 10, '2024-03-12 10:14:57', 'Laptop', NULL, 0, NULL),
(68, 'Słuchawki Pro', 'Bezprzewodowe słuchawki z aktywną redukcją szumów', 899.99, 'pro_headphones.jpg', 25, '2024-03-12 10:14:57', 'Audio', NULL, 0, NULL),
(69, 'Tablet Max', 'Tablet z wysokiej jakości wyświetlaczem', 2499.99, 'max_tablet.jpg', 20, '2024-03-12 10:14:57', 'Tablet', NULL, 0, NULL),
(70, 'Smartwatch Elite', 'Zaawansowany zegarek z monitorem zdrowia', 1299.99, 'elite_watch.jpg', 30, '2024-03-12 10:14:57', 'Smartphone', NULL, 0, NULL),
(71, 'Kamera Action', 'Wodoodporna kamera sportowa 4K', 999.99, 'action_camera.jpg', 18, '2024-03-12 10:14:57', 'Inne', NULL, 0, NULL),
(72, 'Głośnik Bluetooth', 'Przenośny głośnik z doskonałym dźwiękiem', 399.99, 'bt_speaker.jpg', 39, '2024-03-12 10:14:57', 'Audio', NULL, 0, NULL),
(73, 'Powerbank 20000mAh', 'Pojemny powerbank z szybkim ładowaniem', 199.99, 'powerbank.jpg', 50, '2024-03-12 10:14:57', 'Inne', NULL, 0, NULL),
(74, 'Mysz Gaming', 'Precyzyjna mysz dla graczy z RGB', 299.99, 'gaming_mouse.jpg', 35, '2024-03-12 10:14:57', 'Gaming', NULL, 0, NULL),
(75, 'Klawiatura Mechaniczna', 'Gamingowa klawiatura z przełącznikami mechanicznymi', 449.99, 'mech_keyboard.jpg', 22, '2024-03-12 10:14:57', 'Inne', NULL, 0, NULL),
(76, 'Monitor Gaming 27\"', 'Monitor 165Hz z czasem reakcji 1ms, HDR', 1899.99, 'gaming_monitor.jpg', 8, '2024-03-12 10:20:00', 'Gaming', NULL, 0, NULL),
(77, 'Kamera Internetowa 4K', 'Profesjonalna kamera do streamingu i wideokonferencji', 599.99, 'webcam_4k.jpg', 25, '2024-03-12 10:20:00', 'Inne', NULL, 0, NULL),
(78, 'Router Gaming', 'Router Wi-Fi 6 z optymalizacją dla graczy', 899.99, 'gaming_router.jpg', 15, '2024-03-12 10:20:00', 'Gaming', NULL, 0, NULL),
(79, 'Dysk SSD 2TB', 'Szybki dysk SSD NVMe PCIe 4.0', 999.99, 'ssd_drive.jpg', 30, '2024-03-12 10:20:00', 'Inne', NULL, 0, NULL),
(80, 'Karta Graficzna RTX 4070', 'Wydajna karta graficzna do gier', 3499.99, 'rtx_4070.jpg', 4, '2024-03-12 10:20:00', 'Inne', NULL, 0, NULL),
(81, 'Procesor Intel i9 13900K', 'Procesor Intel Core i9 13900K', 2799.99, 'intel_i9.jpg', 10, '2024-03-12 10:20:00', 'Gaming', NULL, 0, NULL),
(82, 'Pamięć RAM 32GB', 'Zestaw pamięci DDR5 6000MHz', 799.99, 'ram_ddr5.jpg', 20, '2024-03-12 10:20:00', 'Inne', NULL, 0, NULL),
(83, 'Mikrofon Pojemnościowy', 'Studyjny mikrofon USB z podstawką', 599.99, 'mic_usb.jpg', 15, '2024-03-12 10:20:00', 'Inne', NULL, 0, NULL),
(84, 'Stacja Dokująca', 'Uniwersalna stacja dokująca USB-C', 699.99, 'dock_station.jpg', 12, '2024-03-12 10:20:00', 'Inne', NULL, 0, NULL),
(85, 'UPS 1500VA', 'Zasilacz awaryjny z czystą sinusoidą', 999.99, 'ups_1500.jpg', 8, '2024-03-12 10:20:00', 'Inne', NULL, 0, NULL),
(86, 'Pad Xbox Elite', 'Kontroler premium do Xbox/PC', 799.99, 'xbox_elite.jpg', 10, '2024-03-12 10:20:00', 'Gaming', NULL, 0, NULL),
(87, 'Słuchawki TWS', 'Prawdziwie bezprzewodowe słuchawki z etui', 399.99, 'tws_earbuds.jpg', 30, '2024-03-12 10:20:00', 'Audio', NULL, 0, NULL),
(88, 'Kamera IP', 'Kamera monitoringu z noktowizją', 299.99, 'ip_camera.jpg', 20, '2024-03-12 10:20:00', 'Inne', NULL, 0, NULL),
(89, 'Hub USB-C', 'Hub 10-w-1 z HDMI i czytnikiem kart', 249.99, 'usb_hub.jpg', 25, '2024-03-12 10:20:00', 'Inne', NULL, 0, NULL),
(90, 'Ładowarka GaN 100W', 'Kompaktowa ładowarka z GaN', 199.99, 'gan_charger.jpg', 35, '2024-03-12 10:20:00', 'Inne', NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `product_colors`
--

CREATE TABLE `product_colors` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `color` varchar(7) NOT NULL,
  `color_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_colors`
--

INSERT INTO `product_colors` (`id`, `product_id`, `color`, `color_name`) VALUES
(30, 56, '#000000', 'Czarny'),
(31, 56, '#000000', 'Czarny');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `product_color_images`
--

CREATE TABLE `product_color_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `color` varchar(50) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_color_images`
--

INSERT INTO `product_color_images` (`id`, `product_id`, `color`, `image_url`, `created_at`) VALUES
(3, 56, '#000000', 'uploads/products/colors/color_67f550d9ec021.png', '2025-04-08 16:37:45'),
(4, 56, '#ababab', 'uploads/products/colors/color_67f550ff832fe.png', '2025-04-08 16:38:23');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `product_memories`
--

CREATE TABLE `product_memories` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `memory_size` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_memories`
--

INSERT INTO `product_memories` (`id`, `product_id`, `memory_size`, `created_at`) VALUES
(13, 56, 64, '2025-04-08 16:45:15'),
(14, 56, 128, '2025-04-08 16:45:15'),
(15, 56, 256, '2025-04-08 16:45:15'),
(16, 56, 1024, '2025-04-08 16:45:15');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `shop_name` varchar(100) DEFAULT 'Sklep Online',
  `shop_email` varchar(100) DEFAULT 'kontakt@example.com',
  `contact_phone` varchar(20) DEFAULT '',
  `contact_address` text DEFAULT '',
  `footer_text` text DEFAULT '',
  `maintenance_mode` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `created_at`, `updated_at`, `shop_name`, `shop_email`, `contact_phone`, `contact_address`, `footer_text`, `maintenance_mode`) VALUES
(1, 'vat_rate', '23', '2025-04-01 15:49:38', '2025-04-01 15:51:08', 'test', 'testy@gmail.com', '505120512', 'Polaczka 6b/1', 'testy', 0),
(2, 'shipping_cost_courier', '15.00', '2025-04-01 15:49:38', '2025-04-01 15:49:38', 'Sklep Online', 'kontakt@example.com', '', '', '', 0),
(3, 'shipping_cost_pickup', '0.00', '2025-04-01 15:49:38', '2025-04-01 15:49:38', 'Sklep Online', 'kontakt@example.com', '', '', '', 0),
(4, 'free_shipping_threshold', '200.00', '2025-04-01 15:49:38', '2025-04-01 15:49:38', 'Sklep Online', 'kontakt@example.com', '', '', '', 0),
(5, 'currency', 'PLN', '2025-04-01 15:49:38', '2025-04-01 15:49:38', 'Sklep Online', 'kontakt@example.com', '', '', '', 0),
(6, 'shop_name', 'Sklep Online', '2025-04-01 15:49:38', '2025-04-01 15:49:38', 'Sklep Online', 'kontakt@example.com', '', '', '', 0),
(7, 'shop_email', 'kontakt@example.com', '2025-04-01 15:49:39', '2025-04-01 15:49:39', 'Sklep Online', 'kontakt@example.com', '', '', '', 0),
(8, 'enable_vouchers', '1', '2025-04-01 15:49:39', '2025-04-01 15:49:39', 'Sklep Online', 'kontakt@example.com', '', '', '', 0);

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `shipping_addresses`
--

CREATE TABLE `shipping_addresses` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `street` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `postal_code` varchar(6) NOT NULL,
  `shipping_method` varchar(50) NOT NULL,
  `shipping_point` varchar(255) DEFAULT NULL,
  `parcel_locker_street` varchar(255) DEFAULT NULL,
  `parcel_locker_number` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shipping_addresses`
--

INSERT INTO `shipping_addresses` (`id`, `order_id`, `full_name`, `street`, `city`, `postal_code`, `shipping_method`, `shipping_point`, `parcel_locker_street`, `parcel_locker_number`) VALUES
(42, 33, 'Maks Nowak', 'Polaczka 6b/1', 'Siemianowice Śląski', '41-106', '', NULL, NULL, NULL),
(43, 34, 'Maks Nowak', 'Polaczka 6b/1', 'Siemianowice Śląski', '41-106', '', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_admin` tinyint(1) DEFAULT 0,
  `role` enum('user','manager','admin') NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `phone`, `password`, `created_at`, `is_admin`, `role`) VALUES
(18, 'admin', 'maxxio2005@gmail.com', NULL, '$2y$10$5k/.D2usHHVUOfit7wXs4O12HeaftFyOX/S1NYfd39JVvygIx2RES', '2025-03-27 15:50:02', 1, 'admin');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `vouchers`
--

CREATE TABLE `vouchers` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vouchers`
--

INSERT INTO `vouchers` (`id`, `order_id`, `code`, `amount`, `is_used`, `created_at`) VALUES
(4, 0, '1111', 50.00, 0, '2025-04-02 20:30:26');

--
-- Indeksy dla zrzutów tabel
--

--
-- Indeksy dla tabeli `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indeksy dla tabeli `featured_products`
--
ALTER TABLE `featured_products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indeksy dla tabeli `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `shipping_address_id` (`shipping_address_id`);

--
-- Indeksy dla tabeli `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indeksy dla tabeli `order_status`
--
ALTER TABLE `order_status`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indeksy dla tabeli `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indeksy dla tabeli `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indeksy dla tabeli `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indeksy dla tabeli `product_colors`
--
ALTER TABLE `product_colors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indeksy dla tabeli `product_color_images`
--
ALTER TABLE `product_color_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indeksy dla tabeli `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indeksy dla tabeli `product_memories`
--
ALTER TABLE `product_memories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indeksy dla tabeli `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indeksy dla tabeli `shipping_addresses`
--
ALTER TABLE `shipping_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indeksy dla tabeli `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indeksy dla tabeli `vouchers`
--
ALTER TABLE `vouchers`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=91;

--
-- AUTO_INCREMENT for table `featured_products`
--
ALTER TABLE `featured_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `order_status`
--
ALTER TABLE `order_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_status_history`
--
ALTER TABLE `order_status_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;

--
-- AUTO_INCREMENT for table `product_colors`
--
ALTER TABLE `product_colors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `product_color_images`
--
ALTER TABLE `product_color_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `product_memories`
--
ALTER TABLE `product_memories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `shipping_addresses`
--
ALTER TABLE `shipping_addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `vouchers`
--
ALTER TABLE `vouchers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `featured_products`
--
ALTER TABLE `featured_products`
  ADD CONSTRAINT `featured_products_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_status`
--
ALTER TABLE `order_status`
  ADD CONSTRAINT `order_status_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD CONSTRAINT `order_status_history_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_colors`
--
ALTER TABLE `product_colors`
  ADD CONSTRAINT `product_colors_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_color_images`
--
ALTER TABLE `product_color_images`
  ADD CONSTRAINT `product_color_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_memories`
--
ALTER TABLE `product_memories`
  ADD CONSTRAINT `product_memories_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shipping_addresses`
--
ALTER TABLE `shipping_addresses`
  ADD CONSTRAINT `shipping_addresses_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
