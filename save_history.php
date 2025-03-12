<?php
session_start();
require 'db.php';

// Pastikan koneksi tersedia
if (!$conn) {
    die(json_encode(["success" => false, "error" => "Koneksi database gagal"]));
}

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    die(json_encode(["success" => false, "error" => "Silakan login terlebih dahulu"]));
}

$user_id = $_SESSION['user_id'];
$post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

if ($post_id <= 0) {
    die(json_encode(["success" => false, "error" => "ID postingan tidak valid"]));
}

// Cek apakah history untuk video ini sudah ada dalam 30 hari terakhir
$check_query = "
    SELECT * FROM history 
    WHERE user_id = ? AND post_id = ? 
    AND watched_at >= NOW() - INTERVAL 30 DAY";

$stmt = $conn->prepare($check_query);
$stmt->bind_param("ii", $user_id, $post_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    // Jika belum ada dalam 30 hari terakhir, tambahkan ke history
    $insert_query = "
        INSERT INTO history (user_id, post_id, watched_at) 
        VALUES (?, ?, NOW())";
    
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("ii", $user_id, $post_id);
    $stmt->execute();
}

// Beri response ke frontend
echo json_encode(["success" => true]);
?>
