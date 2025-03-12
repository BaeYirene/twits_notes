<?php
session_start();
require 'db.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'error' => 'User tidak login']));
}

// Pastikan semua parameter yang diperlukan sudah dikirim
if (!isset($_POST['post_id']) || !isset($_POST['type'])) {
    die(json_encode(['success' => false, 'error' => 'Parameter tidak lengkap']));
}

$post_id = intval($_POST['post_id']);
$user_id = $_SESSION['user_id'];
$type = $_POST['type'] === 'like' ? 'like' : 'dislike';

// Langsung tambahkan like/dislike tanpa pemeriksaan
// Buat ID unik untuk setiap interaksi
$interaction_id = uniqid();

// Insert like/dislike baru setiap kali
$stmt = $conn->prepare("INSERT INTO likes (user_id, post_id, type, interaction_id) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iiss", $user_id, $post_id, $type, $interaction_id);
$stmt->execute();

// Dapatkan jumlah like/dislike terbaru
$count_query = $conn->prepare("
    SELECT 
        (SELECT COUNT(*) FROM likes WHERE post_id = ? AND type = 'like') AS likes,
        (SELECT COUNT(*) FROM likes WHERE post_id = ? AND type = 'dislike') AS dislikes
");
$count_query->bind_param("ii", $post_id, $post_id);
$count_query->execute();
$counts = $count_query->get_result()->fetch_assoc();

// Kembalikan respons dengan jumlah like/dislike terbaru
echo json_encode([
    'success' => true,
    'likes' => $counts['likes'],
    'dislikes' => $counts['dislikes']
]);