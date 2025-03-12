<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["new_notifications" => false]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil notifikasi terbaru yang belum dibaca
$stmt = $conn->prepare("SELECT id, message FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row) {
    echo json_encode(["new_notifications" => true, "message" => $row['message'], "notif_id" => $row['id']]);
} else {
    echo json_encode(["new_notifications" => false]);
}
?>
