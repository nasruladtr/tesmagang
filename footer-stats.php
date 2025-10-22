<?php
// File: footer-stats.php

// Catatan: Data statistik pengunjung/download di bawah ini adalah MOCKUP. 
// Gunakan logika PHP/Database nyata untuk mengisinya.

$total_reload = "1,171";
$total_unique_ip = "283";
$total_downloads = "179";
$excel_count = "90";
$pdf_count = "89";
?>

<div class="container py-5">
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card p-3 bg-dark text-white">
                <h5 class="mb-3"><i class="fas fa-users me-2"></i> Statistik Pengunjung</h5>
                <p class="mb-0">Total Reload: <span class="fw-bold"><?php echo $total_reload; ?></span></p>
                <p class="mb-0 small">Pengunjung Unik by IP Address: <?php echo $total_unique_ip; ?></p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card p-3 bg-dark text-white">
                <h5 class="mb-3"><i class="fas fa-download me-2"></i> Statistik Download</h5>
                <p class="mb-0">Total: <span class="fw-bold"><?php echo $total_downloads; ?></span></p>
                <p class="mb-0 small">Excel: <?php echo $excel_count; ?> | PDF: <?php echo $pdf_count; ?></p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card p-3 bg-dark text-white">
                <h5 class="mb-3"><i class="fas fa-star me-2"></i> Kategori Populer</h5>
                <ol class="small mb-0 ps-3">
                    <li>Jumlah Penduduk (58)</li>
                    <li>Kepala Keluarga (28)</li>
                    <li>Umur Tunggal (26)</li>
                </ol>
            </div>
        </div>
    </div>
</div>