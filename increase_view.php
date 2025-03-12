<?php
session_start();
require 'db.php';

// Check if post_id is provided
if (!isset($_POST['post_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Missing post_id parameter'
    ]);
    exit;
}

$post_id = intval($_POST['post_id']);
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$ip_address = $_SERVER['REMOTE_ADDR'];
$user_agent = $_SERVER['HTTP_USER_AGENT'];

// Generate a unique viewer ID based on IP and user agent if not logged in
$viewer_id = $user_id > 0 ? $user_id : md5($ip_address . $user_agent);

// Check if this view was already counted in the last 24 hours
$check_query = $conn->prepare("SELECT id FROM views_count WHERE post_id = ? AND (user_id = ? OR viewer_id = ?) AND view_date > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
$check_query->bind_param("iis", $post_id, $user_id, $viewer_id);
$check_query->execute();
$result = $check_query->get_result();

if ($result->num_rows == 0) {
    // Insert new view
    $insert_query = $conn->prepare("INSERT INTO views_count (post_id, user_id, viewer_id, ip_address) VALUES (?, ?, ?, ?)");
    $insert_query->bind_param("iiss", $post_id, $user_id, $viewer_id, $ip_address);
    $insert_query->execute();
}

// Get updated view count
$view_count_query = $conn->prepare("SELECT COUNT(*) as count FROM views_count WHERE post_id = ?");
$view_count_query->bind_param("i", $post_id);
$view_count_query->execute();
$view_count_result = $view_count_query->get_result();
$view_count = $view_count_result->fetch_assoc()['count'];

// Return response with updated view count
echo json_encode([
    'success' => true,
    'views' => $view_count
]);
?>