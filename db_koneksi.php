<?php
// Gunakan nama file ini: db_koneksi.php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_data";

// Menggunakan objek mysqli untuk koneksi
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>