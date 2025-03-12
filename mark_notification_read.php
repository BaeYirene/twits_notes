<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['notif_id'])) {
    exit;
}

$notif_id = $_POST['notif_id'];

$stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
$stmt->bind_param("i", $notif_id);
$stmt->execute();
?>
