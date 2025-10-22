<?php
// File: db.php
// Panggil file koneksi
require_once 'db_koneksi.php'; 

// Cek apakah koneksi berhasil
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Skema disesuaikan dengan SQL Dump yang menggunakan ID AUTO_INCREMENT
$conn->query("
CREATE TABLE IF NOT EXISTS data_penduduk (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, 
    nama VARCHAR(100) DEFAULT NULL,
    jenis_kelamin VARCHAR(20) DEFAULT NULL,
    tempat_tgl_lahir VARCHAR(100) DEFAULT NULL,
    alamat TEXT DEFAULT NULL,
    agama VARCHAR(50) DEFAULT NULL,
    status_kawin VARCHAR(50) DEFAULT NULL,
    pekerjaan VARCHAR(50) DEFAULT NULL,
    pendidikan VARCHAR(50) DEFAULT NULL,
    kecamatan VARCHAR(50) DEFAULT NULL,
    kabupaten VARCHAR(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");
?>