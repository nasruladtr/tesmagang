<?php
// File: api.php
require_once 'db_koneksi.php'; 

// --- 1. Ambil Nilai Filter dari AJAX ---
$filter_gender = $_REQUEST['gender'] ?? 'Semua';
$filter_agama = $_REQUEST['agama'] ?? 'Semua';
$filter_pekerjaan = $_REQUEST['pekerjaan'] ?? 'Semua';
$search_query = $_REQUEST['search'] ?? '';

// --- 2. Bangun Kueri Database dengan Filter ---
$where_clauses = [];
$params = [];
$types = '';

if ($filter_gender !== 'Semua') {
    $where_clauses[] = "jenis_kelamin = ?";
    $params[] = $filter_gender;
    $types .= 's';
}
if ($filter_agama !== 'Semua') {
    $where_clauses[] = "agama = ?";
    $params[] = $filter_agama;
    $types .= 's';
}
if ($filter_pekerjaan !== 'Semua') {
    $where_clauses[] = "pekerjaan = ?";
    $params[] = $filter_pekerjaan;
    $types .= 's';
}
if (!empty($search_query)) {
    $where_clauses[] = "(nama LIKE ? OR alamat LIKE ? OR kecamatan LIKE ?)";
    $search_param = '%' . $search_query . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

$sql_select = "SELECT id, nama, jenis_kelamin, tempat_tgl_lahir, alamat, agama, status_kawin, pekerjaan, pendidikan, kecamatan, kabupaten FROM data_penduduk";
if (!empty($where_clauses)) {
    $sql_select .= " WHERE " . implode(' AND ', $where_clauses);
}

// --- 3. Jalankan Kueri dan Ambil Data ---
$stmt = $conn->prepare($sql_select);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// --- 4. Hitung Statistik dari Hasil Kueri yang Sudah Difilter ---
$genderStats = [];
$agamaStats = [];
$statusStats = [];
$pekerjaanStats = []; // <--- BARU

foreach ($data as $row) {
    $jk = trim($row['jenis_kelamin'] ?? 'Tidak Diketahui');
    $genderStats[$jk] = ($genderStats[$jk] ?? 0) + 1;

    $agama = trim($row['agama'] ?? 'Tidak Diketahui');
    $agamaStats[$agama] = ($agamaStats[$agama] ?? 0) + 1;
    
    $status = trim($row['status_kawin'] ?? 'Tidak Diketahui');
    $statusStats[$status] = ($statusStats[$status] ?? 0) + 1;
    
    $pekerjaan = trim($row['pekerjaan'] ?? 'Tidak Diketahui'); // <--- BARU
    $pekerjaanStats[$pekerjaan] = ($pekerjaanStats[$pekerjaan] ?? 0) + 1; // <--- BARU
}

// --- 5. Keluarkan Hasil dalam Format JSON ---
header('Content-Type: application/json');
echo json_encode([
    'data' => $data,
    'genderStats' => $genderStats,
    'agamaStats' => $agamaStats,
    'statusStats' => $statusStats,
    'pekerjaanStats' => $pekerjaanStats, // <--- BARU
    'totalData' => count($data),
    'genderCategoryCount' => count($genderStats),
    'agamaCategoryCount' => count($agamaStats),
    'pekerjaanCategoryCount' => count($pekerjaanStats) // <--- BARU
]);
?>