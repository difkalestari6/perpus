-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Waktu pembuatan: 21 Nov 2025 pada 13.02
-- Versi server: 8.0.30
-- Versi PHP: 8.3.9

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `perpustakaan_online`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `books`
--

CREATE TABLE `books` (
  `id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(100) NOT NULL,
  `description` text,
  `cover_image` varchar(255) DEFAULT NULL,
  `content` text,
  `price` decimal(10,2) DEFAULT '0.00',
  `category_id` int DEFAULT NULL,
  `is_free` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `books`
--

INSERT INTO `books` (`id`, `title`, `author`, `description`, `cover_image`, `content`, `price`, `category_id`, `is_free`, `created_at`) VALUES
(1, 'Panduan Belajar PHP', 'John Doe', 'Buku lengkap untuk belajar PHP dari dasar hingga mahir', 'php-book.jpg', 'Ini adalah konten buku PHP yang lengkap...', 50000.00, 3, 0, '2025-11-21 12:33:48'),
(2, 'Cerita Rakyat Nusantara', 'Jane Smith', 'Kumpulan cerita rakyat dari seluruh Indonesia', 'folklore.jpg', 'Dahulu kala, di sebuah desa yang indah...', 0.00, 1, 1, '2025-11-21 12:33:48'),
(3, 'Strategi Marketing Digital', 'Michael Brown', 'Panduan praktis marketing di era digital', 'marketing.jpg', 'Marketing digital adalah kunci sukses bisnis modern...', 75000.00, 4, 0, '2025-11-21 12:33:48');

-- --------------------------------------------------------

--
-- Struktur dari tabel `book_purchases`
--

CREATE TABLE `book_purchases` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `book_id` int NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `purchased_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `book_purchases`
--

INSERT INTO `book_purchases` (`id`, `user_id`, `book_id`, `price`, `purchased_at`) VALUES
(1, 2, 1, 50000.00, '2025-11-21 12:51:23');

-- --------------------------------------------------------

--
-- Struktur dari tabel `categories`
--

CREATE TABLE `categories` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Fiksi', 'Novel dan cerita fiksi', '2025-11-21 12:33:48'),
(2, 'Non-Fiksi', 'Buku pengetahuan dan fakta', '2025-11-21 12:33:48'),
(3, 'Teknologi', 'Buku tentang teknologi dan pemrograman', '2025-11-21 12:33:48'),
(4, 'Bisnis', 'Buku tentang bisnis dan kewirausahaan', '2025-11-21 12:33:48'),
(5, 'Pendidikan', 'Buku pendidikan dan pembelajaran', '2025-11-21 12:33:48');

-- --------------------------------------------------------

--
-- Struktur dari tabel `subscription_plans`
--

CREATE TABLE `subscription_plans` (
  `id` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `duration_days` int NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `description` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `subscription_plans`
--

INSERT INTO `subscription_plans` (`id`, `name`, `duration_days`, `price`, `description`) VALUES
(1, 'Paket Mingguan', 7, 15000.00, 'Akses semua buku premium selama 7 hari'),
(2, 'Paket Bulanan', 30, 50000.00, 'Akses semua buku premium selama 30 hari'),
(3, 'Paket Tahunan', 365, 500000.00, 'Akses semua buku premium selama 1 tahun');

-- --------------------------------------------------------

--
-- Struktur dari tabel `transactions`
--

CREATE TABLE `transactions` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `type` enum('book','subscription') NOT NULL,
  `reference_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','completed','failed') DEFAULT 'completed',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `transactions`
--

INSERT INTO `transactions` (`id`, `user_id`, `type`, `reference_id`, `amount`, `status`, `created_at`) VALUES
(1, 2, 'book', 1, 50000.00, 'completed', '2025-11-21 12:51:23');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `email_verified` tinyint(1) DEFAULT '0',
  `is_verified` tinyint(1) DEFAULT '0',
  `verification_token` varchar(255) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expire` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `role`, `email_verified`, `is_verified`, `verification_token`, `reset_token`, `reset_token_expire`, `created_at`) VALUES
(1, 'admin', 'admin@perpustakaan.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin', 0, 0, NULL, NULL, NULL, '2025-11-21 12:33:48'),
(2, 'riko', 'priko3020@gmail.com', '$2y$10$PvhCE8kpeKSV/JNDk2.yFubbqw3Ldo9bp4fvOtaPxs1lK3qFrK4IS', 'riko adi pratama', 'user', 1, 0, NULL, NULL, NULL, '2025-11-21 12:43:34'),
(3, 'zahra', 'rikop2424@gmail.com', '$2y$10$/blUDL4KJi323dERjjGaZ.P6IDbrkEJ.naFbQJ0sFqgqBvr2F1QVq', 'zahra', 'user', 0, 0, 'b2cb666ed0700979aaa90365fe00f252dc4d81c8e2e4686975ebd32e85666620', NULL, NULL, '2025-11-21 12:53:04'),
(4, 'hghghg', 'rikop454@gmail.com', '$2y$10$.3ATr6y1DWZua4scwO7PTuSLzUFTf3L02vBoqiiYOdgsho7RdBJeO', 'hghghg', 'user', 0, 0, 'c68bda2a638c75ef1a422902c65ac40146fd8d275f9ed4bc5e0da7b11bff73f8', NULL, NULL, '2025-11-21 12:56:20'),
(5, 'koko', 'yantokrisna53@gmail.com', '$2y$10$JxuJUwEVx/Z1TLWAi9M2W.SyLwjMOOuhq9p0zXy5b.5zdsWo2uFHC', 'koko', 'user', 1, 0, NULL, NULL, NULL, '2025-11-21 13:00:55');

-- --------------------------------------------------------

--
-- Struktur dari tabel `user_subscriptions`
--

CREATE TABLE `user_subscriptions` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `plan_id` int NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indeks untuk tabel `book_purchases`
--
ALTER TABLE `book_purchases`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_purchase` (`user_id`,`book_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indeks untuk tabel `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `subscription_plans`
--
ALTER TABLE `subscription_plans`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indeks untuk tabel `user_subscriptions`
--
ALTER TABLE `user_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `plan_id` (`plan_id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `books`
--
ALTER TABLE `books`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `book_purchases`
--
ALTER TABLE `book_purchases`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `subscription_plans`
--
ALTER TABLE `subscription_plans`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `user_subscriptions`
--
ALTER TABLE `user_subscriptions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `books`
--
ALTER TABLE `books`
  ADD CONSTRAINT `books_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `book_purchases`
--
ALTER TABLE `book_purchases`
  ADD CONSTRAINT `book_purchases_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `book_purchases_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `user_subscriptions`
--
ALTER TABLE `user_subscriptions`
  ADD CONSTRAINT `user_subscriptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_subscriptions_ibfk_2` FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
