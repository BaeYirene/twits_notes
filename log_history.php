<?php
require_once 'db.php';

function logHistory($user_id, $type, $content) {
    global $conn;
    $sql = "INSERT INTO history (user_id, type, content) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $user_id, $type, $content);
    $stmt->execute();
    $stmt->close();
}
?>
