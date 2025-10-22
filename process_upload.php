<?php
require 'vendor/autoload.php';
require 'db.php'; // koneksi database

use PhpOffice\PhpSpreadsheet\IOFactory;

if (isset($_FILES['file_excel']['name'])) {
    $file = $_FILES['file_excel']['tmp_name'];
    $uploadDir = "uploads/";

    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $filePath = $uploadDir . basename($_FILES['file_excel']['name']);
    if (move_uploaded_file($file, $filePath)) {

        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();
        
        $jumlahKolomData = 10; // Harus ada 10 kolom data

        // lewati baris pertama (header Excel)
        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];

            // pastikan jumlah kolom sesuai (Minimal harus sama dengan $jumlahKolomData)
            if (count($row) < $jumlahKolomData) continue;

            // urutan kolom disesuaikan dengan Excel kamu (0-9)
            $nama             = trim($row[0] ?? '');
            $jenis_kelamin    = trim($row[1] ?? '');
            $tempat_tgl_lahir = trim($row[2] ?? '');
            $alamat           = trim($row[3] ?? '');
            $agama            = trim($row[4] ?? '');
            $status_kawin     = trim($row[5] ?? '');
            $pekerjaan        = trim($row[6] ?? '');
            $pendidikan       = trim($row[7] ?? '');
            $kecamatan        = trim($row[8] ?? '');
            $kabupaten        = trim($row[9] ?? '');

            // simpan ke database
            // PASTIKAN KOLOM DI SINI SESUAI DENGAN db.php yang sudah diperbaiki!
            $stmt = $conn->prepare("
                INSERT INTO data_penduduk
                (nama, jenis_kelamin, tempat_tgl_lahir, alamat, agama, status_kawin, pekerjaan, pendidikan, kecamatan, kabupaten)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("ssssssssss", 
                $nama, $jenis_kelamin, $tempat_tgl_lahir, $alamat, $agama, 
                $status_kawin, $pekerjaan, $pendidikan, $kecamatan, $kabupaten
            );
            $stmt->execute();
        }

        echo "<h3>✅ Data berhasil diimport ke database dengan urutan yang benar!</h3>";
        
        // Hapus file Excel setelah diproses
        unlink($filePath);

    } else {
        echo "❌ Gagal mengupload file.";
    }
} else {
    echo "Tidak ada file yang diupload.";
}
?>