-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 19, 2025 at 06:32 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_surat`
--

-- --------------------------------------------------------

--
-- Table structure for table `kontak_luar`
--

CREATE TABLE `kontak_luar` (
  `id_kontak` int(11) NOT NULL,
  `nama_instansi` varchar(255) NOT NULL,
  `alamat` text DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telepon` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kontak_luar`
--

INSERT INTO `kontak_luar` (`id_kontak`, `nama_instansi`, `alamat`, `email`, `telepon`) VALUES
(4, 'LP2M', 'UNPER', 'admin@mail.com', '123'),
(5, 'LP3M', 'UNPER', 'admin@mail.com', '123'),
(6, 'BAAK', 'UNPER', 'admin@mail.com', '123'),
(7, 'BAUMK', 'UNPER', 'admin@mail.com', '123'),
(8, 'UNIVERSITAS', 'UNPER', 'admin@mail.com', '123');

-- --------------------------------------------------------

--
-- Table structure for table `surat`
--

CREATE TABLE `surat` (
  `id_surat` int(11) NOT NULL,
  `nomor_surat` varchar(100) NOT NULL,
  `perihal` varchar(255) NOT NULL,
  `tanggal_surat` date NOT NULL,
  `id_pengirim` int(11) NOT NULL,
  `tujuan` varchar(255) NOT NULL,
  `id_pengirim_eksternal` int(11) DEFAULT NULL,
  `diteruskan_ke` int(11) DEFAULT NULL,
  `diteruskan_oleh` int(11) DEFAULT NULL,
  `tanggal_diteruskan` datetime DEFAULT NULL,
  `tipe_tujuan` enum('internal','eksternal') NOT NULL,
  `isi_ringkas` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','diterima','ditolak') NOT NULL DEFAULT 'pending',
  `catatan_status` text DEFAULT NULL,
  `tanggal_dibuat` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `surat_diteruskan`
--

CREATE TABLE `surat_diteruskan` (
  `id` int(11) NOT NULL,
  `id_surat` int(11) NOT NULL,
  `id_penerima` int(11) NOT NULL,
  `id_penerus` int(11) NOT NULL,
  `tanggal_diteruskan` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `role` enum('prodi_ti','prodi_ts','fakultas','keuangan') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `nama_lengkap`, `role`) VALUES
(7, 'informatika', '$2y$10$aIR9ILm7eKDTW/S.zWMig.Ju.EwxAaCSjEn01s1ap4yy8Pf93uQSG', 'informatika', 'prodi_ti'),
(8, 'sipil', '$2y$10$WpRXbY6ZKZ6I0zMfty8ByuRl59ZB0SDhNxdmwQrtfbZs9gshh/LMC', 'sipil', 'prodi_ts'),
(9, 'fakultas', '$2y$10$M.fVuvOnxgvI9J2i8Jpssu0sKZoYCgvgTj.VBc0mf7JhEkJ7ODACa', 'fakultas', 'fakultas'),
(11, 'keuangan', '$2y$10$RQxcpMMO5hyd0ea1pQIG5e60/6HtSb3dAQYDjsVIEJqDYsQfX1Uo.', 'keuangan', 'keuangan');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `kontak_luar`
--
ALTER TABLE `kontak_luar`
  ADD PRIMARY KEY (`id_kontak`);

--
-- Indexes for table `surat`
--
ALTER TABLE `surat`
  ADD PRIMARY KEY (`id_surat`),
  ADD UNIQUE KEY `nomor_surat` (`nomor_surat`),
  ADD KEY `id_pengirim` (`id_pengirim`);

--
-- Indexes for table `surat_diteruskan`
--
ALTER TABLE `surat_diteruskan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_surat` (`id_surat`),
  ADD KEY `id_penerima` (`id_penerima`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `kontak_luar`
--
ALTER TABLE `kontak_luar`
  MODIFY `id_kontak` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `surat`
--
ALTER TABLE `surat`
  MODIFY `id_surat` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `surat_diteruskan`
--
ALTER TABLE `surat_diteruskan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `surat`
--
ALTER TABLE `surat`
  ADD CONSTRAINT `surat_ibfk_1` FOREIGN KEY (`id_pengirim`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
