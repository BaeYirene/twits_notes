<?php
// File: get_face_data.php
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_GET['username'])) {
    echo json_encode(['success' => false, 'message' => 'Username tidak diberikan']);
    exit;
}

$username = $_GET['username'];
$sql = "SELECT face_descriptor FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'User tidak ditemukan']);
    exit;
}

$user = $result->fetch_assoc();
$faceDescriptor = json_decode($user['face_descriptor']); // Data harus dalam format JSON (array angka)

echo json_encode([
    'success' => true,
    'faceData' => $faceDescriptor
]);
?>
