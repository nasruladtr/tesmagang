<?php
// File: index.php
require_once 'db_koneksi.php'; 
require_once 'db.php'; 

// --- Ambil OPTIONS untuk dropdown DAN untuk Header Tabel ---
$options = [
    'jenis_kelamin' => $conn->query("SELECT DISTINCT jenis_kelamin FROM data_penduduk WHERE jenis_kelamin IS NOT NULL AND jenis_kelamin != '' ORDER BY jenis_kelamin")->fetch_all(MYSQLI_ASSOC),
    'agama' => $conn->query("SELECT DISTINCT agama FROM data_penduduk WHERE agama IS NOT NULL AND agama != '' ORDER BY agama")->fetch_all(MYSQLI_ASSOC),
    'pekerjaan' => $conn->query("SELECT DISTINCT pekerjaan FROM data_penduduk WHERE pekerjaan IS NOT NULL AND pekerjaan != '' ORDER BY pekerjaan")->fetch_all(MYSQLI_ASSOC),
    'status_kawin' => $conn->query("SELECT DISTINCT status_kawin FROM data_penduduk WHERE status_kawin IS NOT NULL AND status_kawin != '' ORDER BY status_kawin")->fetch_all(MYSQLI_ASSOC)
];

// Dapatkan daftar unik untuk kriteria dinamis
$unique_agama = array_map(fn($item) => trim($item['agama']), $options['agama']);
$unique_pekerjaan = array_map(fn($item) => trim($item['pekerjaan']), $options['pekerjaan']);
$unique_status_kawin = array_map(fn($item) => trim($item['status_kawin']), $options['status_kawin']);

// Headers Dasar
$headers_agregat_base = ['WILAYAH', 'TOTAL PENDUDUK', 'LAKI-LAKI', 'PEREMPUAN'];

// Headers Tambahan untuk Agama, Pekerjaan, dan Status Kawin (Untuk keperluan DataTables Column Definition)
$headers_agregat_dynamic = array_merge(
    array_map(fn($a) => "AGAMA: " . $a, $unique_agama),
    array_map(fn($p) => "PEKERJAAN: " . $p, $unique_pekerjaan),
    array_map(fn($s) => "STATUS: " . $s, $unique_status_kawin)
);

// Gabungkan semua header
$headers_agregat = array_merge($headers_agregat_base, $headers_agregat_dynamic);

// Data unik Kriteria Dinamis untuk digunakan di JS (dibersihkan dari string kosong)
$dynamic_criteria = [
    'agama' => array_filter($unique_agama),
    'pekerjaan' => array_filter($unique_pekerjaan),
    'status_kawin' => array_filter($unique_status_kawin), 
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Sistem Informasi Data Agregat Kependudukan</title>
    <meta name="viewport" content="width=device-width, initial-scale=1"> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* --- SKEMA WARNA DAN FONT UTAMA --- */
        body { background-color: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .navbar { background-color: #0c436b; }
        .header-top { background-color: #1a608b; color: white; padding: 10px 0; }
        .navbar-brand { color: #f5f5f5 !important; font-weight: 700; }
        
        /* TOMBOL UTAMA */
        .btn-primary { background-color: #1976d2; border-color: #1976d2; }
        .btn-reset { background-color: #607d8b; border-color: #607d8b; color: white; font-weight: 600; }
        
        /* Tombol Aksi */
        .btn-excel { background-color: #1abc9c; border-color: #1abc9c; color: white; }
        .btn-pdf { background-color: #e74c3c; border-color: #e74c3c; color: white; }
        .btn-diagram { background-color: #2ecc71; border-color: #2ecc71; color: white; }

        /* Filter Area */
        .card { border: 1px solid #e0e0e0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); height: 100%; }
        .filter-container { background-color: white; padding: 20px; border-radius: 8px; margin-bottom: 25px; border: 1px solid #ddd; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .filter-header { color: #1976d2; font-weight: 600; margin-bottom: 5px; }
        
        /* Chart Area */
        .chart-scroll-container { display: flex; flex-wrap: nowrap; overflow-x: auto; padding-bottom: 15px; scroll-snap-type: x mandatory; }
        .chart-item { flex: 0 0 350px; max-width: 400px; margin-right: 15px; scroll-snap-align: start; }
        .chart-canvas-container { height: 280px; }

        /* Kustomisasi Lebar Tombol Reset/Tampilkan */
        #resetFilterBtn, #applyFilterBtn {
            min-width: 95px !important; 
        }
        
        /* --- STYLING KHUSUS UNTUK TABEL AGREGAT (Font dan Padding Kecil) --- */
        #dataTableAgregat {
            font-size: 0.75rem; /* Ukuran font tabel diperkecil */
        }
        #dataTableAgregat th, #dataTableAgregat td {
            padding: 0.35rem 0.5rem !important; /* Padding diperkecil */
            white-space: nowrap; /* Mencegah header memotong baris */
            vertical-align: middle;
        }
        #dataTableAgregat thead th {
            font-weight: bold;
            background-color: #e9ecef; /* Warna latar belakang header */
            text-transform: uppercase;
            border-bottom: 2px solid #dee2e6 !important;
        }
    </style>
</head>
<body>

<div class="header-top">
    <div class="container py-2">
    </div>
</div>

<nav class="navbar shadow-sm">
    <div class="container">
    </div>
</nav>
<div class="container py-5">

    <div class="filter-container">
        <div class="filter-header mb-3 text-center"><i class="fas fa-filter me-2"></i> Filter Data Kependudukan</div>
        <p class="small text-muted mb-4 text-center">Pilih kriteria untuk menampilkan data</p>
        
        <form id="filterForm" class="row g-2 align-items-center justify-content-center">
            
            <div class="col-6 col-md-2">
                <select id="gender" name="gender" class="form-select form-select-sm">
                    <option value="Semua">Jenis Kelamin</option>
                    <?php foreach ($options['jenis_kelamin'] as $opt): ?>
                        <option value="<?= htmlspecialchars($opt['jenis_kelamin']) ?>">
                            <?= htmlspecialchars($opt['jenis_kelamin']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-6 col-md-2">
                <select id="agama" name="agama" class="form-select form-select-sm">
                    <option value="Semua">Agama</option>
                    <?php foreach ($options['agama'] as $opt): ?>
                        <option value="<?= htmlspecialchars($opt['agama']) ?>">
                            <?= htmlspecialchars($opt['agama']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <select id="pekerjaan" name="pekerjaan" class="form-select form-select-sm">
                    <option value="Semua">Pekerjaan</option>
                    <?php foreach ($options['pekerjaan'] as $opt): ?>
                        <option value="<?= htmlspecialchars($opt['pekerjaan']) ?>">
                            <?= htmlspecialchars($opt['pekerjaan']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-6 col-md-4">
                 <input type="text" id="search" name="search" class="form-control form-control-sm" placeholder="Nama, Alamat, atau Kecamatan">
            </div>

            <div class="col-6 col-md-1 d-grid px-1">
                <button type="button" id="resetFilterBtn" class="btn btn-reset btn-sm"><i class="fas fa-redo"></i> Reset</button>
            </div>

            <div class="col-6 col-md-1 d-grid px-1">
                <button type="button" id="applyFilterBtn" class="btn btn-primary btn-sm w-100"><i class="fas fa-search"></i> Tampilkan</button>
            </div>
        </form>
    </div>
    
    <div class="row g-3 mb-4 justify-content-center">
        <div class="col-12 col-md-8 d-flex flex-wrap justify-content-center">
            <button class="btn btn-excel btn-action me-2 mb-2 mb-md-0"><i class="fas fa-file-excel me-1"></i> Download Excel</button>
            <button class="btn btn-pdf btn-action me-2 mb-2 mb-md-0"><i class="fas fa-file-pdf me-1"></i> Download PDF</button>
            <button class="btn btn-diagram btn-action me-2 mb-2 mb-md-0"><i class="fas fa-chart-pie me-1"></i> Diagram Pie</button>
            <button class="btn btn-diagram btn-action mb-2 mb-md-0"><i class="fas fa-chart-bar me-1"></i> Diagram Batang</button>
        </div>
    </div>
    
    <div class="row g-4 mb-5">
        <div class="col-6 col-md-3">
            <div class="card stat-card p-4 text-center bg-primary text-white">
                <h5>Total Data (Saat Ini)</h5>
                <h3 id="totalDataCount" class="fw-bold">0</h3>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card p-4 text-center bg-success text-white">
                <h5>Jenis Kelamin (Kategori)</h5>
                <h3 id="genderCategoryCount" class="fw-bold">0</h3>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card p-4 text-center bg-warning text-white">
                <h5>Agama (Kategori)</h5>
                <h3 id="agamaCategoryCount" class="fw-bold">0</h3>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card p-4 text-center bg-info text-white">
                <h5>Pekerjaan (Kategori)</h5>
                <h3 id="pekerjaanCategoryCount" class="fw-bold">0</h3>
            </div>
        </div>
    </div>

    <div class="mb-5">
        <h4 class="fw-bold mb-3 text-primary">Distribusi Data (Geser untuk Melihat Semua)</h4>
        <div class="chart-scroll-container">
        
            <div class="chart-item">
                <div class="card p-3">
                    <h6 class="fw-bold text-center text-primary mb-3">Distribusi Jenis Kelamin</h6>
                    <div class="chart-canvas-container">
                        <canvas id="genderChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="chart-item">
                <div class="card p-3">
                    <h6 class="fw-bold text-center text-success mb-3">Distribusi Agama</h6>
                    <div class="chart-canvas-container">
                        <canvas id="agamaChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="chart-item">
                <div class="card p-3">
                    <h6 class="fw-bold text-center text-warning mb-3">Status Perkawinan</h6>
                    <div class="chart-canvas-container">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="chart-item">
                <div class="card p-3">
                    <h6 class="fw-bold text-center text-info-custom mb-3">Distribusi Pekerjaan</h6>
                    <div class="chart-canvas-container">
                        <canvas id="pekerjaanChart"></canvas>
                    </div>
                </div>
            </div>

        </div>
    </div>
    
    <div class="card p-4">
        <h5 class="fw-bold mb-4 text-primary">📋 Hasil Data Agregat Per Kecamatan</h5>
        <div class="table-responsive">
            <table id="dataTableAgregat" class="table table-striped table-hover table-custom align-middle w-100">
                <thead>
                    <tr id="header-single">
                        </tr>
                </thead>
                <tbody>
                    </tbody>
            </table>
        </div>
    </div>
</div>


<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Variabel global
let genderChartInstance, agamaChartInstance, statusChartInstance, pekerjaanChartInstance;
let dataTableAgregatInstance; 

// ----------------------------------------------------
// 1. DATA PROCESSING (Agregasi Rinci di Frontend)
// ----------------------------------------------------

function aggregateData(dataArray) {
    const aggregateMap = {};
    
    const dynamicCriteria = <?= json_encode($dynamic_criteria) ?>;
    const uniqueAgama = dynamicCriteria.agama;
    const uniquePekerjaan = dynamicCriteria.pekerjaan;
    const uniqueStatusKawin = dynamicCriteria.status_kawin;
    
    const allUniqueCriteria = [
        ...uniqueAgama, 
        ...uniquePekerjaan,
        ...uniqueStatusKawin
    ];

    dataArray.forEach(item => {
        const wilayah = item.kecamatan || 'Tidak Diketahui';
        const jk = item.jenis_kelamin ? item.jenis_kelamin.toUpperCase().trim() : '';
        const agama = item.agama ? item.agama.trim() : '';
        const pekerjaan = item.pekerjaan ? item.pekerjaan.trim() : '';
        const status_kawin = item.status_kawin ? item.status_kawin.trim() : '';
        
        if (!aggregateMap[wilayah]) {
            aggregateMap[wilayah] = {
                WILAYAH: wilayah,
                L: 0,
                P: 0,
                TOTAL: 0,
                dynamic: {}
            };
            allUniqueCriteria.forEach(criteria => {
                 aggregateMap[wilayah].dynamic[criteria] = 0;
            });
        }
        
        // 1. Hitungan Dasar (L/P/TOTAL)
        if (jk.includes('LAKI')) {
            aggregateMap[wilayah].L += 1;
        } else if (jk.includes('PEREMPUAN')) {
            aggregateMap[wilayah].P += 1;
        }
        aggregateMap[wilayah].TOTAL += 1;
        
        // 2. Hitungan Dinamis
        if (aggregateMap[wilayah].dynamic[agama] !== undefined) {
             aggregateMap[wilayah].dynamic[agama] += 1;
        }
        if (aggregateMap[wilayah].dynamic[pekerjaan] !== undefined) {
             aggregateMap[wilayah].dynamic[pekerjaan] += 1;
        }
        if (aggregateMap[wilayah].dynamic[status_kawin] !== undefined) {
             aggregateMap[wilayah].dynamic[status_kawin] += 1;
        }
    });

    const processedData = [];
    let grandTotal = { L: 0, P: 0, TOTAL: 0 };
    let grandTotalDynamic = {};
    allUniqueCriteria.forEach(criteria => {
        grandTotalDynamic[criteria] = 0;
    });

    for (const key in aggregateMap) {
        const item = aggregateMap[key];
        
        let row = [
            item.WILAYAH, 
            item.TOTAL, 
            item.L, 
            item.P
        ];
        
        allUniqueCriteria.forEach(criteria => {
             const count = item.dynamic[criteria] || 0;
             row.push(count);
             grandTotalDynamic[criteria] += count;
        });

        processedData.push(row);
        
        grandTotal.L += item.L;
        grandTotal.P += item.P;
        grandTotal.TOTAL += item.TOTAL;
    }
    
    // Tambahkan baris Grand Total
    let grandTotalRow = [
        "TOTAL KESELURUHAN", 
        grandTotal.TOTAL, 
        grandTotal.L, 
        grandTotal.P
    ];
    allUniqueCriteria.forEach(criteria => {
        grandTotalRow.push(grandTotalDynamic[criteria]);
    });
    
    processedData.push(grandTotalRow);
    
    return processedData;
}


// ----------------------------------------------------
// 2. FUNGSI UNTUK MEMPERBARUI GRAFIK (Tidak Berubah)
// ----------------------------------------------------
function updateCharts(stats) {
    function renderChart(chartVar, elementId, type, data, options) {
        if (chartVar) chartVar.destroy();
        return new Chart(document.getElementById(elementId), {
            type: type,
            data: data,
            options: options
        });
    }

    const chartColors = {
        primary: '#1976d2',
        success: '#2ecc71',
        warning: '#f39c12',
        info: '#00bcd4'
    };

    const barOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { 
            y: { beginAtZero: true },
            x: { ticks: { padding: 0, autoSkip: false } }
        },
    };
    
    const horizontalBarOptions = {...barOptions, indexAxis: 'y'};

    // 1. Chart Jenis Kelamin (Horizontal Bar)
    genderChartInstance = renderChart(genderChartInstance, 'genderChart', 'bar', {
        labels: Object.keys(stats.genderStats),
        datasets: [{
            data: Object.values(stats.genderStats),
            backgroundColor: chartColors.primary
        }]
    }, horizontalBarOptions);

    // 2. Chart Agama (Vertical Bar)
    agamaChartInstance = renderChart(agamaChartInstance, 'agamaChart', 'bar', {
        labels: Object.keys(stats.agamaStats),
        datasets: [{
            data: Object.values(stats.agamaStats),
            backgroundColor: chartColors.success
        }]
    }, barOptions);

    // 3. Chart Status Perkawinan (Horizontal Bar)
    statusChartInstance = renderChart(statusChartInstance, 'statusChart', 'bar', {
        labels: Object.keys(stats.statusStats),
        datasets: [{
            data: Object.values(stats.statusStats),
            backgroundColor: chartColors.warning
        }]
    }, horizontalBarOptions);
    
    // 4. Chart Pekerjaan (Vertical Bar)
    pekerjaanChartInstance = renderChart(pekerjaanChartInstance, 'pekerjaanChart', 'bar', {
        labels: Object.keys(stats.pekerjaanStats), 
        datasets: [{
            data: Object.values(stats.pekerjaanStats), 
            backgroundColor: chartColors.info
        }]
    }, barOptions);
}


// ----------------------------------------------------
// 3. FUNGSI UNTUK MEMPERBARUI TABEL AGREGAT (Single Header)
// ----------------------------------------------------

function updateTable(dataArray) {
    if (dataTableAgregatInstance) {
        dataTableAgregatInstance.destroy();
    }
    
    const dataTableData = aggregateData(dataArray);
    const tableHeaders = <?= json_encode($headers_agregat) ?>;
    
    // --- GENERASI HEADER SATU BARIS ---
    
    const $thead = $('#dataTableAgregat thead');
    $thead.empty().append('<tr id="header-single"></tr>'); 
    const $headerRow = $('#header-single');
    
    // Tampilkan semua header
    tableHeaders.forEach(header => {
        // Membersihkan label pengelompokan (misal: "AGAMA: Islam" menjadi "Islam")
        const cleanHeader = header.replace(/(WILAYAH|TOTAL PENDUDUK|LAKI-LAKI|PEREMPUAN|AGAMA|PEKERJAAN|STATUS):\s*/g, '').trim(); 
        $headerRow.append(`<th class="text-center">${cleanHeader}</th>`);
    });

    // --- INISIALISASI DATATABLES DENGAN SINGLE HEADER ---
    
    const columnDefinitions = [];
    for (let i = 0; i < tableHeaders.length; i++) {
        columnDefinitions.push({ 
            data: i, 
            className: (i === 0 ? 'text-start fw-bold' : 'text-center'),
            title: tableHeaders[i]
        });
    }

    dataTableAgregatInstance = $('#dataTableAgregat').DataTable({
        data: dataTableData,
        columns: columnDefinitions,
        pageLength: 30, 
        lengthMenu: [10, 30, 50, 100], 
        destroy: true, 
        searching: false,
        responsive: true,
        ordering: false, 
        scrollX: true, 
        scrollCollapse: true,
        fixedColumns: false, // Dinonaktifkan
        
        initComplete: function() {
            dataTableAgregatInstance.columns.adjust().draw(); 
        }
    });

    // Berikan style khusus pada baris terakhir (Total)
    $('#dataTableAgregat tbody tr:last').addClass('fw-bold bg-info bg-opacity-25');
}

// ----------------------------------------------------
// 4. FUNGSI UTAMA AJAX FILTERING
// ----------------------------------------------------
function applyFilter() {
    const filters = {
        gender: $('#gender').val(),
        agama: $('#agama').val(),
        pekerjaan: $('#pekerjaan').val(),
        search: $('#search').val()
    };
    
    $('#totalDataCount').text('Loading...');
    
    $.ajax({
        url: 'api.php', 
        method: 'GET',
        data: filters,
        dataType: 'json',
        success: function(response) {
            // Update Card Statistik
            $('#totalDataCount').text(response.totalData.toLocaleString());
            $('#genderCategoryCount').text(response.genderCategoryCount);
            $('#agamaCategoryCount').text(response.agamaCategoryCount);
            $('#pekerjaanCategoryCount').text(response.pekerjaanCategoryCount); 

            // Update Grafik dan Tabel Agregat
            updateCharts(response);
            updateTable(response.data); 
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error:", status, error);
            $('#totalDataCount').text('Error');
            alert("Gagal memuat data. Periksa file api.php.");
        }
    });
}

// ----------------------------------------------------
// 5. INISIALISASI DAN EVENT HANDLERS
// ----------------------------------------------------
$(document).ready(function() {
    // Muat data pertama kali saat halaman dibuka
    applyFilter(); 
    
    // Event handler untuk tombol "Filter Data" dan "Cari"
    $('#applyFilterBtn').on('click', function() {
        applyFilter();
    });

    // Event handler untuk reset filter
    $('#resetFilterBtn').on('click', function() {
        $('#gender').val('Semua').trigger('change');
        $('#agama').val('Semua').trigger('change');
        $('#pekerjaan').val('Semua').trigger('change');
        $('#search').val('');
        applyFilter();
    });


    // Event handler untuk input pencarian (saat Enter ditekan)
    $('#search').on('keypress', function(e) {
        if (e.which === 13) { 
            e.preventDefault();
            applyFilter();
        }
    });
    
});
</script>
</body>
</html>