<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Silakan login terlebih dahulu.");
}

if (!isset($_GET['post_id'])) {
    die("Post ID tidak ditemukan.");
}

$user_id = $_SESSION['user_id'];
$post_id = intval($_GET['post_id']); // Pastikan ada post_id yang valid

// Simpan ke history hanya jika belum pernah ditonton dalam 30 hari terakhir
$check_query = $conn->prepare("
    SELECT id FROM history WHERE user_id = ? AND post_id = ? AND watched_at >= NOW() - INTERVAL 30 DAY
");
$check_query->bind_param("ii", $user_id, $post_id);
$check_query->execute();
$result = $check_query->get_result();

if ($result->num_rows === 0) {
    $insert_query = $conn->prepare("INSERT INTO history (user_id, post_id) VALUES (?, ?)");
    $insert_query->bind_param("ii", $user_id, $post_id);
    $insert_query->execute();
}

// Ambil detail postingan
$post_query = $conn->prepare("SELECT content, media FROM posts WHERE id = ?");
$post_query->bind_param("i", $post_id);
$post_query->execute();
$post_result = $post_query->get_result();
$post = $post_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Post</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="post-container">
        <h2>Detail Postingan</h2>
        <p><?= htmlspecialchars($post['content']); ?></p>
        
        <?php if (!empty($post['media'])): ?>
            <img src="uploads/<?= htmlspecialchars($post['media']); ?>" class="uploaded-media">
        <?php endif; ?>

        <a href="history.php">Lihat Riwayat</a>
    </div>
</body>
</html>
