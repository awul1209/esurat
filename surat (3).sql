-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 26, 2026 at 01:47 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `surat`
--

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(191) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('iddrus@gmail.com|127.0.0.1', 'i:1;', 1769306541),
('iddrus@gmail.com|127.0.0.1:timer', 'i:1769306541;', 1769306541),
('tolaksriwulandari090204@gmail.com|127.0.0.1', 'i:1;', 1768880770),
('tolaksriwulandari090204@gmail.com|127.0.0.1:timer', 'i:1768880770;', 1768880770);

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(191) NOT NULL,
  `owner` varchar(191) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `disposisis`
--

CREATE TABLE `disposisis` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `surat_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `klasifikasi_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tujuan_satker_id` bigint(20) UNSIGNED DEFAULT NULL,
  `catatan_rektor` text DEFAULT NULL,
  `disposisi_lain` text DEFAULT NULL,
  `status_penerimaan` varchar(191) NOT NULL DEFAULT 'belum_diproses',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `disposisis`
--

INSERT INTO `disposisis` (`id`, `surat_id`, `user_id`, `klasifikasi_id`, `tujuan_satker_id`, `catatan_rektor`, `disposisi_lain`, `status_penerimaan`, `created_at`, `updated_at`) VALUES
(78, 163, 2, 2, 10, 'HADIRI', NULL, 'selesai', '2026-01-26 11:04:18', '2026-01-26 11:07:55'),
(79, 164, 2, 2, 10, 'HADIRI ACARANYA', NULL, 'belum_diproses', '2026-01-26 11:09:37', '2026-01-26 11:09:37'),
(80, 165, 2, 4, 10, 'PELAJARI', NULL, 'belum_diproses', '2026-01-26 11:14:03', '2026-01-26 11:14:03'),
(81, 167, 2, 5, NULL, 'TANGANI DAN SELESAIKAN', 'BEM UNIV', 'belum_diproses', '2026-01-26 11:20:45', '2026-01-26 11:20:45'),
(82, 170, 2, 3, 10, 'asdfgh', NULL, 'belum_diproses', '2026-01-26 12:17:15', '2026-01-26 12:17:15');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(191) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(191) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(191) NOT NULL,
  `name` varchar(191) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `klasifikasis`
--

CREATE TABLE `klasifikasis` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `kode_klasifikasi` varchar(191) NOT NULL,
  `nama_klasifikasi` varchar(191) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `klasifikasis`
--

INSERT INTO `klasifikasis` (`id`, `kode_klasifikasi`, `nama_klasifikasi`, `deskripsi`, `created_at`, `updated_at`) VALUES
(1, '001', 'Segera', NULL, '2025-12-08 18:05:29', '2025-12-08 18:05:29'),
(2, '002', 'Disposisi', NULL, '2025-12-08 18:05:29', '2025-12-08 18:05:29'),
(3, '003', 'Tindak Lanjut', NULL, '2025-12-08 18:05:29', '2025-12-08 18:05:29'),
(4, '004', 'Selesaikan', NULL, '2025-12-08 18:05:29', '2025-12-08 18:05:29'),
(5, '005', 'Pedomani', NULL, '2025-12-08 18:05:30', '2025-12-08 18:05:30'),
(6, '006', 'Sarankan', NULL, '2025-12-08 18:05:30', '2025-12-08 18:05:30'),
(7, '007', 'Untuk Diketahui', NULL, '2025-12-08 18:05:30', '2025-12-08 18:05:30'),
(8, '008', 'Untuk Diproses', NULL, '2025-12-08 18:05:30', '2025-12-08 18:05:30'),
(9, '009', 'Sampaikan Ybs.', NULL, '2025-12-08 18:05:30', '2025-12-08 18:05:30'),
(10, '010', 'Siapkan', NULL, '2025-12-08 18:05:30', '2025-12-08 18:05:30'),
(11, '011', 'Pertimbangkan', NULL, '2025-12-08 18:05:30', '2025-12-08 18:05:30'),
(12, '012', 'Agar Menghadap Saya', NULL, '2025-12-08 18:05:30', '2025-12-08 18:05:30'),
(13, '013', 'Periksa Disposisi Saya di Dalam', NULL, '2025-12-08 18:05:30', '2025-12-08 18:05:30'),
(14, '014', 'Agar Hadir', NULL, '2025-12-08 18:05:30', '2025-12-08 18:05:30'),
(15, '015', 'Kompulir', NULL, '2025-12-08 18:05:30', '2025-12-08 18:05:30'),
(16, '016', 'Agendakan', NULL, '2025-12-08 18:05:30', '2025-12-08 18:05:30'),
(17, '017', 'Laporkan Hasilnya', NULL, '2025-12-08 18:05:30', '2025-12-08 18:05:30'),
(18, '018', 'Untuk diwakili', NULL, '2025-12-08 18:05:30', '2025-12-08 18:05:30'),
(19, '420', 'Pendidikan', NULL, '2025-12-08 18:05:30', '2025-12-08 18:05:30'),
(20, '800', 'Kepegawaian', NULL, '2025-12-08 18:05:30', '2025-12-08 18:05:30'),
(21, '900', 'Arsipkan', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `klasifikasi_surat`
--

CREATE TABLE `klasifikasi_surat` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `surat_id` bigint(20) UNSIGNED NOT NULL,
  `klasifikasi_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `klasifikasi_surat`
--

INSERT INTO `klasifikasi_surat` (`id`, `surat_id`, `klasifikasi_id`, `created_at`, `updated_at`) VALUES
(73, 163, 2, NULL, NULL),
(74, 164, 2, NULL, NULL),
(75, 164, 3, NULL, NULL),
(76, 165, 4, NULL, NULL),
(77, 166, 21, NULL, NULL),
(78, 167, 5, NULL, NULL),
(79, 168, 21, NULL, NULL),
(80, 170, 3, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(191) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2025_11_06_054124_create_satkers_table', 1),
(5, '2025_11_06_054238_create_surats_table', 1),
(6, '2025_11_06_055645_create_klasifikasis_table', 1),
(7, '2025_11_06_055646_create_disposisis_table', 1),
(8, '2025_11_06_055729_create_riwayat_surats_table', 1),
(9, '2025_11_07_035817_add_tujuan_satker_id_to_surats_table', 1),
(10, '2025_11_10_071407_add_disposisi_lain_to_disposisis_table', 1),
(11, '2025_11_10_083115_add_tujuan_wadek_to_disposisis_table', 1),
(12, '2025_11_11_015147_add_tujuan_user_id_to_surats_table', 1),
(13, '2025_11_11_024746_add_satker_constraint_to_users_table', 1),
(14, '2025_11_11_030826_remove_tujuan_wadek_from_disposisis_table', 1),
(15, '2025_11_12_024200_create_surat_edaran_satker_table', 1),
(16, '2025_11_17_021044_create_surat_keluars_table', 1),
(17, '2025_12_09_024125_add_tujuan_tipe_to_surats_table', 2),
(18, '2025_12_09_040532_create_surat_delegasi_table', 3),
(19, '2025_12_09_120511_add_status_penerimaan_to_disposisis_table', 4),
(20, '2025_12_11_012515_add_tujuan_internal_to_surat_keluars', 5),
(22, '2025_12_11_014055_create_surat_keluar_internal_pivot_table', 6),
(23, '2025_12_12_013057_add_no_hp_to_users_table', 7),
(24, '2025_12_17_020357_add_tujuan_luar_to_surat_keluars_table', 8),
(25, '2025_12_17_020121_add_tujuan_eksternal_to_surats_table', 9),
(26, '2025_12_18_055223_create_klasifikasi_surat_table', 9),
(27, '2026_01_13_114120_add_status_to_surat_keluars_table', 10),
(28, '2026_01_13_131702_add_is_read_to_pivot_table', 11),
(29, '2026_01_15_085326_add_via_to_surat_keluars_table', 12),
(30, '2026_01_15_102333_create_surat_keluar_rektor_tujuan_table', 13),
(31, '2026_01_15_111349_add_tanggal_terusan_to_surat_keluars_table', 14),
(32, '2026_01_15_132545_add_email2_to_users_table', 15),
(33, '2026_01_19_082456_add_soft_deletes_to_surats_table', 16),
(34, '2026_01_19_084056_add_soft_deletes_to_surats_and_surat_keluars', 17),
(35, '2026_01_19_145913_make_surat_id_nullable_on_riwayat_surats_table', 18),
(36, '2026_01_19_150319_add_surat_keluar_id_to_riwayat_surats_table', 19),
(37, '2026_01_19_150919_add_surat_keluar_id_to_riwayat_surats_table', 20),
(38, '2026_01_19_162212_add_penerima_id_to_riwayat_surats_table', 21),
(39, '2026_01_20_152149_add_is_read_to_riwayat_surats_table', 22),
(40, '2026_01_20_152242_add_is_read_to_riwayat_surats_table', 23);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(191) NOT NULL,
  `token` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `riwayat_surats`
--

CREATE TABLE `riwayat_surats` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `surat_id` bigint(20) UNSIGNED DEFAULT NULL,
  `surat_keluar_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `penerima_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status_aksi` varchar(191) NOT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_read` int(11) NOT NULL DEFAULT 0 COMMENT '0: Menunggu, 2: Selesai'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `riwayat_surats`
--

INSERT INTO `riwayat_surats` (`id`, `surat_id`, `surat_keluar_id`, `user_id`, `penerima_id`, `status_aksi`, `catatan`, `created_at`, `updated_at`, `is_read`) VALUES
(431, NULL, 234, 6, 8, 'Informasi: Informasi Umum', 'Surat ini disebarluaskan kepada seluruh pegawai untuk diketahui.', '2026-01-26 10:19:13', '2026-01-26 10:19:13', 0),
(432, NULL, 234, 6, 13, 'Informasi: Informasi Umum', 'Surat ini disebarluaskan kepada seluruh pegawai untuk diketahui.', '2026-01-26 10:19:13', '2026-01-26 10:19:13', 0),
(433, NULL, 236, 6, 8, 'Disposisi: Agar Hadir', 'memakai almamater', '2026-01-26 10:23:42', '2026-01-26 10:23:42', 0),
(434, 160, 237, 3, 8, 'Surat Diterima oleh Pegawai', 'Surat dikirim langsung ke Pegawai spesifik.', '2026-01-26 10:25:02', '2026-01-26 10:27:05', 2),
(435, NULL, 238, 6, 8, 'Informasi: Informasi Umum', 'Surat ini disebarluaskan kepada seluruh pegawai untuk diketahui.', '2026-01-26 10:28:40', '2026-01-26 10:28:40', 0),
(436, NULL, 238, 6, 13, 'Informasi: Informasi Umum', 'Surat ini disebarluaskan kepada seluruh pegawai untuk diketahui.', '2026-01-26 10:28:40', '2026-01-26 10:28:40', 0),
(437, NULL, 240, 6, 8, 'Informasi: Informasi Umum', 'Surat ini disebarluaskan kepada seluruh pegawai untuk diketahui.', '2026-01-26 10:41:08', '2026-01-26 10:41:08', 0),
(438, NULL, 240, 6, 13, 'Informasi: Informasi Umum', 'Surat ini disebarluaskan kepada seluruh pegawai untuk diketahui.', '2026-01-26 10:41:08', '2026-01-26 10:41:08', 0),
(439, NULL, 241, 6, 8, 'Disposisi: Agar Hadir', 'PAKAI PDH', '2026-01-26 10:42:34', '2026-01-26 10:42:34', 0),
(440, 161, 242, 1, 8, 'Surat Diterima oleh Pegawai', 'Surat dikirim langsung ke Pegawai spesifik.', '2026-01-26 10:43:46', '2026-01-26 10:45:00', 2),
(441, NULL, 244, 1, 12, 'Delegasi: Tindak Lanjuti', 'hadiri memakai pdh', '2026-01-26 10:51:25', '2026-01-26 10:51:25', 0),
(442, NULL, 245, 1, 12, 'Informasi Umum: Informasi Umum', 'Surat ini disebarluaskan kepada seluruh pegawai BAU untuk diketahui.', '2026-01-26 10:52:36', '2026-01-26 10:52:36', 0),
(443, NULL, 245, 1, 14, 'Informasi Umum: Informasi Umum', 'Surat ini disebarluaskan kepada seluruh pegawai BAU untuk diketahui.', '2026-01-26 10:52:36', '2026-01-26 10:52:36', 0),
(444, 162, 246, 6, 12, 'Surat Diterima oleh Pegawai', 'Surat dikirim langsung ke Pegawai spesifik.', '2026-01-26 10:59:40', '2026-01-26 10:59:57', 2),
(445, 163, NULL, 1, NULL, 'Input Surat', 'Surat diinput dan diteruskan ke Admin Rektor', '2026-01-26 11:03:40', '2026-01-26 11:03:40', 0),
(446, 163, NULL, 2, NULL, 'Disposisi Rektor', 'Rektor mendisposisikan surat ke: Dekan FEB. (Menunggu BAU meneruskan).', '2026-01-26 11:04:18', '2026-01-26 11:04:18', 0),
(447, 163, NULL, 1, NULL, 'Dikirim ke Satker/Penerima', 'Dikirim ke: Dekan FEB', '2026-01-26 11:07:09', '2026-01-26 11:07:09', 0),
(448, 163, NULL, 6, NULL, 'Diarsipkan/Selesai di Satker', 'Surat ditandai selesai oleh Satker FEB (Tidak didelegasikan).', '2026-01-26 11:07:55', '2026-01-26 11:07:55', 0),
(449, 164, NULL, 1, NULL, 'Input Surat', 'Surat diinput dan diteruskan ke Admin Rektor', '2026-01-26 11:08:55', '2026-01-26 11:08:55', 0),
(450, 164, NULL, 2, NULL, 'Disposisi Rektor', 'Rektor mendisposisikan surat ke: Dekan FEB. (Menunggu BAU meneruskan).', '2026-01-26 11:09:37', '2026-01-26 11:09:37', 0),
(451, 164, NULL, 1, NULL, 'Dikirim ke Satker/Penerima', 'Dikirim ke: Dekan FEB', '2026-01-26 11:10:02', '2026-01-26 11:10:02', 0),
(452, 164, NULL, 6, 8, 'Delegasi: Hadir / Wakili', 'PAKAI PDH', '2026-01-26 11:11:48', '2026-01-26 11:11:48', 0),
(453, 165, NULL, 1, NULL, 'Input Surat', 'Surat diinput dan diteruskan ke Admin Rektor', '2026-01-26 11:13:29', '2026-01-26 11:13:29', 0),
(454, 165, NULL, 2, NULL, 'Disposisi Rektor', 'Rektor mendisposisikan surat ke: Dekan FEB. (Menunggu BAU meneruskan).', '2026-01-26 11:14:03', '2026-01-26 11:14:03', 0),
(455, 165, NULL, 1, NULL, 'Dikirim ke Satker/Penerima', 'Dikirim ke: Dekan FEB', '2026-01-26 11:14:35', '2026-01-26 11:14:35', 0),
(456, 165, NULL, 6, 8, 'Informasi Umum', 'Untuk diketahui dan dipelajari.', '2026-01-26 11:14:57', '2026-01-26 11:14:57', 0),
(457, 165, NULL, 6, 13, 'Informasi Umum', 'Untuk diketahui dan dipelajari.', '2026-01-26 11:14:57', '2026-01-26 11:14:57', 0),
(458, 166, NULL, 1, NULL, 'Input Surat', 'Surat diinput dan diteruskan ke Admin Rektor', '2026-01-26 11:16:58', '2026-01-26 11:16:58', 0),
(459, 166, NULL, 2, NULL, 'Selesai (Arsip Rektor)', 'Surat disetujui/dibaca oleh Rektor. Langsung diarsipkan (Tanpa Disposisi).', '2026-01-26 11:17:31', '2026-01-26 11:17:31', 0),
(460, 167, NULL, 1, NULL, 'Input Surat', 'Surat diinput dan diteruskan ke Admin Rektor', '2026-01-26 11:19:28', '2026-01-26 11:19:28', 0),
(461, 167, NULL, 2, NULL, 'Disposisi Rektor', 'Rektor mendisposisikan surat ke: BEM UNIV (Eksternal). (Menunggu BAU meneruskan).', '2026-01-26 11:20:45', '2026-01-26 11:20:45', 0),
(462, 167, NULL, 1, NULL, 'Selesai (Manual)', 'Diarsipkan oleh BAU.', '2026-01-26 11:21:21', '2026-01-26 11:21:21', 0),
(463, 168, NULL, 6, NULL, 'Surat Masuk Internal', 'Surat dikirim ke BAU. Menunggu verifikasi BAU.', '2026-01-26 11:22:39', '2026-01-26 11:22:39', 0),
(464, 168, NULL, 1, NULL, 'Diteruskan ke Admin Rektor', 'Diteruskan ke Admin Rektor.', '2026-01-26 11:25:06', '2026-01-26 11:25:06', 0),
(465, 168, NULL, 2, NULL, 'Selesai (Arsip Rektor)', 'Surat disetujui/dibaca oleh Rektor. Langsung diarsipkan (Tanpa Disposisi).', '2026-01-26 11:25:32', '2026-01-26 11:25:32', 0),
(466, 169, 248, 6, 5, 'Surat Diterima oleh Pegawai', 'Surat dikirim langsung ke Pegawai spesifik.', '2026-01-26 11:30:00', '2026-01-26 11:30:28', 2),
(467, NULL, 251, 6, 8, 'Disposisi: Agar Hadir', 'HADIRI', '2026-01-26 12:00:41', '2026-01-26 12:00:41', 0),
(468, NULL, 252, 6, 8, 'Informasi: Informasi Umum', 'Surat ini disebarluaskan kepada seluruh pegawai untuk diketahui.', '2026-01-26 12:12:44', '2026-01-26 12:12:44', 0),
(469, NULL, 252, 6, 13, 'Informasi: Informasi Umum', 'Surat ini disebarluaskan kepada seluruh pegawai untuk diketahui.', '2026-01-26 12:12:44', '2026-01-26 12:12:44', 0),
(470, 170, NULL, 1, NULL, 'Input Surat', 'Surat diinput dan diteruskan ke Admin Rektor', '2026-01-26 12:16:54', '2026-01-26 12:16:54', 0),
(471, 170, NULL, 2, NULL, 'Disposisi Rektor', 'Rektor mendisposisikan surat ke: Dekan FEB. (Menunggu BAU meneruskan).', '2026-01-26 12:17:15', '2026-01-26 12:17:15', 0),
(472, 170, NULL, 1, NULL, 'Dikirim ke Satker/Penerima', 'Dikirim ke: Dekan FEB', '2026-01-26 12:17:46', '2026-01-26 12:17:46', 0),
(473, 170, NULL, 6, 8, 'Delegasi: Segera Tindak Lanjuti', 'asdfghhj', '2026-01-26 12:19:02', '2026-01-26 12:19:02', 0),
(474, 170, NULL, 6, 13, 'Delegasi: Segera Tindak Lanjuti', 'asdfghhj', '2026-01-26 12:19:02', '2026-01-26 12:19:02', 0);

-- --------------------------------------------------------

--
-- Table structure for table `satkers`
--

CREATE TABLE `satkers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nama_satker` varchar(191) NOT NULL,
  `singkatan` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `satkers`
--

INSERT INTO `satkers` (`id`, `nama_satker`, `singkatan`, `created_at`, `updated_at`) VALUES
(1, 'Rektor', NULL, NULL, NULL),
(2, 'Wakil Rektor I', NULL, NULL, NULL),
(3, 'Wakil Rektor II', NULL, NULL, NULL),
(4, 'Wakil Rektor III', NULL, NULL, NULL),
(5, 'Sekretaris Rektor', NULL, NULL, NULL),
(6, 'Kepala Sekretariatan', NULL, NULL, NULL),
(7, 'Dekan FH', 'FH', NULL, NULL),
(8, 'Wadek I FH', 'FH', NULL, NULL),
(9, 'Wadek II FH', 'FH', NULL, NULL),
(10, 'Dekan FEB', 'FEB', NULL, NULL),
(11, 'Wadek I FEB', 'FEB', NULL, NULL),
(12, 'Wadek II FEB', 'FEB', NULL, NULL),
(13, 'Dekan FISIP', 'FISIP', NULL, NULL),
(14, 'Wadek I FISIP', 'FISIP', NULL, NULL),
(15, 'Wadek II FISIP', 'FISIP', NULL, NULL),
(16, 'Dekan FT', 'FT', NULL, NULL),
(17, 'Wadek I FT', 'FT', NULL, NULL),
(18, 'Wadek II FT', 'FT', NULL, NULL),
(19, 'Dekan FIK', 'FIK', NULL, NULL),
(20, 'Wadek I FIK', 'FIK', NULL, NULL),
(21, 'Wadek II FIK', 'FIK', NULL, NULL),
(22, 'Dekan FKIP', 'FKIP', NULL, NULL),
(23, 'Wadek I FKIP', 'FKIP', NULL, NULL),
(24, 'Wadek II FKIP', 'FKIP', NULL, NULL),
(25, 'Direktur PASCASARJANA', 'PASCASARJANA', NULL, NULL),
(26, 'Wadek I PASCASARJANA', 'PASCASARJANA', NULL, NULL),
(27, 'Wadek II PASCASARJANA', 'PASCASARJANA', NULL, NULL),
(28, 'Ketua Pusat Jaminan Mutu', NULL, NULL, NULL),
(29, 'Ketua Satuan Pengendali Internal', NULL, NULL, NULL),
(30, 'Kepala Lembaga Penelitian dan Pengamdian Kepada Masyarakat', 'LPPM', NULL, NULL),
(31, 'Kepala Lembaga Bantuan Hukum', 'LBH', NULL, NULL),
(32, 'Kepala Badan Pengelola usaha', NULL, NULL, NULL),
(33, 'Kepala Biro Administrasi Akademik dan Kemahasiswaan', NULL, NULL, NULL),
(34, 'Kepala Biro Administrasi Umum', 'BAU', NULL, NULL),
(35, 'Kepala Biro Administrasi Keuangan', NULL, NULL, NULL),
(36, 'Kepala Biro Administrasi Perencanaan, Sistem Informasi dan Pangkalan Data', NULL, NULL, NULL),
(37, 'Kepala UPT Perpustakaan', NULL, NULL, NULL),
(38, 'Kepala UPT Laboratorium/Studio', NULL, NULL, NULL),
(39, 'Kepala UPT Pusat Bahasa', NULL, NULL, NULL),
(40, 'Kepala UPT Pusat Layanan Karier dan Konseling', NULL, NULL, NULL),
(41, 'Kepala UPT Pusat Layanan Kesehatan', NULL, NULL, NULL),
(42, 'Kepala UPT Penerimaan Mahasiswa Baru', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(191) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('jYhtIhDn1EYFAnahmssMU4ECkEn8eaA5nXRNWyGQ', 6, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiR0VMTng4QWlubmxIYU5yWUI2d1g0UEhBdVBZSVJsR2ViVnJITGoxNCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NDk6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9zYXRrZXIvc3VyYXQtbWFzdWstaW50ZXJuYWwiO31zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aTo2O3M6NDoiYXV0aCI7YToxOntzOjIxOiJwYXNzd29yZF9jb25maXJtZWRfYXQiO2k6MTc2OTQyNzY2ODt9fQ==', 1769431632),
('kELeRD5aI174iodS0zy56NsyCuUCNmyMFF1YYQTg', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiQ1ZZSzRqUDliajBOQjcySXNDQ213WEtTeFhxdmFSNHFuc1RpSk5ZMyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9iYXUvaW5ib3giO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO3M6NDoiYXV0aCI7YToxOntzOjIxOiJwYXNzd29yZF9jb25maXJtZWRfYXQiO2k6MTc2OTQyMDk5OTt9fQ==', 1769430628),
('skkGD7id8hMJiElROCCSB4kg2cm3I96FGZy8XfaP', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiWlJoNjdtMjA2czlvbG1rQWJuUWRpa2ZQcm93QzhQY3Bsd1ZTRXl3OCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMCI7fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjI7czo0OiJhdXRoIjthOjE6e3M6MjE6InBhc3N3b3JkX2NvbmZpcm1lZF9hdCI7aToxNzY5NDI1MzExO319', 1769431651),
('vtDFaEaPtHenhUqNkz1D05GvsKWMLn24MRHmbTj6', 13, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiTFROOHhyOExsNWtJaDFvSVR3SFd1am5KNVBsamV5R1ZuTmxTckJPbSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NDk6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9wZWdhd2FpL3N1cmF0LW1hc3VrL3ByaWJhZGkiO31zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxMztzOjQ6ImF1dGgiO2E6MTp7czoyMToicGFzc3dvcmRfY29uZmlybWVkX2F0IjtpOjE3Njk0MzA0MjU7fX0=', 1769430427);

-- --------------------------------------------------------

--
-- Table structure for table `surats`
--

CREATE TABLE `surats` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `surat_dari` varchar(191) NOT NULL,
  `tanggal_surat` date NOT NULL,
  `nomor_surat` varchar(191) NOT NULL,
  `perihal` varchar(191) NOT NULL,
  `diterima_tanggal` date NOT NULL,
  `no_agenda` varchar(191) NOT NULL,
  `sifat` varchar(191) NOT NULL,
  `tipe_surat` enum('internal','eksternal') NOT NULL,
  `file_surat` varchar(191) NOT NULL,
  `status` varchar(191) NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `tujuan_tipe` varchar(191) DEFAULT NULL COMMENT 'rektor, universitas, satker, pegawai, edaran_semua_satker',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `tujuan_satker_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tujuan_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `surats`
--

INSERT INTO `surats` (`id`, `surat_dari`, `tanggal_surat`, `nomor_surat`, `perihal`, `diterima_tanggal`, `no_agenda`, `sifat`, `tipe_surat`, `file_surat`, `status`, `user_id`, `tujuan_tipe`, `created_at`, `updated_at`, `tujuan_satker_id`, `tujuan_user_id`, `deleted_at`) VALUES
(160, 'Dekan FT', '2026-01-27', 'FT04', 'undangan ft4', '2026-01-26', 'PEGAWAI-697740FE860D9', 'Asli', 'internal', 'surat_keluar_internal_satker/2m9vT8w79XWDeLiI5dRZHMLEbfddEUkxaB32tDtn.jpg', 'proses', 3, 'pegawai', '2026-01-26 10:25:02', '2026-01-26 10:25:02', NULL, 8, NULL),
(161, 'BAU / Universitas', '2026-01-28', 'BAU4', 'UNDANGAN BAU4', '2026-01-26', 'PEGAWAI-697745629D2FB', 'Asli', 'internal', 'sk_bau_internaleks/02aVGFESr4S5LJKd26Z4fKAD9BvxOhN3iquYfjT4.jpg', 'proses', 1, 'pegawai', '2026-01-26 10:43:46', '2026-01-26 10:43:46', NULL, 8, NULL),
(162, 'Dekan FEB', '2026-01-29', 'FEB4', 'undangan bau4', '2026-01-26', 'PEGAWAI-6977491CCC3EC', 'Asli', 'internal', 'surat_keluar_internal_satker/WAaCm2n56vgum7BIXLYfWCsEoEYBAw0odUJsVHzz.jpg', 'proses', 6, 'pegawai', '2026-01-26 10:59:40', '2026-01-26 10:59:40', NULL, 12, NULL),
(163, 'Universitas Islam Malang', '2026-01-29', 'EKSR01', 'UNDANGAN REK1', '2026-01-26', 'AG01', 'Asli', 'eksternal', 'surat/797FbGEtFLQmXFId73kGK4mwOhmAj6U8NdKd5QM9.jpg', 'selesai', 1, 'universitas', '2026-01-26 11:03:40', '2026-01-26 11:07:55', NULL, NULL, NULL),
(164, 'Dinas Pendidikan Sumenep', '2026-01-29', 'EKSREK2', 'UNDANGAN REK2', '2026-01-26', 'AG02', 'Asli', 'eksternal', 'surat/05n9sDw9hQP74mX7NKV39FCf3MetnD3lIQNF7s1e.jpg', 'selesai', 1, 'universitas', '2026-01-26 11:08:55', '2026-01-26 11:11:48', NULL, NULL, NULL),
(165, 'Universitas Gajah Madah', '2026-01-29', 'EKSREK3', 'UNDANGAN REK3', '2026-01-26', 'AG03', 'Asli', 'eksternal', 'surat/KBm6BJzysmErCxzezuwyux74E89kWDWNAfHQDJ8u.jpg', 'selesai', 1, 'universitas', '2026-01-26 11:13:29', '2026-01-26 11:14:57', NULL, NULL, NULL),
(166, 'UNIVERSITAS JEMBER', '2026-01-29', 'EKSREK4', 'UNDANGAN PELANTIKAN REKTOR', '2026-01-26', 'AG04', 'Asli', 'eksternal', 'surat/H0qsuNNNjid16RN3FVxZo4wQVOt2llTvyYCbG0uJ.jpg', 'arsip rektor', 1, 'universitas', '2026-01-26 11:16:58', '2026-01-26 11:17:31', NULL, NULL, NULL),
(167, 'UNIVERSITAS BANYUWANGI', '2026-01-29', 'EKSREK5', 'UNDANGAN BEM', '2026-01-26', 'AG05', 'Asli', 'eksternal', 'surat/BSgX35sVjtbgBh41UZ1ZmhqxNH9Y8GhpjIIL0k5S.jpg', 'diarsipkan', 1, 'universitas', '2026-01-26 11:19:28', '2026-01-26 11:21:21', NULL, NULL, NULL),
(168, 'Dekan FEB', '2026-01-30', 'FEB5', 'UNDANGAN PELANTIKAN KAPRODI', '2026-01-26', 'AG06', 'Asli', 'internal', 'surat_keluar_internal_satker/ks6Oz3OFT7GGAKgq5cPdPcenYnArJUm6RbW4X4Gw.jpg', 'arsip rektor', 6, 'universitas', '2026-01-26 11:22:39', '2026-01-26 11:25:32', NULL, NULL, NULL),
(169, 'Dekan FEB', '2026-01-29', 'FEB6', 'UNDANGAN PEMATERI', '2026-01-26', 'PEGAWAI-69775038324FE', 'Asli', 'internal', 'surat_keluar_internal_satker/FNAyo40SOo0hMqWVIkPp9qHUnaGWLNPi0BxcJ225.jpg', 'proses', 6, 'pegawai', '2026-01-26 11:30:00', '2026-01-26 11:30:00', NULL, 5, NULL),
(170, 'ITS Surabaya', '2026-02-01', 'DNS01/Vii/2025', 'undangan', '2026-01-26', 'AG07', 'Asli', 'eksternal', 'surat/CjKeij99nKAQ34aWpJUPLeU7O59qUp2h5Kc5Zsjw.jpg', 'selesai', 1, 'universitas', '2026-01-26 12:16:54', '2026-01-26 12:19:02', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `surat_delegasi`
--

CREATE TABLE `surat_delegasi` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `surat_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `status` varchar(191) NOT NULL DEFAULT 'belum_dibaca',
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `surat_delegasi`
--

INSERT INTO `surat_delegasi` (`id`, `surat_id`, `user_id`, `status`, `catatan`, `created_at`, `updated_at`) VALUES
(46, 164, 8, 'belum_dibaca', 'PAKAI PDH', '2026-01-26 11:11:48', '2026-01-26 11:11:48'),
(47, 165, 8, 'belum_dibaca', 'Untuk diketahui dan dipelajari.', '2026-01-26 11:14:57', '2026-01-26 11:14:57'),
(48, 165, 13, 'belum_dibaca', 'Untuk diketahui dan dipelajari.', '2026-01-26 11:14:57', '2026-01-26 11:14:57'),
(49, 170, 8, 'belum_dibaca', 'asdfghhj', '2026-01-26 12:19:02', '2026-01-26 12:19:02'),
(50, 170, 13, 'belum_dibaca', 'asdfghhj', '2026-01-26 12:19:02', '2026-01-26 12:19:02');

-- --------------------------------------------------------

--
-- Table structure for table `surat_edaran_satker`
--

CREATE TABLE `surat_edaran_satker` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `surat_id` bigint(20) UNSIGNED NOT NULL,
  `satker_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('terkirim','diteruskan_internal') NOT NULL DEFAULT 'terkirim',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `surat_keluars`
--

CREATE TABLE `surat_keluars` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nomor_surat` varchar(191) NOT NULL,
  `tipe_kirim` varchar(191) NOT NULL DEFAULT 'eksternal',
  `tanggal_surat` date NOT NULL,
  `tujuan_surat` varchar(191) DEFAULT NULL,
  `tujuan_satker_id` bigint(20) UNSIGNED DEFAULT NULL,
  `perihal` text NOT NULL,
  `tujuan_luar` varchar(191) DEFAULT NULL,
  `via` varchar(191) DEFAULT NULL,
  `file_surat` varchar(191) NOT NULL,
  `status` varchar(191) DEFAULT 'Terkirim',
  `tanggal_terusan` datetime DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `surat_keluars`
--

INSERT INTO `surat_keluars` (`id`, `nomor_surat`, `tipe_kirim`, `tanggal_surat`, `tujuan_surat`, `tujuan_satker_id`, `perihal`, `tujuan_luar`, `via`, `file_surat`, `status`, `tanggal_terusan`, `user_id`, `created_at`, `updated_at`, `deleted_at`) VALUES
(233, 'FT02', 'internal', '2026-01-27', NULL, NULL, 'UNDANGAN FT2', NULL, NULL, 'surat_keluar_internal_satker/UEwuE9ugDnZtd3JqcgyCFda5zPoD6iwHMhH6BTdA.png', 'selesai', NULL, 3, '2026-01-26 10:07:16', '2026-01-26 10:17:12', NULL),
(234, 'FT01', 'internal', '2026-01-27', NULL, NULL, 'undangan ft2', NULL, NULL, 'surat_keluar_internal_satker/ti0j2xmouLEMlLJVw5NwF3aaBNjkrItGoXnsO0Fl.png', 'selesai', NULL, 3, '2026-01-26 10:18:09', '2026-01-26 10:19:13', NULL),
(236, 'FT03', 'internal', '2026-01-27', NULL, NULL, 'undangan ft3', NULL, NULL, 'surat_keluar_internal_satker/eHGuI4e51BveEkWbFlqC1H1xRaPcWcgmpJ6o0fvm.jpg', 'selesai', NULL, 3, '2026-01-26 10:23:10', '2026-01-26 10:23:42', NULL),
(237, 'FT04', 'internal', '2026-01-27', '1 Pegawai Spesifik', NULL, 'undangan ft4', NULL, NULL, 'surat_keluar_internal_satker/2m9vT8w79XWDeLiI5dRZHMLEbfddEUkxaB32tDtn.jpg', 'Terkirim', NULL, 3, '2026-01-26 10:25:02', '2026-01-26 10:25:02', NULL),
(238, 'FT05', 'internal', '2026-01-27', NULL, NULL, 'coba sebar', NULL, NULL, 'surat_keluar_internal_satker/N5RbpVaXKkWu9xzZP5aZHUyvMCNEadbPpafCGZog.png', 'selesai', NULL, 3, '2026-01-26 10:28:11', '2026-01-26 10:28:40', NULL),
(239, 'BAU01', 'internal', '2026-01-28', NULL, NULL, 'UNDANGAN BAU1', NULL, NULL, 'sk_bau_internaleks/b8ew4PWW7s0A1dPHdgsTV3WFtQPFuUEw6Hpzg5Hq.jpg', 'selesai', NULL, 1, '2026-01-26 10:34:42', '2026-01-26 10:35:16', NULL),
(240, 'BAU02', 'internal', '2026-01-28', NULL, NULL, 'Undangan BAU2', NULL, NULL, 'sk_bau_internaleks/ZYCfNQL5ZMbeIwDkuR20VIfCRLA2wOXkger2JihX.png', 'selesai', NULL, 1, '2026-01-26 10:40:37', '2026-01-26 10:41:08', NULL),
(241, 'BAU03', 'internal', '2026-01-26', NULL, NULL, 'Undangan BAU3', NULL, NULL, 'sk_bau_internaleks/Ph1TaaDwSpWBhnBRGPvpTUWaRR0CU6oDkQtNq83e.jpg', 'selesai', NULL, 1, '2026-01-26 10:42:06', '2026-01-26 10:42:34', NULL),
(242, 'BAU4', 'internal', '2026-01-28', NULL, NULL, 'UNDANGAN BAU4', NULL, NULL, 'sk_bau_internaleks/02aVGFESr4S5LJKd26Z4fKAD9BvxOhN3iquYfjT4.jpg', 'Terkirim', NULL, 1, '2026-01-26 10:43:46', '2026-01-26 10:43:46', NULL),
(243, 'FEB1', 'internal', '2026-01-29', NULL, NULL, 'undangan feb1', NULL, NULL, 'surat_keluar_internal_satker/iLsV0MaPLUH2DQ4jdiLowkVIztCdmCACKvuDrLlE.jpg', 'Selesai di BAU', NULL, 6, '2026-01-26 10:49:35', '2026-01-26 10:49:53', NULL),
(244, 'FEB2', 'internal', '2026-01-29', NULL, NULL, 'undangan feb2', NULL, NULL, 'surat_keluar_internal_satker/CtBhOmMYiIVGlAAJ7b3wJnttkZxNtVMzuXU5ycuu.jpg', 'Delegasi/Sebar', NULL, 6, '2026-01-26 10:50:26', '2026-01-26 10:51:25', NULL),
(245, 'FEB3', 'internal', '2026-01-29', NULL, NULL, 'undangan feb3', NULL, NULL, 'surat_keluar_internal_satker/gKqu5mh1Y4UdQ2Ls3KivB8gopLTn0MnIxVqf03KE.png', 'Delegasi/Sebar', NULL, 6, '2026-01-26 10:52:19', '2026-01-26 10:52:36', NULL),
(246, 'FEB4', 'internal', '2026-01-29', '1 Pegawai Spesifik', NULL, 'undangan bau4', NULL, NULL, 'surat_keluar_internal_satker/WAaCm2n56vgum7BIXLYfWCsEoEYBAw0odUJsVHzz.jpg', 'Terkirim', NULL, 6, '2026-01-26 10:59:40', '2026-01-26 10:59:40', NULL),
(247, 'FEB5', 'internal', '2026-01-30', 'Universitas (Via BAU)', NULL, 'UNDANGAN PELANTIKAN KAPRODI', NULL, NULL, 'surat_keluar_internal_satker/ks6Oz3OFT7GGAKgq5cPdPcenYnArJUm6RbW4X4Gw.jpg', 'Terkirim', NULL, 6, '2026-01-26 11:22:39', '2026-01-26 11:22:39', NULL),
(248, 'FEB6', 'internal', '2026-01-29', '1 Pegawai Spesifik', NULL, 'UNDANGAN PEMATERI', NULL, NULL, 'surat_keluar_internal_satker/FNAyo40SOo0hMqWVIkPp9qHUnaGWLNPi0BxcJ225.jpg', 'Terkirim', NULL, 6, '2026-01-26 11:30:00', '2026-01-26 11:30:00', NULL),
(249, 'REK01', 'internal', '2026-01-30', NULL, NULL, 'UNDANGAN REKTORAT1', NULL, NULL, 'sk_rektor_internal/4B2Yjsa1OxRnqXpcxNC0hAm5ML9KPxReEw1yo77H.jpg', 'selesai', '2026-01-26 18:36:06', 2, '2026-01-26 11:35:12', '2026-01-26 11:36:06', NULL),
(250, 'REK02', 'internal', '2026-01-30', NULL, NULL, 'UNDANGAN REK02', NULL, NULL, 'sk_rektor_internal/m02FLeMfwcYkNzAa5XcyLqAWmxDTmkcKCIQHqF5G.jpg', 'selesai', '2026-01-26 18:42:00', 2, '2026-01-26 11:40:13', '2026-01-26 11:42:00', NULL),
(251, 'REK03', 'internal', '2026-01-30', NULL, NULL, 'UNDANGAN REK3', NULL, NULL, 'sk_rektor_internal/PdxFSn1LuwQSDV4WcCWuDNw2jNMZAlj1CsXdeME4.jpg', 'selesai', '2026-01-26 19:00:16', 2, '2026-01-26 11:59:55', '2026-01-26 12:00:16', NULL),
(252, 'BAU5', 'internal', '2026-01-31', NULL, NULL, 'Undangan BAU11', NULL, NULL, 'sk_bau_internaleks/zvLUzlm51hKf5yDY5F8BSAsaGJz42MWtTBr4nJt5.jpg', 'selesai', NULL, 1, '2026-01-26 12:06:06', '2026-01-26 12:12:44', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `surat_keluar_internal_penerima`
--

CREATE TABLE `surat_keluar_internal_penerima` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `surat_keluar_id` bigint(20) UNSIGNED NOT NULL,
  `satker_id` bigint(20) UNSIGNED NOT NULL,
  `is_read` tinyint(4) NOT NULL DEFAULT 0,
  `dibaca_pada` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `surat_keluar_internal_penerima`
--

INSERT INTO `surat_keluar_internal_penerima` (`id`, `surat_keluar_id`, `satker_id`, `is_read`, `dibaca_pada`, `created_at`, `updated_at`) VALUES
(176, 233, 10, 2, NULL, '2026-01-26 10:07:16', '2026-01-26 10:17:12'),
(177, 234, 10, 2, NULL, '2026-01-26 10:18:09', '2026-01-26 10:19:13'),
(179, 236, 10, 2, NULL, '2026-01-26 10:23:10', '2026-01-26 10:23:42'),
(180, 238, 10, 2, NULL, '2026-01-26 10:28:11', '2026-01-26 10:28:40'),
(181, 239, 10, 2, NULL, '2026-01-26 10:34:42', '2026-01-26 10:35:16'),
(182, 240, 10, 2, NULL, '2026-01-26 10:40:37', '2026-01-26 10:41:08'),
(183, 241, 10, 2, NULL, '2026-01-26 10:42:06', '2026-01-26 10:42:34'),
(184, 243, 34, 2, NULL, '2026-01-26 10:49:35', '2026-01-26 10:49:53'),
(185, 244, 34, 2, NULL, '2026-01-26 10:50:26', '2026-01-26 10:51:25'),
(186, 245, 34, 2, NULL, '2026-01-26 10:52:19', '2026-01-26 10:52:36'),
(187, 249, 16, 0, NULL, '2026-01-26 11:35:12', '2026-01-26 11:35:12'),
(188, 250, 10, 2, NULL, '2026-01-26 11:40:13', '2026-01-26 11:52:49'),
(189, 251, 10, 2, NULL, '2026-01-26 11:59:55', '2026-01-26 12:00:41'),
(190, 252, 10, 2, NULL, '2026-01-26 12:06:06', '2026-01-26 12:12:44');

-- --------------------------------------------------------

--
-- Table structure for table `surat_keluar_rektor_tujuan`
--

CREATE TABLE `surat_keluar_rektor_tujuan` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `surat_keluar_id` bigint(20) UNSIGNED NOT NULL,
  `satker_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `email` varchar(191) NOT NULL,
  `email2` varchar(191) DEFAULT NULL,
  `no_hp` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(191) NOT NULL,
  `role` enum('bau','admin_rektor','satker','pegawai') NOT NULL DEFAULT 'satker',
  `satker_id` bigint(20) UNSIGNED DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email2`, `no_hp`, `email_verified_at`, `password`, `role`, `satker_id`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Admin BAU', 'ayyubalfredo@gmail.com', NULL, '6281234499433,087754768652', '2025-12-08 18:05:32', '$2y$12$bHiI1CbDQVWySl73XhJubuF2n.07c8BH/hBIKQyKAdDboEaaWEl9y', 'bau', 34, '0zBLzsiaXkllGdVbce3Jc9jXsx5A4ZbvvpcfU2HLPJDaaxZy0hZE2VFKdflY', '2025-12-08 18:05:33', '2025-12-20 21:55:55'),
(2, 'Admin Rektor', 'ayyub.24@mhs.istts.ac.id', NULL, '6281234499433,087754768652', '2025-12-08 18:05:33', '$2y$12$Qvyepn0aVSl7At6XU2ZdCeP/hWx1eYZbV9iJKQz2t60CoBOHkzJUe', 'admin_rektor', NULL, 'kP0aMDq3sWEsegPCgb78GAqV12FXtQN5ggApr4vGxfYy79Wc449GOzVBoYtB', '2025-12-08 18:05:33', '2025-12-22 14:25:17'),
(3, 'Admin FT', 'AW819@gmail.com', NULL, '6281234499433,087754768652', '2025-12-08 18:05:33', '$2y$12$B1eXx2QAAjurfilvN5qlOuuzF4PgMM.m1U9ynoxxzowBmBJeZe/ke', 'satker', 16, 'uUJUs0aYWP9R1fE3RAfTJzOe920Bi0fGa0crz3WoPYVZKfAc4EjIED0y0rir', '2025-12-08 18:05:33', '2026-01-26 11:37:45'),
(4, 'Bapak Johan (Pegawai)', 'pegawai@example.com', NULL, NULL, '2025-12-08 18:05:34', '$2y$12$0QJz5BEF7k/a43ufg3Q0peUUGsdK4Zc42AlUg7vB2Zr6cnUB8Vvna', 'pegawai', 16, 'Jv9FmoKqN4rCO0ziVWgeQ10r5d460drexpAfjad4fuPeOEP5iwZyfWWSQ6lV', '2025-12-08 18:05:34', '2025-12-08 18:05:34'),
(5, 'Bapak Iddrus', 'ay@wiraraja.ac.id', NULL, NULL, NULL, '$2y$12$GAZofqURNhkRXkSP1WwYR.wtbNlhqcJO4HIKQ6x9siH6DDZozsvzm', 'pegawai', 16, NULL, '2025-12-08 20:42:08', '2026-01-26 11:39:07'),
(6, 'Satker FEB', 'awulawul819@gmail.com', NULL, '6281234499433,087754768652', NULL, '$2y$12$sZNRw7TNjo84mub6IlhKt.YOSwpN3R6xdNFLS3Yi22IZfxG/TbrTS', 'satker', 10, NULL, '2025-12-09 00:06:57', '2026-01-26 11:38:23'),
(8, 'Muhammad Gazali', 'gazali@wiraraja.ac.id', NULL, NULL, NULL, '$2y$12$hBQc7O6JAGJBWEu9s8dsyOpmvI6ejnaX..7AbFQgtOYJt1jY8Dki2', 'pegawai', 10, NULL, '2025-12-09 03:52:47', '2026-01-26 11:33:44'),
(10, 'Admin TU Fakultas Hukum', 'fh@gmail.com', NULL, '8123456001', NULL, '$2y$12$aOwG5KKRptjtHKhY2haHxuWDPYQ9JD4FFFqUOyfA0VNQIX2TJUshy', 'satker', 7, NULL, '2026-01-05 11:59:44', '2026-01-05 11:59:44'),
(11, 'Kepala BAPSI', 'bapsi@gmail.com', NULL, '8123456002', NULL, '$2y$12$VLhAj96fplU2AfV8irtRm.wAQ8yf/DkSXE2zdE.F8gwM2bwb4sxjy', 'satker', 36, NULL, '2026-01-05 11:59:44', '2026-01-05 12:00:44'),
(12, 'Pegawai BAU', 'ayyub@wiraraja.ac.id', NULL, '034590359', NULL, '$2y$12$OYcNX0NBq/OT8HFZBqA5QOXzl/hJeRo29KhpEZyxJtTHMkm4Uh4my', 'pegawai', 34, NULL, '2026-01-07 11:32:00', '2026-01-26 11:39:16'),
(13, 'Abdillah', 'argapradipta867@gmail.com', NULL, '087754768652', NULL, '$2y$12$hzBu2riNxxOlgEyJqsQMgedTyhMb/zkHGP3gnGMP5fh.QF47wujYO', 'pegawai', 10, NULL, '2026-01-19 12:17:33', '2026-01-19 12:17:33'),
(14, 'Ayyub', 'bau2@gmail.com', NULL, '087754768652', NULL, '$2y$12$dNR1CK2rWnLd8iNfFVWVd.1FQrMpja4C3z7EmRRbEpeD1zxY2OCqO', 'pegawai', 34, NULL, '2026-01-25 08:51:15', '2026-01-26 04:34:04');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `disposisis`
--
ALTER TABLE `disposisis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `disposisis_surat_id_foreign` (`surat_id`),
  ADD KEY `disposisis_user_id_foreign` (`user_id`),
  ADD KEY `disposisis_klasifikasi_id_foreign` (`klasifikasi_id`),
  ADD KEY `disposisis_tujuan_satker_id_foreign` (`tujuan_satker_id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `klasifikasis`
--
ALTER TABLE `klasifikasis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `klasifikasis_kode_klasifikasi_unique` (`kode_klasifikasi`);

--
-- Indexes for table `klasifikasi_surat`
--
ALTER TABLE `klasifikasi_surat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `klasifikasi_surat_surat_id_foreign` (`surat_id`),
  ADD KEY `klasifikasi_surat_klasifikasi_id_foreign` (`klasifikasi_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `riwayat_surats`
--
ALTER TABLE `riwayat_surats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `riwayat_surats_surat_id_foreign` (`surat_id`),
  ADD KEY `riwayat_surats_surat_keluar_id_foreign` (`surat_keluar_id`),
  ADD KEY `riwayat_surats_penerima_id_foreign` (`penerima_id`);

--
-- Indexes for table `satkers`
--
ALTER TABLE `satkers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `surats`
--
ALTER TABLE `surats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `surats_no_agenda_unique` (`no_agenda`),
  ADD KEY `surats_user_id_foreign` (`user_id`),
  ADD KEY `surats_tujuan_satker_id_foreign` (`tujuan_satker_id`),
  ADD KEY `surats_tujuan_user_id_foreign` (`tujuan_user_id`);

--
-- Indexes for table `surat_delegasi`
--
ALTER TABLE `surat_delegasi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `surat_delegasi_surat_id_foreign` (`surat_id`),
  ADD KEY `surat_delegasi_user_id_foreign` (`user_id`);

--
-- Indexes for table `surat_edaran_satker`
--
ALTER TABLE `surat_edaran_satker`
  ADD PRIMARY KEY (`id`),
  ADD KEY `surat_edaran_satker_surat_id_foreign` (`surat_id`),
  ADD KEY `surat_edaran_satker_satker_id_foreign` (`satker_id`);

--
-- Indexes for table `surat_keluars`
--
ALTER TABLE `surat_keluars`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `surat_keluars_nomor_surat_unique` (`nomor_surat`),
  ADD KEY `surat_keluars_user_id_foreign` (`user_id`),
  ADD KEY `surat_keluars_tujuan_satker_id_foreign` (`tujuan_satker_id`);

--
-- Indexes for table `surat_keluar_internal_penerima`
--
ALTER TABLE `surat_keluar_internal_penerima`
  ADD PRIMARY KEY (`id`),
  ADD KEY `surat_keluar_internal_penerima_surat_keluar_id_foreign` (`surat_keluar_id`),
  ADD KEY `surat_keluar_internal_penerima_satker_id_foreign` (`satker_id`);

--
-- Indexes for table `surat_keluar_rektor_tujuan`
--
ALTER TABLE `surat_keluar_rektor_tujuan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `surat_keluar_rektor_tujuan_surat_keluar_id_foreign` (`surat_keluar_id`),
  ADD KEY `surat_keluar_rektor_tujuan_satker_id_foreign` (`satker_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD KEY `users_satker_id_foreign` (`satker_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `disposisis`
--
ALTER TABLE `disposisis`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `klasifikasis`
--
ALTER TABLE `klasifikasis`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `klasifikasi_surat`
--
ALTER TABLE `klasifikasi_surat`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `riwayat_surats`
--
ALTER TABLE `riwayat_surats`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=475;

--
-- AUTO_INCREMENT for table `satkers`
--
ALTER TABLE `satkers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `surats`
--
ALTER TABLE `surats`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=171;

--
-- AUTO_INCREMENT for table `surat_delegasi`
--
ALTER TABLE `surat_delegasi`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `surat_edaran_satker`
--
ALTER TABLE `surat_edaran_satker`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `surat_keluars`
--
ALTER TABLE `surat_keluars`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=253;

--
-- AUTO_INCREMENT for table `surat_keluar_internal_penerima`
--
ALTER TABLE `surat_keluar_internal_penerima`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=191;

--
-- AUTO_INCREMENT for table `surat_keluar_rektor_tujuan`
--
ALTER TABLE `surat_keluar_rektor_tujuan`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `disposisis`
--
ALTER TABLE `disposisis`
  ADD CONSTRAINT `disposisis_klasifikasi_id_foreign` FOREIGN KEY (`klasifikasi_id`) REFERENCES `klasifikasis` (`id`),
  ADD CONSTRAINT `disposisis_surat_id_foreign` FOREIGN KEY (`surat_id`) REFERENCES `surats` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `disposisis_tujuan_satker_id_foreign` FOREIGN KEY (`tujuan_satker_id`) REFERENCES `satkers` (`id`),
  ADD CONSTRAINT `disposisis_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `klasifikasi_surat`
--
ALTER TABLE `klasifikasi_surat`
  ADD CONSTRAINT `klasifikasi_surat_klasifikasi_id_foreign` FOREIGN KEY (`klasifikasi_id`) REFERENCES `klasifikasis` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `klasifikasi_surat_surat_id_foreign` FOREIGN KEY (`surat_id`) REFERENCES `surats` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `riwayat_surats`
--
ALTER TABLE `riwayat_surats`
  ADD CONSTRAINT `riwayat_surats_penerima_id_foreign` FOREIGN KEY (`penerima_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `riwayat_surats_surat_id_foreign` FOREIGN KEY (`surat_id`) REFERENCES `surats` (`id`),
  ADD CONSTRAINT `riwayat_surats_surat_keluar_id_foreign` FOREIGN KEY (`surat_keluar_id`) REFERENCES `surat_keluars` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `surats`
--
ALTER TABLE `surats`
  ADD CONSTRAINT `surats_tujuan_satker_id_foreign` FOREIGN KEY (`tujuan_satker_id`) REFERENCES `satkers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `surats_tujuan_user_id_foreign` FOREIGN KEY (`tujuan_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `surats_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `surat_delegasi`
--
ALTER TABLE `surat_delegasi`
  ADD CONSTRAINT `surat_delegasi_surat_id_foreign` FOREIGN KEY (`surat_id`) REFERENCES `surats` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `surat_delegasi_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `surat_edaran_satker`
--
ALTER TABLE `surat_edaran_satker`
  ADD CONSTRAINT `surat_edaran_satker_satker_id_foreign` FOREIGN KEY (`satker_id`) REFERENCES `satkers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `surat_edaran_satker_surat_id_foreign` FOREIGN KEY (`surat_id`) REFERENCES `surats` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `surat_keluars`
--
ALTER TABLE `surat_keluars`
  ADD CONSTRAINT `surat_keluars_tujuan_satker_id_foreign` FOREIGN KEY (`tujuan_satker_id`) REFERENCES `satkers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `surat_keluars_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `surat_keluar_internal_penerima`
--
ALTER TABLE `surat_keluar_internal_penerima`
  ADD CONSTRAINT `surat_keluar_internal_penerima_satker_id_foreign` FOREIGN KEY (`satker_id`) REFERENCES `satkers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `surat_keluar_internal_penerima_surat_keluar_id_foreign` FOREIGN KEY (`surat_keluar_id`) REFERENCES `surat_keluars` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `surat_keluar_rektor_tujuan`
--
ALTER TABLE `surat_keluar_rektor_tujuan`
  ADD CONSTRAINT `surat_keluar_rektor_tujuan_satker_id_foreign` FOREIGN KEY (`satker_id`) REFERENCES `satkers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `surat_keluar_rektor_tujuan_surat_keluar_id_foreign` FOREIGN KEY (`surat_keluar_id`) REFERENCES `surat_keluars` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_satker_id_foreign` FOREIGN KEY (`satker_id`) REFERENCES `satkers` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
