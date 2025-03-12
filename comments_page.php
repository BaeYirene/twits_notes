<?php
session_start();
require 'db.php';

// Check if post_id is provided
if (!isset($_GET['post_id'])) {
    header("Location: fyp.php");
    exit;
}

$post_id = intval($_GET['post_id']);

// Get post information
$post_query = $conn->prepare("
    SELECT posts.*, users.username, users.profile_pic 
    FROM posts 
    LEFT JOIN users ON posts.user_id = users.id 
    WHERE posts.id = ?
");
$post_query->bind_param("i", $post_id);
$post_query->execute();
$post_result = $post_query->get_result();

if ($post_result->num_rows == 0) {
    header("Location: fyp.php");
    exit;
}

$post = $post_result->fetch_assoc();
$profile_pic = !empty($post['profile_pic']) ? $post['profile_pic'] : 'default-profile.jpg';
$username = !empty($post['username']) ? $post['username'] : 'user_' . $post['user_id'];

// Get comments
$comments_query = $conn->prepare("
    SELECT comments.*, users.username, users.profile_pic,
           (SELECT COUNT(*) FROM comment_likes WHERE comment_id = comments.id AND type = 'like') AS like_count
    FROM comments 
    LEFT JOIN users ON comments.user_id = users.id 
    WHERE comments.post_id = ? 
    ORDER BY comments.created_at DESC
");
$comments_query->bind_param("i", $post_id);
$comments_query->execute();
$comments_result = $comments_query->get_result();
$comments = $comments_result->fetch_all(MYSQLI_ASSOC);

// Handle comment submission
if (isset($_POST['submit_comment'])) {
    if (!isset($_SESSION['user_id'])) {
        $error = "Login diperlukan untuk menambahkan komentar";
    } else {
        $user_id = $_SESSION['user_id'];
        $comment_text = trim($_POST['comment_text']);
        
        if (empty($comment_text)) {
            $error = "Komentar tidak boleh kosong";
        } else {
            $insert_comment = $conn->prepare("INSERT INTO comments (post_id, user_id, comment_text) VALUES (?, ?, ?)");
            $insert_comment->bind_param("iis", $post_id, $user_id, $comment_text);
            
            if ($insert_comment->execute()) {
                // Redirect to avoid form resubmission
                header("Location: comments_page.php?post_id=" . $post_id);
                exit;
            } else {
                $error = "Gagal menambahkan komentar: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Comments - Twits Notes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        :root {
            --primary-color: #fe2c55;
            --secondary-color: #25f4ee;
            --background-dark: #121212;
            --text-light: #ffffff;
            --text-secondary: #aaaaaa;
            --overlay-color: rgba(0, 0, 0, 0.5);
            --border-color: #333333;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background-color: var(--background-dark);
            color: var(--text-light);
        }
        
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            display: flex;
            align-items: center;
            padding: 15px;
            background-color: rgba(18, 18, 18, 0.95);
            z-index: 100;
            border-bottom: 1px solid var(--border-color);
        }
        
        .back-button {
            color: var(--text-light);
            text-decoration: none;
            font-size: 20px;
            margin-right: 15px;
        }
        
        .header h2 {
            font-size: 18px;
        }
        
        .container {
            padding-top: 60px;
            padding-bottom: 70px;
        }
        
        .post-preview {
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: flex-start;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
        }
        
        .post-content {
            flex-grow: 1;
        }
        
        .username {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .content-text {
            margin-bottom: 10px;
            font-size: 14px;
            line-height: 1.4;
        }
        
        .comment-box {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 10px 15px;
            background-color: rgba(18, 18, 18, 0.95);
            border-top: 1px solid var(--border-color);
            display: flex;
            align-items: center;
        }
        
        .comment-input {
            flex-grow: 1;
            padding: 10px 15px;
            border-radius: 20px;
            border: 1px solid var(--border-color);
            background-color: #1e1e1e;
            color: var(--text-light);
            font-size: 14px;
            margin-right: 10px;
        }
        
        .comment-input:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .submit-comment {
            background-color: transparent;
            border: none;
            color: var(--primary-color);
            font-weight: bold;
            font-size: 14px;
            cursor: pointer;
        }
        
        .comment-list {
            padding: 0 15px;
        }
        
        .comment-item {
            padding: 15px 0;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: flex-start;
        }
        
        .comment-content {
            flex-grow: 1;
        }
        
        .comment-text {
            font-size: 14px;
            line-height: 1.4;
            margin-bottom: 5px;
        }
        
        .comment-meta {
            display: flex;
            align-items: center;
            font-size: 12px;
            color: var(--text-secondary);
        }
        
        .comment-time {
            margin-right: 15px;
        }
        
        .like-comment {
            display: flex;
            align-items: center;
            margin-right: 15px;
            color: var(--text-secondary);
            cursor: pointer;
            background: none;
            border: none;
            font-size: 12px;
            padding: 0;
        }
        
        .like-comment.active {
            color: var(--primary-color);
        }
        
        .like-comment i {
            margin-right: 5px;
        }
        
        .reply-btn {
            color: var(--text-secondary);
            cursor: pointer;
            background: none;
            border: none;
            font-size: 12px;
            padding: 0;
        }
        
        .error-message {
            background-color: rgba(255, 0, 0, 0.2);
            color: #ff6b6b;
            padding: 10px 15px;
            border-radius: 5px;
            margin: 10px 15px;
            font-size: 14px;
        }
        
        .no-comments {
            padding: 30px 0;
            text-align: center;
            color: var(--text-secondary);
            font-size: 14px;
        }
        
        /* Toast notification style */
        .toast {
            position: fixed;
            top: 80px;
            left: 50%;
            transform: translateX(-50%);
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 10px 20px;
            border-radius: 20px;
            z-index: 1000;
            display: none;
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="javascript:history.back()" class="back-button">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h2>Comments</h2>
    </div>
    
    <div class="container">
        <div class="post-preview">
            <img src="uploads/profiles/<?= htmlspecialchars($profile_pic); ?>" class="user-avatar" alt="Profile">
            <div class="post-content">
                <div class="username">@<?= htmlspecialchars($username); ?></div>
                <div class="content-text"><?= htmlspecialchars($post['content']); ?></div>
            </div>
        </div>
        
        <?php if (isset($error)): ?>
        <div class="error-message">
            <?= $error; ?>
        </div>
        <?php endif; ?>
        
        <div class="comment-list">
            <?php if (empty($comments)): ?>
            <div class="no-comments">
                <i class="fas fa-comment-slash"></i>
                <p>No comments yet. Be the first to comment!</p>
            </div>
            <?php else: ?>
                <?php foreach ($comments as $comment): 
                    $comment_profile_pic = !empty($comment['profile_pic']) ? $comment['profile_pic'] : 'default-profile.jpg';
                    $comment_username = !empty($comment['username']) ? $comment['username'] : 'user_' . $comment['user_id'];
                    
                    // Format time difference
                    $comment_time = new DateTime($comment['created_at']);
                    $now = new DateTime();
                    $interval = $comment_time->diff($now);
                    
                    if ($interval->y > 0) {
                        $time_str = $interval->y . 'y ago';
                    } elseif ($interval->m > 0) {
                        $time_str = $interval->m . 'm ago';
                    } elseif ($interval->d > 0) {
                        $time_str = $interval->d . 'd ago';
                    } elseif ($interval->h > 0) {
                        $time_str = $interval->h . 'h ago';
                    } elseif ($interval->i > 0) {
                        $time_str = $interval->i . 'm ago';
                    } else {
                        $time_str = 'Just now';
                    }
                    
                    // Check if user liked this comment
                    $liked = false;
                    if (isset($_SESSION['user_id'])) {
                        $like_check = $conn->prepare("SELECT id FROM comment_likes WHERE comment_id = ? AND user_id = ? AND type = 'like'");
                        $like_check->bind_param("ii", $comment['id'], $_SESSION['user_id']);
                        $like_check->execute();
                        $like_result = $like_check->get_result();
                        $liked = ($like_result->num_rows > 0);
                    }
                ?>
                <div class="comment-item" id="comment-<?= $comment['id']; ?>">
                    <img src="uploads/profiles/<?= htmlspecialchars($comment_profile_pic); ?>" class="user-avatar" alt="Profile">
                    <div class="comment-content">
                        <div class="username">@<?= htmlspecialchars($comment_username); ?></div>
                        <div class="comment-text"><?= htmlspecialchars($comment['comment_text']); ?></div>
                        <div class="comment-meta">
                            <span class="comment-time"><?= $time_str; ?></span>
                            <button class="like-comment <?= $liked ? 'active' : ''; ?>" data-id="<?= $comment['id']; ?>">
                                <i class="fas fa-heart"></i> <span class="like-count"><?= $comment['like_count']; ?></span>
                            </button>
                            <button class="reply-btn" data-username="<?= htmlspecialchars($comment_username); ?>">
                                Reply
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <form class="comment-box" method="post" action="">
        <input type="text" class="comment-input" name="comment_text" placeholder="Add a comment..." autocomplete="off">
        <button type="submit" name="submit_comment" class="submit-comment">Post</button>
    </form>
    
    <div class="toast" id="toast-message"></div>
    
    <script>
    $(document).ready(function() {
        // Function to show toast messages
        function showToast(message, duration = 2000) {
            $('#toast-message').text(message).fadeIn();
            setTimeout(function() {
                $('#toast-message').fadeOut();
            }, duration);
        }
        
        // Check if user is logged in
        function checkLogin() {
            <?php if (!isset($_SESSION['user_id'])): ?>
            return false;
            <?php else: ?>
            return true;
            <?php endif; ?>
        }
        
        // Like comment functionality
        $(document).on('click', '.like-comment', function() {
            if (!checkLogin()) {
                showToast("Login diperlukan untuk menyukai komentar", 2000);
                return;
            }
            
            let $this = $(this);
            let commentId = $this.data('id');
            let isActive = $this.hasClass('active');
            
            // Optimistic UI update
            if (isActive) {
                $this.removeClass('active');
                let count = parseInt($this.find('.like-count').text()) - 1;
                $this.find('.like-count').text(count < 0 ? 0 : count);
            } else {
                $this.addClass('active');
                let count = parseInt($this.find('.like-count').text()) + 1;
                $this.find('.like-count').text(count);
            }
            
            // Send AJAX request
            $.post("like_comment.php", {
                comment_id: commentId,
                action: isActive ? 'unlike' : 'like'
            }, function(data) {
                try {
                    let response = JSON.parse(data);
                    if (response.success) {
                        // Update like count with actual number from server
                        $this.find('.like-count').text(response.likes);
                    } else {
                        // Revert optimistic update on error
                        if (isActive) {
                            $this.addClass('active');
                        } else {
                            $this.removeClass('active');
                        }
                        showToast(response.error || "An error occurred", 2000);
                    }
                } catch (e) {
                    console.error("Error parsing response:", e);
                }
            });
        });
        
        // Reply functionality
        $(document).on('click', '.reply-btn', function() {
            let username = $(this).data('username');
            $('.comment-input').val('@' + username + ' ').focus();
        });
    });
    </script>
</body>
</html>