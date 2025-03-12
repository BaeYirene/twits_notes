<?php
session_start();
require 'db.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    die("Error: Anda harus login terlebih dahulu.");
}

$user_id = $_SESSION['user_id']; 
$content = $_POST['content'] ?? ''; 
$media = "";

// Cek apakah user_id valid
$user_check = $conn->prepare("SELECT id FROM users WHERE id = ?");
$user_check->bind_param("i", $user_id);
$user_check->execute();
$result = $user_check->get_result();
if ($result->num_rows == 0) {
    die("Error: User tidak ditemukan.");
}
$user_check->close();

// Cek apakah file diunggah
if (!empty($_FILES['media']['name'])) {
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true); // Buat folder jika belum ada
    }

    $media = time() . "_" . basename($_FILES["media"]["name"]); // Rename file agar unik
    $target_file = $target_dir . $media;

    // Validasi file
    $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'mov', 'avi'];

    if (in_array($file_type, $allowed_types)) {
        if (!move_uploaded_file($_FILES["media"]["tmp_name"], $target_file)) {
            die("Error: Gagal mengupload file.");
        }
    } else {
        die("Error: Format file tidak didukung.");
    }
}

// Simpan ke database
$stmt = $conn->prepare("INSERT INTO posts (user_id, content, media) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $user_id, $content, $media);

if ($stmt->execute()) {
    echo "<script>alert('Konten berhasil diupload!'); window.location.href='index.php';</script>";
} else {
    echo "Gagal mengupload!";
}

$stmt->close();
?>
