<?php
// FILE: includes/db_connect.php (TIDAK ADA PERUBAHAN)

$servername = "localhost";
$username = "root"; // Sesuaikan
$password = "";     // Sesuaikan
$dbname = "ecommerce_db"; // Nama database

// Membuat koneksi
$conn = new mysqli($servername, $username, $password, $dbname);

// Memeriksa koneksi
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}
?>
