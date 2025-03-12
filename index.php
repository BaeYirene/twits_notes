<?php
session_start();
require 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Set cookies to remember the user for 30 days
if (!isset($_COOKIE['user_logged_in'])) {
    setcookie('user_logged_in', 'true', time() + (30 * 24 * 60 * 60), "/"); // 30 days
    echo "<script>alert('Kami menggunakan cookies untuk mengingat bahwa Anda sudah login.');</script>";
}

// Fetch all posts except those marked as "Not Interested"
$result = $conn->query("
    SELECT posts.*, users.username, users.id as author_id, users.profile_pic as author_pic,
           (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id AND type = 'like') AS like_count,
           (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id AND type = 'dislike') AS dislike_count,
           (SELECT COUNT(*) FROM comments WHERE comments.post_id = posts.id) AS comment_count
    FROM posts
    JOIN users ON posts.user_id = users.id
    WHERE posts.id NOT IN (SELECT post_id FROM not_interested WHERE user_id = $user_id)
    ORDER BY like_count DESC, comment_count DESC
");

// Fetch user data for the sidebar
$user_query = $conn->query("SELECT username, profile_pic FROM users WHERE id = $user_id");
$user_data = $user_query->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Twits Notes</title>
    <link rel="stylesheet" href="style.css?v=3">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        :root {
            --primary-color:rgb(65, 140, 253);
            --primary-dark:rgb(40, 147, 255);
            --secondary-color: #f8f9fa;
            --text-color: #333;
            --border-radius: 10px;
            --sidebar-width: 240px;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            color: var(--text-color);
            padding: 0;
            margin: 0;
            min-height: 100vh;
        }
        
        .layout-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            background-color: white;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            border-right: 1px solid #eee;
            box-shadow: var(--shadow);
            z-index: 1000;
            padding: 20px 0;
            transition: all 0.3s ease;
        }
        
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #eee;
            margin-bottom: 20px;
        }
        
        .sidebar-logo {
            font-weight: bold;
            font-size: 24px;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        .sidebar-logo i {
            margin-right: 8px;
        }
        
        .user-profile {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .profile-image {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary-color);
            margin-bottom: 10px;
        }
        
        .profile-name {
            font-size: 16px;
            font-weight: 600;
        }
        
        .sidebar-nav {
            padding: 0 10px;
        }
        
        .nav-item {
            margin-bottom: 5px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            text-decoration: none;
            color: #666;
            border-radius: var(--border-radius);
            transition: all 0.2s ease;
        }
        
        .nav-link:hover, .nav-link.active {
            background-color: #f0f7ff;
            color: var(--primary-color);
        }
        
        .nav-link i {
            font-size: 18px;
            margin-right: 10px;
            width: 24px;
            text-align: center;
        }
        
        .logout-btn {
            margin-top: 20px;
            color: #ff5757;
        }
        
        .logout-btn:hover {
            background-color: #fff0f0;
            color: #ff3333;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: all 0.3s ease;
        }
        
        .page-header {
            padding: 15px 0;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .page-title {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 0;
        }
        
        /* Post Form Styles */
        .post-form {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
        }
        
        .form-header {
            margin-bottom: 15px;
            font-weight: 600;
            color: var(--primary-color);
            font-size: 18px;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(82, 113, 255, 0.25);
        }
        
        .category-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .category-checkbox {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .category-checkbox input[type="checkbox"] {
            position: absolute;
            opacity: 0;
        }
        
        .category-checkbox label {
            padding: 8px 15px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 20px;
            cursor: pointer;
            width: 100%;
            text-align: center;
            transition: all 0.2s ease;
        }
        
        .category-checkbox input[type="checkbox"]:checked + label {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .media-preview {
            margin-top: 10px;
            text-align: center;
            display: none;
        }
        
        .media-preview img {
            max-height: 200px;
            border-radius: 8px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 8px 20px;
            font-weight: 500;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }
        
        /* Post Cards */
        .posts-container {
            margin-top: 20px;
        }
        
        .card {
            border: none;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: transform 0.2s ease;
        }
        
        .card:hover {
            transform: translateY(-3px);
        }
        
        .card-header {
            background-color: white;
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
        }
        
        .post-author {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .author-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .author-name {
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 0;
        }
        
        .post-date {
            font-size: 12px;
            color: #888;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .post-content {
            margin-bottom: 15px;
            line-height: 1.5;
        }
        
        .post-media {
            margin-bottom: 15px;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .post-media img, .post-media video {
            width: 100%;
        }
        
        .post-category {
            display: inline-block;
            padding: 4px 10px;
            background-color: #e9ecef;
            border-radius: 15px;
            font-size: 12px;
            margin-bottom: 15px;
        }
        
        .interaction-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .btn-outline-primary, .btn-outline-danger, .btn-outline-success {
            border-radius: 20px;
            font-size: 14px;
            padding: 5px 15px;
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .comment-form {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        
        
        .comment-input {
            flex: 1;
            border-radius: 20px;
            padding: 8px 15px;
            border: 1px solid #ddd;
        }
        
        .comment-submit {
            border-radius: 20px;
            padding: 5px 15px;
            background-color: var(--primary-color);
            color: white;
            border: none;
        }
        
        /* Chatbot Styles */
        #chatbot-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }
        
        #chatbot-toggle {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            cursor: pointer;
            box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        #chatbot-toggle i {
            font-size: 24px;
        }
        
        #chatbot-toggle:hover {
            transform: scale(1.1);
        }
        
        #chatbot-popup {
            display: none;
            width: 320px;
            height: 400px;
            background: white;
            border-radius: 15px;
            box-shadow: 0px 5px 20px rgba(0, 0, 0, 0.2);
            position: absolute;
            bottom: 70px;
            right: 0;
            padding: 15px;
            overflow: hidden;
            animation: slideUp 0.3s ease;
        }
        
        #chatbot-popup .chat-header {
            padding-bottom: 10px;
            margin-bottom: 10px;
            border-bottom: 1px solid #eee;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        #chat-messages {
            height: 280px;
            overflow-y: auto;
            padding: 10px 5px;
            margin-bottom: 10px;
        }
        
        .chat-input-container {
            display: flex;
            gap: 10px;
        }
        
        #chatbot-input {
            flex: 1;
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 20px;
        }
        
        #chatbot-send {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        
        .bot-message, .user-message {
            margin-bottom: 10px;
            max-width: 80%;
            padding: 10px 15px;
            border-radius: 15px;
        }
        
        .user-message {
            background-color: var(--primary-color);
            color: white;
            margin-left: auto;
            border-top-right-radius: 5px;
        }
        
        .bot-message {
            background-color: #f0f2f5;
            border-top-left-radius: 5px;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .toggle-sidebar {
                display: block;
            }
            
            .category-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 576px) {
            .category-container {
                grid-template-columns: 1fr;
            }
            
            .interaction-buttons {
                flex-wrap: wrap;
            }
        }
        
        /* Animation */
        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>
</head>
<body>
    <div class="layout-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="fas fa-comment-dots"></i> TwitsNotes
                </div>
                <div class="user-profile">
                    <img src="<?= !empty($user_data['profile_pic']) ? htmlspecialchars($user_data['profile_pic']) : 'assets/images/default-avatar.jpg'; ?>" 
                         class="profile-image" alt="Profile Image">
                    <div class="profile-name">@<?= htmlspecialchars($user_data['username'] ?? 'User'); ?></div>
                </div>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-item">
                    <a href="index.php" class="nav-link active">
                        <i class="fas fa-home"></i> Beranda
                    </a>
                </div>
                <div class="nav-item">
                    <a href="profile.php" class="nav-link">
                        <i class="fas fa-user"></i> Profil
                    </a>
                </div>
                <div class="nav-item">
                    <a href="fyp.php" class="nav-link">
                        <i class="fas fa-fire"></i> For You Page
                    </a>
                </div>
                <div class="nav-item">
                    <a href="search.php" class="nav-link">
                        <i class="fas fa-search"></i> Cari Konten
                    </a>
                </div>
                <div class="nav-item">
                    <a href="history.php" class="nav-link">
                        <i class="fas fa-history"></i> History
                    </a>
                </div>
                <div class="nav-item">
                    <a href="logout.php" class="nav-link logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">Beranda</h1>
            </div>

            <!-- Post Creation Form -->
            <div class="post-form">
                <h4 class="form-header">Buat Postingan Baru</h4>
                <form action="process_upload.php" method="POST" enctype="multipart/form-data" id="post-form">
                    <div class="mb-3">
                        <textarea name="content" class="form-control" placeholder="Apa yang sedang Anda pikirkan?" rows="3" required></textarea>
                    </div>
                    
                    <label class="form-label">Pilih Kategori:</label>
                    <div class="category-container">
                        <div class="category-checkbox">
                            <input class="form-check-input" type="checkbox" name="categories[]" value="Politik" id="politik">
                            <label for="politik">Politik</label>
                        </div>
                        <div class="category-checkbox">
                            <input class="form-check-input" type="checkbox" name="categories[]" value="Kesehatan" id="kesehatan">
                            <label for="kesehatan">Kesehatan</label>
                        </div>
                        <div class="category-checkbox">
                            <input class="form-check-input" type="checkbox" name="categories[]" value="Teknologi" id="teknologi">
                            <label for="teknologi">Teknologi</label>
                        </div>
                        <div class="category-checkbox">
                            <input class="form-check-input" type="checkbox" name="categories[]" value="Berita" id="berita">
                            <label for="berita">Berita</label>
                        </div>
                        <div class="category-checkbox">
                            <input class="form-check-input" type="checkbox" name="categories[]" value="Hukum dan Kriminal" id="hukum">
                            <label for="hukum">Hukum & Kriminal</label>
                        </div>
                        <div class="category-checkbox">
                            <input class="form-check-input" type="checkbox" name="categories[]" value="Kpop" id="kpop">
                            <label for="kpop">Kpop</label>
                        </div>
                        <div class="category-checkbox">
                            <input class="form-check-input" type="checkbox" name="categories[]" value="custom" id="other">
                            <label for="other">Lainnya</label>
                        </div>
                    </div>

                    <div class="mb-3" id="custom-category-container" style="display:none;">
                        <input type="text" id="customCategory" name="custom_category" class="form-control" placeholder="Masukkan kategori lain...">
                    </div>
                    
                    <div class="mb-3">
                        <label for="mediaUpload" class="form-label d-block">
                            <div class="btn btn-outline-primary w-100">
                                <i class="fas fa-image me-2"></i> Tambahkan Foto/Video
                            </div>
                            <input type="file" name="media" id="mediaUpload" accept="image/*,video/*" class="d-none">
                        </label>
                        <div class="media-preview" id="mediaPreview"></div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-paper-plane me-2"></i> Bagikan
                    </button>
                </form>
            </div>

            <!-- Posts -->
            <div class="posts-container">
                <h4 class="mb-3"><i class="fas fa-fire-alt me-2"></i> Konten Terpopuler</h4>
                <div class="row">
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <div class="post-author">
                                            <img src="<?= !empty($row['author_pic']) ? htmlspecialchars($row['author_pic']) : 'assets/images/default-avatar.jpg'; ?>" class="author-img" alt="Profile">
                                            <div>
                                                <p class="author-name">@<?= htmlspecialchars($row['username']); ?></p>
                                                <p class="post-date"><?= date('d M Y, H:i', strtotime($row['created_at'] ?? 'now')); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <p class="post-content"><?= htmlspecialchars($row['content']); ?></p>
                                        
                                        <?php if (!empty($row['media'])): ?>
                                            <div class="post-media">
                                                <?php $ext = strtolower(pathinfo($row['media'], PATHINFO_EXTENSION)); ?>
                                                <?php if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                                    <img src="uploads/<?= htmlspecialchars($row['media']); ?>" alt="Uploaded Image">
                                                <?php elseif (in_array($ext, ['mp4', 'mov', 'avi'])): ?>
                                                    <video controls class="w-100">
                                                        <source src="uploads/<?= htmlspecialchars($row['media']); ?>" type="video/<?= $ext; ?>">
                                                    </video>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <span class="post-category"># <?= htmlspecialchars($row['category']); ?></span>
                                        
                                        <div class="interaction-buttons">
                                            <button class="btn btn-outline-primary like-btn" data-id="<?= $row['id']; ?>">
                                                <i class="far fa-thumbs-up me-1"></i> Like (<span id="likes-<?= $row['id']; ?>"><?= $row['like_count']; ?></span>)
                                            </button>
                                            <button class="btn btn-outline-danger dislike-btn" data-id="<?= $row['id']; ?>">
                                                <i class="far fa-thumbs-down me-1"></i> Dislike (<span id="dislikes-<?= $row['id']; ?>"><?= $row['dislike_count']; ?></span>)
                                            </button>
                                            <button class="btn btn-outline-secondary not-interested-btn" data-id="<?= $row['id']; ?>">
                                                <i class="fas fa-times me-1"></i> Not Interested
                                            </button>
                                            <a href="comments_page.php?post_id=<?= $row['id']; ?>" class="btn btn-outline-success">
                                                <i class="far fa-comment me-1"></i> Komentar (<?= $row['comment_count']; ?>)
                                            </a>
                                        </div>
                                        
                                        <form action="submit_comment.php" method="POST" class="comment-form" id="comment-form-<?= $row['id']; ?>">
                                            <input type="hidden" name="post_id" value="<?= $row['id']; ?>">
                                            <input type="text" name="comment" class="comment-input" placeholder="Tambahkan komentar..." required>
                                            <button type="submit" class="comment-submit">
                                                <i class="fas fa-paper-plane"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle me-2"></i> Belum ada postingan. Jadilah yang pertama membuat postingan!
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Chatbot -->
    <div id="chatbot-container">
        <button id="chatbot-toggle">
            <i class="fas fa-comment-dots"></i>
        </button>
        <div id="chatbot-popup">
            <div class="chat-header">
                <i class="fas fa-robot me-2"></i> Chatbot Asisten
            </div>
            <div id="chat-messages"></div>
            <div class="chat-input-container">
                <input type="text" id="chatbot-input" placeholder="Ketik pesan..." />
                <button id="chatbot-send">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function () {
    // Sidebar toggle for mobile
    $(".toggle-sidebar").click(function () {
        $(".sidebar").toggleClass("active");
    });

  // Like/Dislike functionality
$(".like-btn, .dislike-btn").click(function () {
    const postId = $(this).data("id");
    const type = $(this).hasClass("like-btn") ? "like" : "dislike";
    const likeCountElement = $(`#likes-${postId}`);
    const dislikeCountElement = $(`#dislikes-${postId}`);

    // Increment counter langsung untuk respons UI yang cepat
    if (type === "like") {
        const currentLikes = parseInt(likeCountElement.text());
        likeCountElement.text(currentLikes + 1);
    } else {
        const currentDislikes = parseInt(dislikeCountElement.text());
        dislikeCountElement.text(currentDislikes + 1);
    }

    // Kirim request ke server
    $.ajax({
        url: "like.php",
        type: "POST",
        data: { post_id: postId, type: type },
        dataType: "json",
        success: function(response) {
            if (response.success) {
                // Update counter dengan nilai dari server
                likeCountElement.text(response.likes);
                dislikeCountElement.text(response.dislikes);
            } else {
                // Jika gagal, tampilkan pesan error
                console.error("Server error:", response.error || "Unknown error");
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX error:", status, error);
        }
    });
    
    // Tambahkan efek visual saat klik
    const button = $(this);
    button.addClass('active');
    setTimeout(function() {
        button.removeClass('active');
    }, 200);
});


// Not Interested functionality
$(".not-interested-btn").click(function() {
    const postId = $(this).data("id");
    const postCard = $(this).closest(".col-md-6");
    
    $.ajax({
        url: "not_interested.php",
        type: "POST",
        data: { post_id: postId },
        dataType: "json",
        success: function(response) {
            if (response.success) {
                // Hide the post with animation
                postCard.fadeOut(300, function() {
                    $(this).remove();
                });
            } else {
                console.error("Server error:", response.error || "Unknown error");
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX error:", status, error);
        }
    });
});

    // Custom category toggle
    $("#other").change(function () {
        $("#custom-category-container").slideToggle($(this).is(":checked"));
    });

    // Media upload preview
    $("#mediaUpload").change(function () {
        const file = this.files[0];
        const mediaPreview = $("#mediaPreview");

        if (file) {
            const reader = new FileReader();
            const fileType = file.type.split("/")[0];

            reader.onload = function (e) {
                mediaPreview.empty().show();
                if (fileType === "image") {
                    mediaPreview.html(`<img src="${e.target.result}" alt="Preview">`);
                } else if (fileType === "video") {
                    mediaPreview.html(`<video controls><source src="${e.target.result}" type="${file.type}"></video>`);
                }
            };

            reader.readAsDataURL(file);
        } else {
            mediaPreview.empty().hide();
        }
    });


    // Comment form submission
$(".comment-form").submit(function(e) {
    e.preventDefault(); // Prevent the form from submitting normally
    
    const form = $(this);
    const postId = form.find("input[name='post_id']").val();
    const comment = form.find("input[name='comment']").val();
    
    if (!comment.trim()) {
        return;
    }
    
    $.ajax({
        url: "submit_comment.php",
        type: "POST",
        data: { post_id: postId, comment: comment },
        dataType: "json",
        success: function(response) {
            if (response.success) {
                // Clear the comment input
                form.find("input[name='comment']").val("");
                
                // Update comment count
                const commentBtn = form.closest(".card-body").find("a.btn-outline-success");
                const currentCount = parseInt(commentBtn.text().match(/\d+/)[0]);
                commentBtn.html(`<i class="far fa-comment me-1"></i> Komentar (${currentCount + 1})`);
                
                // Show success message if needed
                // alert("Komentar berhasil ditambahkan");
            } else {
                console.error("Server error:", response.error || "Unknown error");
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX error:", status, error);
        }
    });
});

    // Chatbot functionality
    $("#chatbot-toggle").click(function () {
        $("#chatbot-popup").toggle();
        if ($("#chatbot-popup").is(":visible")) {
            $("#chatbot-input").focus();
        }
    });

    $("#chatbot-send").click(sendChatMessage);
    $("#chatbot-input").keypress(function (e) {
        if (e.which === 13) {
            sendChatMessage();
        }
    });

    function sendChatMessage() {
        const message = $("#chatbot-input").val().trim();

        if (!message) {
            return;
        }

        appendUserMessage(message);
        $("#chatbot-input").val("");

        $.ajax({
            url: "chatbot.php",
            type: "POST",
            data: { message: message },
            dataType: "json",
            success: function (response) {
                if (!response || typeof response !== "object") {
                    appendBotMessage("Terjadi kesalahan dalam respons server.");
                } else {
                    const botMessage = response.response || "Maaf, saya tidak mengerti pertanyaan Anda.";
                    appendBotMessage(botMessage);
                }
            },
            error: function () {
                appendBotMessage("Terjadi kesalahan saat menghubungi server.");
            },
        });
    }

    function appendUserMessage(message) {
        $("#chat-messages").append(`<div class="user-message">${message}</div>`);
        scrollChatToBottom();
    }

    function appendBotMessage(message) {
        $("#chat-messages").append(`<div class="bot-message">${message}</div>`);
        scrollChatToBottom();
    }

    function scrollChatToBottom() {
        const chatMessages = $("#chat-messages");
        chatMessages.scrollTop(chatMessages[0].scrollHeight);
    }
});
    </script>
</body>
</html>