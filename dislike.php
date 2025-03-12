<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_POST['post_id']) || !isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "error" => "Invalid request"]);
    exit;
}

$post_id = intval($_POST['post_id']);
$user_id = $_SESSION['user_id'];

// Cek apakah user sudah dislike sebelumnya
$checkQuery = $conn->prepare("SELECT * FROM likes WHERE post_id = ? AND user_id = ? AND type = 'dislike'");
$checkQuery->bind_param("ii", $post_id, $user_id);
$checkQuery->execute();
$result = $checkQuery->get_result();
$existing = $result->fetch_assoc();
$checkQuery->close();

if ($existing) {
    $deleteQuery = $conn->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?");
    $deleteQuery->bind_param("ii", $post_id, $user_id);
    $deleteQuery->execute();
    $deleteQuery->close();
} else {
    $insertQuery = $conn->prepare("INSERT INTO likes (post_id, user_id, type) VALUES (?, ?, 'dislike')");
    $insertQuery->bind_param("ii", $post_id, $user_id);
    $insertQuery->execute();
    $insertQuery->close();
}

// Ambil jumlah dislike terbaru
$countDislikes = $conn->prepare("SELECT COUNT(*) AS dislikes FROM likes WHERE post_id = ? AND type = 'dislike'");
$countDislikes->bind_param("i", $post_id);
$countDislikes->execute();
$result = $countDislikes->get_result()->fetch_assoc();
$countDislikes->close();

echo json_encode(["success" => true, "dislikes" => $result['dislikes']]);
exit;
?>
