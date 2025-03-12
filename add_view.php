<?php
session_start();
require 'db.php';

$data = json_decode(file_get_contents("php://input"), true);
$post_id = $data['post_id'];
$user_id = $_SESSION['user_id'] ?? 0; 

if ($post_id) {
    $stmt = $conn->prepare("INSERT INTO views_count (post_id, user_id, viewed_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("ii", $post_id, $user_id);
    $stmt->execute();
}

echo json_encode(["status" => "success"]);
?>
