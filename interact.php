<?php
session_start();
require 'db.php';

$data = json_decode(file_get_contents("php://input"), true);
$post_id = $data['post_id'];
$action = $data['action'];
$user_id = $_SESSION['user_id'] ?? 0; 

if ($post_id && $action) {
    if ($action == "like") {
        $stmt = $conn->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE post_id = post_id");
    } elseif ($action == "dislike") {
        $stmt = $conn->prepare("INSERT INTO dislikes (post_id, user_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE post_id = post_id");
    } elseif ($action == "not_interested") {
        $stmt = $conn->prepare("INSERT INTO not_interested (post_id, user_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE post_id = post_id");
    }

    if (isset($stmt)) {
        $stmt->bind_param("ii", $post_id, $user_id);
        $stmt->execute();
    }
}

echo json_encode(["status" => "success"]);
?>
