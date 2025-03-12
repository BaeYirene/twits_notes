<?php
session_start();
require 'db.php';

// Ensure database connection is available
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

$search_query = "";
$search_results = [];
$user_results = [];
$popular_topics = [];

// If there's a search query
if (isset($_GET['q']) && !empty($_GET['q'])) {
    $search_query = mysqli_real_escape_string($conn, $_GET['q']);

    // Query to search posts by content or username
    $query = "
        SELECT posts.*, 
            users.username, users.profile_pic,
            (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id AND type = 'like') AS like_count,
            (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id AND type = 'dislike') AS dislike_count,
            (SELECT COUNT(*) FROM comments WHERE comments.post_id = posts.id) AS comment_count,
            (SELECT COUNT(*) FROM views WHERE views.post_id = posts.id) AS view_count
        FROM posts
        LEFT JOIN users ON posts.user_id = users.id
        WHERE posts.content LIKE '%$search_query%' 
            OR users.username LIKE '%$search_query%'
        ORDER BY posts.created_at DESC
        LIMIT 30";

    $result = $conn->query($query);
    if ($result) {
        $search_results = $result->fetch_all(MYSQLI_ASSOC);
    }

    // Query to search users
    $users_query = "
        SELECT id, username, profile_pic, bio,
            (SELECT COUNT(*) FROM posts WHERE posts.user_id = users.id) AS post_count,
            (SELECT COUNT(*) FROM follows WHERE follows.following_id = users.id) AS follower_count
        FROM users
        WHERE username LIKE '%$search_query%'
            OR bio LIKE '%$search_query%'
        ORDER BY follower_count DESC
        LIMIT 10";

    $users_result = $conn->query($users_query);
    $user_results = $users_result ? $users_result->fetch_all(MYSQLI_ASSOC) : [];
}

// Query to fetch popular hashtags
$topics_query = "
    SELECT h.name, COUNT(*) as count
    FROM post_hashtags ph
    JOIN hashtags h ON ph.hashtag_id = h.id
    GROUP BY h.name
    ORDER BY count DESC
    LIMIT 10";
$topics_result = $conn->query($topics_query);
$popular_topics = $topics_result ? $topics_result->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Search - Twits Notes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
         :root {
            --primary-color: #fe2c55;
            --secondary-color: #25f4ee;
            --background-dark: #121212;
            --background-light: #1e1e1e;
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
            overflow-x: hidden;
            min-height: 100vh;
        }
        
        .header {
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 100;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 10px 15px;
            background-color: var(--background-dark);
            border-bottom: 1px solid var(--border-color);
        }
        
        .back-button {
            position: absolute;
            left: 15px;
            color: var(--text-light);
            text-decoration: none;
            font-size: 20px;
        }
        
        .search-container {
            width: 100%;
            max-width: 600px;
            padding: 0 10px;
            margin-left: 30px;
        }
        
        .search-wrapper {
            position: relative;
            width: 100%;
        }
        
        .search-input {
            width: 100%;
            padding: 10px 40px 10px 40px;
            border-radius: 25px;
            border: none;
            background-color: var(--background-light);
            color: var(--text-light);
            font-size: 14px;
            outline: none;
        }
        
        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
        }
        
        .clear-search {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            background: none;
            border: none;
            cursor: pointer;
            display: none;
        }
        
        .search-input:focus + .search-icon,
        .search-input:not(:placeholder-shown) + .search-icon {
            color: var(--primary-color);
        }
        
        .search-input:not(:placeholder-shown) ~ .clear-search {
            display: block;
        }
        
        .content {
            margin-top: 70px;
            padding: 10px 15px;
            padding-bottom: 60px; /* Space for nav-tabs */
        }
        
        .section-title {
            font-size: 16px;
            font-weight: bold;
            margin: 15px 0 10px;
            color: var(--text-light);
        }
        
        .topics-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .topic-item {
            background-color: var(--background-light);
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            color: var(--text-light);
            display: flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
        }
        
        .topic-item:hover {
            background-color: #333;
        }
        
        .topic-item i {
            color: var(--primary-color);
            font-size: 12px;
        }
        
        .users-container {
            margin-bottom: 20px;
        }
        
        .user-item {
            display: flex;
            align-items: center;
            padding: 12px 0;
            text-decoration: none;
            color: var(--text-light);
            border-bottom: 1px solid var(--border-color);
        }
        
        .user-profile {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
            border: 2px solid var(--primary-color);
        }
        
        .user-info {
            flex: 1;
        }
        
        .username {
            font-weight: bold;
            font-size: 15px;
            display: block;
            margin-bottom: 3px;
        }
        
        .user-bio {
            font-size: 13px;
            color: var(--text-secondary);
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .user-stats {
            display: flex;
            gap: 10px;
            font-size: 12px;
            margin-top: 5px;
            color: var(--text-secondary);
        }
        
        .follow-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 6px 15px;
            border-radius: 3px;
            font-size: 14px;
            cursor: pointer;
        }
        
        .videos-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2px;
        }
        
        .video-thumbnail {
            aspect-ratio: 9/16;
            position: relative;
            overflow: hidden;
        }
        
        .video-thumbnail img,
        .video-thumbnail video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .video-stats {
            position: absolute;
            bottom: 5px;
            left: 5px;
            font-size: 12px;
            color: var(--text-light);
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .video-stats i {
            font-size: 14px;
        }
        
        .nav-tabs {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            display: flex;
            justify-content: space-around;
            padding: 10px 0;
            background-color: var(--background-dark);
            border-top: 1px solid var(--border-color);
            z-index: 40;
        }
        
        .nav-tab {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 20px;
            padding: 5px 15px;
        }
        
        .nav-tab.active {
            color: var(--primary-color);
        }
        
        .no-results {
            text-align: center;
            padding: 30px 0;
            color: var(--text-secondary);
        }
        
        .search-tabs {
            display: flex;
            gap: 20px;
            margin: 15px 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .search-tab {
            padding: 10px 5px;
            position: relative;
            color: var(--text-secondary);
            cursor: pointer;
        }
        
        .search-tab.active {
            color: var(--text-light);
            font-weight: bold;
        }
        
        .search-tab.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: var(--primary-color);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .loader {
            text-align: center;
            padding: 20px;
            color: var(--text-secondary);
        }
        
        .spinner {
            display: inline-block;
            width: 25px;
            height: 25px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: var(--primary-color);
            animation: spin 1s linear infinite;
            margin-right: 10px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        @media (max-width: 480px) {
            .videos-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="fyp.php" class="back-button">
            <i class="fas fa-arrow-left"></i>
        </a>
        
        <div class="search-container">
            <form action="search.php" method="GET">
                <div class="search-wrapper">
                    <input type="text" name="q" id="search-input" class="search-input" placeholder="Cari video, pengguna atau hashtag..." value="<?= htmlspecialchars($search_query); ?>" autocomplete="off">
                    <i class="fas fa-search search-icon"></i>
                    <button type="button" class="clear-search" id="clear-search">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="content">
        <?php if (empty($search_query)): ?>
            <!-- Tampilkan topik populer jika belum ada pencarian -->
            <div class="section-title">Populer</div>
            <div class="topics-container">
                <?php 
                if (!empty($popular_topics)):
                    foreach ($popular_topics as $topic): 
                ?>
                    <a href="search.php?q=<?= urlencode($topic['name']); ?>" class="topic-item">
                        <i class="fas fa-hashtag"></i>
                        <?= htmlspecialchars($topic['name']); ?>
                    </a>
                <?php 
                    endforeach;
                else:
                ?>
                    <div class="topic-item">
                        <i class="fas fa-hashtag"></i>
                        trending
                    </div>
                    <div class="topic-item">
                        <i class="fas fa-hashtag"></i>
                        viral
                    </div>
                    <div class="topic-item">
                        <i class="fas fa-hashtag"></i>
                        comedy
                    </div>
                    <div class="topic-item">
                        <i class="fas fa-hashtag"></i>
                        music
                    </div>
                    <div class="topic-item">
                        <i class="fas fa-hashtag"></i>
                        dance
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="section-title">Pencarian Terbaru</div>
            <!-- Tampilkan riwayat pencarian -->
            <div id="search-history">
                <!-- Akan diisi oleh JavaScript -->
            </div>
        <?php else: ?>
            <!-- Hasil pencarian -->
            <div class="search-tabs">
                <div class="search-tab active" data-tab="all">Semua</div>
                <div class="search-tab" data-tab="videos">Video</div>
                <div class="search-tab" data-tab="users">Pengguna</div>
                <div class="search-tab" data-tab="hashtags">Hashtag</div>
            </div>
            
            <!-- Tab All -->
            <div class="tab-content active" id="tab-all">
                <?php if (!empty($user_results)): ?>
                    <div class="section-title">Pengguna</div>
                    <div class="users-container">
                        <?php foreach ($user_results as $user): ?>
                            <a href="profile.php?id=<?= $user['id']; ?>" class="user-item">
                                <img src="uploads/profiles/<?= !empty($user['profile_pic']) ? htmlspecialchars($user['profile_pic']) : 'default-profile.jpg'; ?>" 
                                     class="user-profile" alt="Profile">
                                <div class="user-info">
                                    <span class="username">@<?= htmlspecialchars($user['username']); ?></span>
                                    <span class="user-bio"><?= htmlspecialchars(substr($user['bio'] ?? '', 0, 50)); ?></span>
                                    <div class="user-stats">
                                        <span><i class="fas fa-video"></i> <?= $user['post_count']; ?> videos</span>
                                        <span><i class="fas fa-user-friends"></i> <?= $user['follower_count']; ?> followers</span>
                                    </div>
                                </div>
                                <button class="follow-btn">Follow</button>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($search_results)): ?>
                    <div class="section-title">Video</div>
                    <div class="videos-grid">
                        <?php foreach ($search_results as $video): ?>
                            <a href="video.php?id=<?= $video['id']; ?>" class="video-thumbnail">
                                <video muted>
                                    <source src="uploads/<?= htmlspecialchars($video['media']); ?>" type="video/mp4">
                                </video>
                                <div class="video-stats">
                                    <i class="fas fa-play"></i> <?= $video['view_count']; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (empty($user_results) && empty($search_results)): ?>
                    <div class="no-results">
                        <i class="fas fa-search" style="font-size: 40px; margin-bottom: 15px;"></i>
                        <p>Tidak ada hasil ditemukan untuk "<?= htmlspecialchars($search_query); ?>"</p>
                        <p style="font-size: 14px; color: var(--text-secondary); margin-top: 10px;">
                            Coba kata kunci lain atau cari dengan hashtag
                        </p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Tab Videos -->
            <div class="tab-content" id="tab-videos">
                <?php if (!empty($search_results)): ?>
                    <div class="videos-grid">
                        <?php foreach ($search_results as $video): ?>
                            <a href="fyp.php?id=<?= $video['id']; ?>" class="video-thumbnail">
                                <video muted>
                                    <source src="uploads/<?= htmlspecialchars($video['media']); ?>" type="video/mp4">
                                </video>
                                <div class="video-stats">
                                    <i class="fas fa-play"></i> <?= $video['view_count']; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-results">
                        <i class="fas fa-video" style="font-size: 40px; margin-bottom: 15px;"></i>
                        <p>Tidak ada video ditemukan</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Tab Users -->
            <div class="tab-content" id="tab-users">
                <?php if (!empty($user_results)): ?>
                    <div class="users-container">
                        <?php foreach ($user_results as $user): ?>
                            <a href="profile.php?id=<?= $user['id']; ?>" class="user-item">
                                <img src="uploads/profiles/<?= !empty($user['profile_pic']) ? htmlspecialchars($user['profile_pic']) : 'default-profile.jpg'; ?>" 
                                     class="user-profile" alt="Profile">
                                <div class="user-info">
                                    <span class="username">@<?= htmlspecialchars($user['username']); ?></span>
                                    <span class="user-bio"><?= htmlspecialchars(substr($user['bio'] ?? '', 0, 50)); ?></span>
                                    <div class="user-stats">
                                        <span><i class="fas fa-video"></i> <?= $user['post_count']; ?> videos</span>
                                        <span><i class="fas fa-user-friends"></i> <?= $user['follower_count']; ?> followers</span>
                                    </div>
                                </div>
                                <button class="follow-btn">Follow</button>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-results">
                        <i class="fas fa-user" style="font-size: 40px; margin-bottom: 15px;"></i>
                        <p>Tidak ada pengguna ditemukan</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Tab Hashtags -->
            <div class="tab-content" id="tab-hashtags">
                <div class="topics-container">
                    <?php 
                    // Filter topics sesuai query
                    $related_hashtags = [];
                    if (!empty($popular_topics)) {
                        foreach ($popular_topics as $topic) {
                            if (stripos($topic['name'], $search_query) !== false) {
                                $related_hashtags[] = $topic;
                            }
                        }
                    }
                    
                    if (!empty($related_hashtags)):
                        foreach ($related_hashtags as $topic): 
                    ?>
                        <a href="search.php?q=<?= urlencode($topic['name']); ?>" class="topic-item">
                            <i class="fas fa-hashtag"></i>
                            <?= htmlspecialchars($topic['name']); ?>
                            <span style="margin-left: 5px; font-size: 12px; color: var(--text-secondary);">
                                (<?= $topic['count']; ?>)
                            </span>
                        </a>
                    <?php 
                        endforeach;
                    else:
                    ?>
                        <div class="no-results">
                            <i class="fas fa-hashtag" style="font-size: 40px; margin-bottom: 15px;"></i>
                            <p>Tidak ada hashtag ditemukan</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="nav-tabs">
        <a href="fyp.php" class="nav-tab"><i class="fas fa-home"></i></a>
        <a href="search.php" class="nav-tab"><i class="fas fa-compass"></i></a>
        <a href="upload.php" class="nav-tab"><i class="fas fa-plus-square"></i></a>
        <a href="inbox.php" class="nav-tab"><i class="fas fa-inbox"></i></a>
        <a href="profile.php" class="nav-tab"><i class="fas fa-user"></i></a>
    </div>
    
    <script>
    $(document).ready(function () {
    // Initialize video thumbnails
    initVideoThumbnails();

    // Handle tab switching
    $('.search-tab').on('click', function () {
        const tabId = $(this).data('tab');
        $('.search-tab').removeClass('active');
        $(this).addClass('active');
        $('.tab-content').removeClass('active');
        $(`#tab-${tabId}`).addClass('active');
    });

    // Clear search input
    $('#clear-search').on('click', function () {
        $('#search-input').val('').focus();
        $(this).hide();
    });

    // Auto-submit form after typing
    let typingTimer;
    const doneTypingInterval = 500;

    $('#search-input').on('keyup', function () {
        clearTimeout(typingTimer);
        if ($(this).val()) {
            typingTimer = setTimeout(function () {
                $('form').submit();
            }, doneTypingInterval);
        }
    });

    // Save search history
    if ("<?= $search_query ?>") {
        saveSearchHistory("<?= htmlspecialchars($search_query) ?>");
    }

    // Display search history
    displaySearchHistory();

    // Initialize video hover
    initVideoThumbnailHover();

    // Follow button functionality
    $('.follow-btn').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        const $btn = $(this);
        $btn.toggleClass('following').text($btn.hasClass('following') ? 'Following' : 'Follow');
    });
});

// Initialize video thumbnails
function initVideoThumbnails() {
    $('.video-thumbnail video').each(function () {
        const video = this;
        video.addEventListener('loadeddata', function () {
            video.currentTime = 1.0;
        }, false);
        video.addEventListener('seeked', function () {
            video.pause();
        }, false);
    });
}

// Save search history to localStorage
function saveSearchHistory(query) {
    if (!query) return;
    let history = JSON.parse(localStorage.getItem('searchHistory') || '[]');
    history = history.filter(item => item !== query);
    history.unshift(query);
    if (history.length > 5) history = history.slice(0, 5);
    localStorage.setItem('searchHistory', JSON.stringify(history));
}

// Display search history
function displaySearchHistory() {
    const history = JSON.parse(localStorage.getItem('searchHistory') || '[]');
    const $container = $('#search-history');
    if (history.length === 0) {
        $container.html('<div class="no-results">Belum ada riwayat pencarian</div>');
        return;
    }
    let html = history.map(item => `
        <div class="user-item search-history-item">
            <i class="fas fa-history"></i>
            <div class="user-info">${htmlEscape(item)}</div>
            <button class="clear-history-item" data-query="${htmlEscape(item)}">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `).join('');
    $container.html(html);
}

// Remove item from search history
function removeSearchHistoryItem(query) {
    let history = JSON.parse(localStorage.getItem('searchHistory') || '[]');
    history = history.filter(item => item !== query);
    localStorage.setItem('searchHistory', JSON.stringify(history));
}

// Escape HTML to prevent XSS
function htmlEscape(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// Initialize video hover
function initVideoThumbnailHover() {
    $('.video-thumbnail').on('mouseenter', function () {
        const video = $(this).find('video')[0];
        if (video) video.play();
    }).on('mouseleave', function () {
        const video = $(this).find('video')[0];
        if (video) {
            video.pause();
            video.currentTime = 1.0;
        }
    });
}
    </script>
</body>
</html>