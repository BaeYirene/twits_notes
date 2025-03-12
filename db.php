<?php
$host = "localhost"; // Server database (default: localhost)
$user = "root"; // Username default di XAMPP
$pass = ""; // Password default di XAMPP kosong
$dbname = "twits_notes"; // Nama database

// Membuat koneksi
$conn = new mysqli($host, $user, $pass, $dbname);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
