<?php
require 'vendor/autoload.php';
require 'db_koneksi.php'; // koneksi database

use PhpOffice\PhpSpreadsheet\IOFactory;

$debugMode = false; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excelFile']['name'])) {
    
    $file = $_FILES['excelFile']['tmp_name'];
    $uploadDir = "uploads/";

    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $filePath = $uploadDir . basename($_FILES['excelFile']['name']);
    
    if (!move_uploaded_file($file, $filePath)) {
        header("Location: upload.php?error=upload_failed");
        exit;
    }
    
    try {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        
        $rows = $sheet->toArray(null, true, true, true); 
        
        $headers = array_map('trim', array_shift($rows));

        // 1. Kosongkan tabel sebelum import data baru
        $conn->query("TRUNCATE TABLE data_penduduk"); 

        // 2. Siapkan statement INSERT (HANYA 10 kolom data)
        $stmt = $conn->prepare("
            INSERT INTO data_penduduk
            (nama, jenis_kelamin, tempat_tgl_lahir, alamat, agama, status_kawin, pekerjaan, pendidikan, kecamatan, kabupaten)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssssssssss", $nama, $jenis_kelamin, $tempat_tgl_lahir, $alamat, $agama, $status_kawin, $pekerjaan, $pendidikan, $kecamatan, $kabupaten);

        // 3. Loop data dan insert
        foreach ($rows as $row) {
             if (empty(array_filter($row))) continue; 

             // Kolom A (NIK) diabaikan. Mapping dimulai dari Kolom B ke 'nama'.
             $nama             = trim($row['B'] ?? ''); 
             $jenis_kelamin    = trim($row['C'] ?? '');
             $tempat_tgl_lahir = trim($row['D'] ?? '');
             $alamat           = trim($row['E'] ?? ''); 
             $agama            = trim($row['F'] ?? ''); 
             $status_kawin     = trim($row['G'] ?? '');
             $pekerjaan        = trim($row['H'] ?? ''); 
             $pendidikan       = trim($row['I'] ?? ''); 
             $kecamatan        = trim($row['J'] ?? ''); 
             $kabupaten        = trim($row['K'] ?? ''); 
            
             if (!empty($nama)) {
                 $stmt->execute();
             }
        }

        $stmt->close();
        unlink($filePath);
        // PENTING: Redirect ke index.php
        header("Location: index.php?upload=success");
        exit;
        
    } catch (\Exception $e) {
        if (file_exists($filePath)) unlink($filePath);
        header("Location: upload.php?error=process_failed");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Upload Data Excel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .navbar { background-color: #2563eb; }
        .navbar-brand { color: white !important; font-weight: 700; }
        .card { border: none; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    </style>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="index.php">📊 Kembali ke Dashboard</a>
    </div>
</nav>

<div class="container py-5">
    <div class="card shadow-lg p-4 mx-auto" style="max-width: 500px;">
        <h3 class="mb-4 text-center text-primary">Import File Data Penduduk</h3>
        
        <?php if (isset($_GET['error']) && $_GET['error'] === 'upload_failed'): ?>
            <div class="alert alert-danger">❌ Gagal mengunggah file. Pastikan izin direktori 'uploads/' sudah benar (0777).</div>
        <?php elseif (isset($_GET['error']) && $_GET['error'] === 'process_failed'): ?>
            <div class="alert alert-danger">❌ Gagal memproses file. Periksa format kolom Excel atau koneksi database.</div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="excelFile" class="form-label text-muted">Pilih File Excel (.xlsx, .xls)</label>
                <input class="form-control" type="file" id="excelFile" name="excelFile" accept=".xlsx, .xls" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 fw-bold">Import ke Database</button>
        </form>

        <p class="mt-4 text-muted text-center small">⚠️ Mengimport data akan **menghapus** data lama di database.</p>
    </div>
</div>
</body>
</html>