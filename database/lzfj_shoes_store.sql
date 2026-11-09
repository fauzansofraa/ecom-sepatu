-- =========================================================
--  LZFJ Shoes Store - Database (MySQL / MariaDB)
--  Import lewat phpMyAdmin: buat database `lzfj_shoes_store`
--  lalu tab "Import" -> pilih file ini -> Go.
-- =========================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `lzfj_shoes_store`
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `lzfj_shoes_store`;

DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `promo_codes`;
DROP TABLE IF EXISTS `subscribers`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `users`;

-- ---------------------------------------------------------
-- Users (login email/password & login Google)
-- ---------------------------------------------------------
CREATE TABLE `users` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`          VARCHAR(120) NOT NULL,
  `username`      VARCHAR(60)  NOT NULL,
  `email`         VARCHAR(190) NOT NULL,
  `password_hash` VARCHAR(255) NULL,
  `google_id`     VARCHAR(64)  NULL,
  `avatar_url`    VARCHAR(500) NULL,
  `phone`         VARCHAR(30)  NULL,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  UNIQUE KEY `uq_users_username` (`username`),
  UNIQUE KEY `uq_users_google` (`google_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Products
--   gender   : men | women | kids | unisex
--   category : running | casual | lifestyle
--   sizes    : daftar ukuran dipisah koma
--   colors   : Nama:#HEX dipisah koma
--   rank_all : urutan di halaman Shop (semua produk)
--   position : urutan di halaman Men / Women / Kids
-- ---------------------------------------------------------
CREATE TABLE `products` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug`        VARCHAR(120) NOT NULL,
  `name`        VARCHAR(160) NOT NULL,
  `brand`       VARCHAR(60)  NOT NULL,
  `gender`      ENUM('men','women','kids','unisex') NOT NULL DEFAULT 'unisex',
  `category`    ENUM('running','casual','lifestyle') NOT NULL DEFAULT 'lifestyle',
  `price`       INT UNSIGNED NOT NULL,
  `old_price`   INT UNSIGNED NULL,
  `badge`       VARCHAR(30)  NULL,
  `rating`      DECIMAL(2,1) NOT NULL DEFAULT 0.0,
  `reviews`     INT UNSIGNED NOT NULL DEFAULT 0,
  `sold`        INT UNSIGNED NOT NULL DEFAULT 0,
  `description` TEXT NOT NULL,
  `image`       VARCHAR(190) NOT NULL,
  `sizes`       VARCHAR(120) NOT NULL,
  `colors`      VARCHAR(255) NOT NULL,
  `stock`       INT UNSIGNED NOT NULL DEFAULT 50,
  `is_featured` TINYINT(1)   NOT NULL DEFAULT 0,
  `rank_all`    INT NOT NULL DEFAULT 100,
  `position`    INT NOT NULL DEFAULT 100,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_products_slug` (`slug`),
  KEY `idx_products_gender` (`gender`),
  KEY `idx_products_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Orders
--   status: pending | paid | failed | expired | cancelled | refunded
-- ---------------------------------------------------------
CREATE TABLE `orders` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_code`       VARCHAR(40)  NOT NULL,
  `user_id`          INT UNSIGNED NOT NULL,
  `full_name`        VARCHAR(120) NOT NULL,
  `email`            VARCHAR(190) NOT NULL,
  `phone`            VARCHAR(30)  NOT NULL,
  `city`             VARCHAR(80)  NOT NULL,
  `address`          VARCHAR(255) NOT NULL,
  `postal_code`      VARCHAR(12)  NOT NULL,
  `delivery_method`  ENUM('regular','express') NOT NULL DEFAULT 'regular',
  `payment_method`   ENUM('bank_transfer','ewallet','card') NOT NULL,
  `payment_channel`  VARCHAR(30)  NOT NULL,
  `subtotal`         INT UNSIGNED NOT NULL,
  `shipping_cost`    INT UNSIGNED NOT NULL,
  `discount`         INT UNSIGNED NOT NULL DEFAULT 0,
  `promo_code`       VARCHAR(30)  NULL,
  `total`            INT UNSIGNED NOT NULL,
  `status`           VARCHAR(20)  NOT NULL DEFAULT 'pending',
  `snap_token`       VARCHAR(80)  NULL,
  `snap_redirect_url` VARCHAR(255) NULL,
  `midtrans_transaction_id` VARCHAR(80) NULL,
  `midtrans_payment_type`   VARCHAR(40) NULL,
  `va_number`        VARCHAR(60)  NULL,
  `stock_restored`   TINYINT(1)   NOT NULL DEFAULT 0,
  `paid_at`          DATETIME NULL,
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_orders_code` (`order_code`),
  KEY `idx_orders_user` (`user_id`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `order_items` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id`     INT UNSIGNED NOT NULL,
  `product_id`   INT UNSIGNED NULL,
  `product_name` VARCHAR(160) NOT NULL,
  `image`        VARCHAR(190) NOT NULL,
  `size`         VARCHAR(10)  NOT NULL,
  `color`        VARCHAR(40)  NOT NULL,
  `qty`          INT UNSIGNED NOT NULL,
  `price`        INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_items_order` (`order_id`),
  CONSTRAINT `fk_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `promo_codes` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code`       VARCHAR(30) NOT NULL,
  `type`       ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
  `value`      INT UNSIGNED NOT NULL,
  `max_discount` INT UNSIGNED NULL,
  `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_promo_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `subscribers` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email`      VARCHAR(190) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_subscriber_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------
-- Data produk (sesuai desain Figma)
-- ---------------------------------------------------------
INSERT INTO `products`
(`slug`,`name`,`brand`,`gender`,`category`,`price`,`old_price`,`badge`,`rating`,`reviews`,`sold`,`description`,`image`,`sizes`,`colors`,`stock`,`is_featured`,`rank_all`,`position`) VALUES
('nike-air-max','Nike Air Max','Nike','men','running',1899000,NULL,'NEW',4.8,124,320,
 'Stand out and stay comfortable all day long with the Nike Air Max. Combining legendary Visible Air cushioning with an iconic, futuristic design, these sneakers are engineered to deliver maximum impact protection while elevating your everyday streetwear look.',
 'nike-air-max.webp','39,40,41,42,43','Black:#111111,White:#FFFFFF,Grey:#9AA0AC',40,1,1,1),
('adidas-ultraboost','Adidas Ultraboost','Adidas','men','running',2299000,2874000,'SALE -20%',4.7,98,320,
 'Combining a flexible PRIMEKNIT upper with responsive BOOST cushioning, the adidas Ultraboost delivers maximum energy return and supreme comfort for both running and daily wear.',
 'adidas-ultraboost.webp','39,40,41,42,43','Black:#111111,White:#FFFFFF,Grey:#9AA0AC',35,2,2,2),
('new-balance-530','New Balance 530','New Balance','unisex','lifestyle',1499000,NULL,NULL,4.6,210,540,
 'A retro running silhouette reimagined for everyday wear. The New Balance 530 pairs a breathable mesh upper with ABZORB cushioning for lightweight comfort from morning to night.',
 'new-balance-530.webp','39,40,41,42,43','White:#FFFFFF,Grey:#9AA0AC,Black:#111111',50,3,3,100),
('puma-speedcat-ballet','Puma Speedcat Ballet','Puma','women','lifestyle',1699000,1999000,'SALE -15%',4.5,76,150,
 'Motorsport heritage meets ballet-inspired elegance. The PUMA Speedcat Ballet features a soft suede upper, crossover straps and a slim rubber outsole for an effortless, feminine everyday look.',
 'puma-speedcat-ballet.webp','37,38,39,40,41','Black:#111111,White:#FFFFFF,Grey:#9AA0AC',30,0,4,4),
('converse-chuck-taylor','Converse','Converse','men','lifestyle',899000,NULL,NULL,4.7,110,410,
 'The timeless Converse Chuck Taylor All Star. A durable canvas upper, classic rubber toe cap and cushioned insole make it the go-to sneaker for any outfit, any day.',
 'converse-chuck-taylor.webp','39,40,41,42,43','Black:#111111,White:#FFFFFF,Grey:#9AA0AC',60,0,5,3),
('puma-speedcat','Puma Speedcat','Puma','men','lifestyle',1699000,1999000,'SALE -15%',4.5,76,150,
 'Born on the racetrack, the PUMA Speedcat brings a low-profile suede upper and a grippy rubber outsole for a sleek, motorsport-inspired look that works on and off the street.',
 'puma-speedcat.webp','39,40,41,42,43','Black:#111111,White:#FFFFFF,Grey:#9AA0AC',30,4,6,4),
('adidas-gazelle-indoor','Adidas Gazelle Indoor','Adidas','women','running',1999000,NULL,'NEW',4.8,124,320,
 'Crafted with a premium suede upper and its iconic translucent gum sole, the adidas Gazelle Indoor delivers timeless retro style and low-profile comfort for your everyday streetwear look.',
 'adidas-gazelle-indoor.webp','37,38,39,40,41','Grey:#9AA0AC,Black:#111111,White:#FFFFFF',40,0,7,1),
('nike-v2k-run','Nike V2K Run','Nike','women','running',2299000,2874000,NULL,4.7,98,260,
 'A Y2K-inspired runner with a chunky, layered upper and plush foam midsole. The Nike V2K Run blends early-2000s tech style with modern comfort for all-day wear.',
 'nike-v2k-run.webp','37,38,39,40,41','Grey:#9AA0AC,Black:#111111,White:#FFFFFF',35,0,8,2),
('new-balance-530-women','New Balance 530','New Balance','women','lifestyle',1499000,NULL,NULL,4.6,210,540,
 'A retro running silhouette reimagined for everyday wear. The New Balance 530 pairs a breathable mesh upper with ABZORB cushioning for lightweight comfort from morning to night.',
 'new-balance-530-women.webp','37,38,39,40,41','White:#FFFFFF,Grey:#9AA0AC,Black:#111111',45,0,9,3),
('puma-suede-xl-toddlers','Suede XL Toddlers'' Sneakers','Puma','kids','running',1799000,NULL,'NEW',4.8,124,320,
 'Featuring a playful Y2K-inspired oversized design, the Puma Suede XL Toddlers'' Sneakers combine a soft suede upper, extra padding, and a durable rubber outsole for all-day comfort and stability for little feet.',
 'puma-suede-xl-toddlers.webp','23,24,25,26,27','Blue:#1F4FFF,White:#FFFFFF,Grey:#9AA0AC',40,0,10,1),
('puma-ca-pro-kids','Puma CA Pro Kids Shoes','Puma','kids','running',1299000,2874000,NULL,4.7,98,190,
 'Clean court style for little ones. The PUMA CA Pro Kids Shoes feature a soft leather-look upper, playful graphic details and a cushioned sole that keeps up with every adventure.',
 'puma-ca-pro-kids.webp','23,24,25,26,27','Pink:#F4B8D1,White:#FFFFFF,Grey:#9AA0AC',40,0,11,2),
('adidas-vl-court-kids','Adidas VL Court 3.0 Shoes Kids','Adidas','kids','lifestyle',1599000,NULL,NULL,4.6,210,280,
 'Skate-inspired style made easy. The adidas VL Court 3.0 Kids comes with hook-and-loop straps for quick on and off, a soft upper and a gum rubber outsole for steady steps.',
 'adidas-vl-court-kids.webp','23,24,25,26,27','White:#FFFFFF,Pink:#F4B8D1,Grey:#9AA0AC',40,0,12,3),
('adidas-performance-court-kids','Adidas Performance Court','Adidas','kids','lifestyle',1899000,1999000,'SALE -15%',4.5,76,150,
 'Bright colours, big fun. The adidas Performance Court for kids pairs a durable upper with an easy strap closure and a grippy gum sole built for the playground.',
 'adidas-performance-court-kids.webp','23,24,25,26,27','Blue:#3A8EE6,White:#FFFFFF,Grey:#9AA0AC',40,0,13,4);

-- Kode promo (sesuai "Get 10% off your first order")
INSERT INTO `promo_codes` (`code`,`type`,`value`,`max_discount`,`is_active`) VALUES
('LZFJ10','percent',10,500000,1),
('HEMAT50K','fixed',50000,NULL,1);
