<?php
session_start();
require 'db.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    // Jika belum login, kembalikan respons JSON error
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        echo json_encode(['success' => false, 'error' => 'Login diperlukan']);
        exit;
    }
    // Jika bukan AJAX request, redirect ke halaman login
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Cek apakah ini POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi input
    if (isset($_POST['post_id']) && isset($_POST['comment']) && !empty($_POST['comment'])) {
        $post_id = (int)$_POST['post_id'];
        $comment = trim($_POST['comment']);
        $parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        
        // Persiapkan query SQL
        if ($parent_id) {
            // Ini adalah balasan komentar
            $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, content, parent_id, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("iisi", $post_id, $user_id, $comment, $parent_id);
        } else {
            // Ini adalah komentar utama
            $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("iis", $post_id, $user_id, $comment);
        }
        
        if ($stmt->execute()) {
            $comment_id = $stmt->insert_id;
            
            // Cek apakah ini AJAX request
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                // Kembalikan respons JSON sukses
                echo json_encode(['success' => true, 'comment_id' => $comment_id]);
                exit;
            } else {
                // Jika bukan AJAX, redirect ke halaman komentar
                header("Location: comments_page.php?post_id=$post_id");
                exit;
            }
        } else {
            // Error saat eksekusi query
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                echo json_encode(['success' => false, 'error' => 'Gagal menyimpan komentar: ' . $stmt->error]);
                exit;
            } else {
                // Set error message dan redirect kembali
                $_SESSION['error_message'] = "Gagal menyimpan komentar: " . $stmt->error;
                header("Location: comments_page.php?post_id=$post_id");
                exit;
            }
        }
    } else {
        // Input tidak valid
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            echo json_encode(['success' => false, 'error' => 'Input tidak valid']);
            exit;
        } else {
            $_SESSION['error_message'] = "Komentar tidak boleh kosong";
            $post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
            header("Location: comments_page.php?post_id=$post_id");
            exit;
        }
    }
} else {
    // Bukan POST request
    header("Location: index.php");
    exit;
}
?>