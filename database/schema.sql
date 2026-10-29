-- App-Topsis Modern Schema
-- Compatible with MySQL 8.0+ and MariaDB 11+

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `nilai_matriks`;
DROP TABLE IF EXISTS `kriteria`;
DROP TABLE IF EXISTS `alternatif`;
DROP TABLE IF EXISTS `users`;

-- Users table
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL DEFAULT '',
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Alternatif table
CREATE TABLE `alternatif` (
  `id` VARCHAR(10) PRIMARY KEY,
  `nama` VARCHAR(255) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Kriteria table
CREATE TABLE `kriteria` (
  `id` VARCHAR(10) PRIMARY KEY,
  `nama` VARCHAR(100) NOT NULL,
  `bobot` DECIMAL(8,4) NOT NULL,
  `jenis` ENUM('benefit', 'cost') NOT NULL DEFAULT 'benefit',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Nilai Matriks table
CREATE TABLE `nilai_matriks` (
  `id_alternatif` VARCHAR(10) NOT NULL,
  `id_kriteria` VARCHAR(10) NOT NULL,
  `nilai` DECIMAL(12,4) NOT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_alternatif`, `id_kriteria`),
  CONSTRAINT `fk_matriks_alternatif` FOREIGN KEY (`id_alternatif`) 
    REFERENCES `alternatif` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_matriks_kriteria` FOREIGN KEY (`id_kriteria`) 
    REFERENCES `kriteria` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Default Admin Account (username: admin, password: 123)
INSERT INTO `users` (`name`, `username`, `email`, `password`) VALUES
('Administrator', 'admin', 'admin@admin.com', '$2y$10$s5wmxw8R1i3n7TeGhYwi0OiRVClhAzio4kWHPLqi/ojbgVj1ORiba');

-- Seed Kriteria Example
INSERT INTO `kriteria` (`id`, `nama`, `bobot`, `jenis`) VALUES
('C1', 'RAM', 25.0000, 'benefit'),
('C2', 'Storage', 25.0000, 'benefit'),
('C3', 'Color', 25.0000, 'benefit'),
('C4', 'Price', 25.0000, 'cost');
