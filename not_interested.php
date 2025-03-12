<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['post_id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$user_id = $_SESSION['user_id'];
$post_id = (int)$_POST['post_id'];

// Check if the record already exists
$check_query = $conn->prepare("SELECT * FROM not_interested WHERE user_id = ? AND post_id = ?");
$check_query->bind_param("ii", $user_id, $post_id);
$check_query->execute();
$result = $check_query->get_result();

if ($result->num_rows > 0) {
    // Already marked as not interested
    echo json_encode(['success' => true]);
    exit;
}

// Insert the not interested record
$insert_query = $conn->prepare("INSERT INTO not_interested (user_id, post_id) VALUES (?, ?)");
$insert_query->bind_param("ii", $user_id, $post_id);

if ($insert_query->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}

$insert_query->close();
$conn->close();
?>