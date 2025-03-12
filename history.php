<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=history.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil username untuk personalisasi
$user_query = $conn->prepare("SELECT username FROM users WHERE id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user_result = $user_query->get_result();
$username = $user_result->fetch_assoc()['username'];

// Hapus history yang lebih dari 30 hari
$conn->query("DELETE FROM history WHERE watched_at < NOW() - INTERVAL 30 DAY");

// Filter dan pengurutan
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Bangun query dasar
$base_query = "
    SELECT posts.id, posts.content, posts.media, posts.category, posts.created_at, 
           history.watched_at,
           users.username as creator_name,
           (SELECT COUNT(*) FROM views_count WHERE views_count.post_id = posts.id) AS view_count
    FROM history 
    JOIN posts ON history.post_id = posts.id 
    JOIN users ON posts.user_id = users.id
    WHERE history.user_id = ? 
";

// Tambahkan filter jika ada
if ($filter !== 'all') {
    if ($filter === 'videos') {
        $base_query .= " AND posts.media LIKE '%.mp4'";
    } elseif ($filter === 'images') {
        $base_query .= " AND (posts.media LIKE '%.jpg' OR posts.media LIKE '%.png' OR posts.media LIKE '%.gif')";
    } elseif ($filter === 'completed') {
        // Jika Anda ingin menambahkan filter "completed" di masa depan, Anda perlu menambahkan kolom `completed` ke tabel `history`.
        $base_query .= " AND 1=0"; // Sementara, filter ini tidak akan mengembalikan hasil apa pun.
    } elseif (is_numeric($filter)) {
        // Filter berdasarkan kategori (jika Anda memiliki ID kategori)
        $base_query .= " AND posts.category = '" . $conn->real_escape_string($filter) . "'";
    }
}

// Tambahkan pengurutan
if ($sort === 'newest') {
    $base_query .= " ORDER BY history.watched_at DESC";
} elseif ($sort === 'oldest') {
    $base_query .= " ORDER BY history.watched_at ASC";
} elseif ($sort === 'popular') {
    $base_query .= " ORDER BY view_count DESC";
}

// Siapkan query dengan parameter
$query = $conn->prepare($base_query);
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();

// Ambil kategori unik dari tabel posts
$category_query = $conn->query("SELECT DISTINCT category FROM posts ORDER BY category");
$categories = [];
while ($cat = $category_query->fetch_assoc()) {
    $categories[] = $cat['category'];
}

// Statistik tontonan
$stats_query = $conn->prepare("
    SELECT 
        COUNT(*) AS total_watches,
        COUNT(DISTINCT post_id) AS unique_content,
        MAX(watched_at) AS last_watch
    FROM history 
    WHERE user_id = ?
");
$stats_query->bind_param("i", $user_id);
$stats_query->execute();
$stats = $stats_query->get_result()->fetch_assoc();

// Format durasi total dari detik ke format jam:menit:detik
$total_hours = 0; // Default value since watch_duration is not available
$total_minutes = 0;
$total_seconds = 0;
$formatted_duration = sprintf("%02d:%02d:%02d", $total_hours, $total_minutes, $total_seconds);

// Ambil rekomendasi berdasarkan history
$recommendation_query = $conn->prepare("
    SELECT DISTINCT p.id, p.content, p.media, p.created_at, 
           (SELECT COUNT(*) FROM views_count WHERE views_count.post_id = p.id) AS view_count, 
           u.username as creator_name
    FROM posts p
    JOIN users u ON p.user_id = u.id
    WHERE p.category IN (
        SELECT DISTINCT posts.category 
        FROM history 
        JOIN posts ON history.post_id = posts.id 
        WHERE history.user_id = ?
    )
    AND p.id NOT IN (
        SELECT post_id FROM history WHERE user_id = ?
    )
    ORDER BY view_count DESC
    LIMIT 3
");
$recommendation_query->bind_param("ii", $user_id, $user_id);
$recommendation_query->execute();
$recommendations = $recommendation_query->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Tontonan Saya - StreamKita</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --primary-color: #8e44ad;
            --secondary-color: #9b59b6;
            --accent-color: #e74c3c;
            --text-color: #333;
            --bg-color: #f5f5f5;
            --card-bg: #fff;
            --border-radius: 12px;
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }

        header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem 0;
            text-align: center;
            border-bottom-left-radius: 30px;
            border-bottom-right-radius: 30px;
            box-shadow: var(--box-shadow);
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="rgba(255,255,255,0.1)"/></svg>');
            background-size: cover;
        }

        .stats-container {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            max-width: 800px;
            margin: 1rem auto;
        }

        .stat-card {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 1rem;
            margin: 0.5rem;
            flex: 1 1 200px;
            text-align: center;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .stat-card i {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .stat-number {
            font-size: 1.8rem;
            font-weight: bold;
            margin: 0.5rem 0;
            color: var(--accent-color);
        }

        .stat-label {
            font-size: 0.9rem;
            color: #666;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .page-title {
            font-size: 2.5rem;
            margin: 0;
            position: relative;
            z-index: 1;
        }

        .welcome-text {
            margin-bottom: 1rem;
            opacity: 0.9;
            font-weight: 300;
            position: relative;
            z-index: 1;
        }

        .filter-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin: 1.5rem 0;
            padding: 1rem;
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .filter-group {
            display: flex;
            align-items: center;
            margin: 0.5rem 0;
        }

        .filter-group label {
            margin-right: 0.5rem;
            font-weight: 500;
        }

        select {
            padding: 0.5rem 1rem;
            border: 1px solid #ddd;
            border-radius: 20px;
            background-color: white;
            outline: none;
            cursor: pointer;
            transition: var(--transition);
        }

        select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(142, 68, 173, 0.2);
        }

        .history-list {
            list-style: none;
            padding: 0;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .history-item {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            position: relative;
        }

        .history-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .history-media-container {
            position: relative;
            width: 100%;
            padding-top: 56.25%; /* 16:9 Aspect Ratio */
            overflow: hidden;
            background-color: #eee;
        }

        .history-media {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .history-item:hover .history-media {
            transform: scale(1.05);
        }

        .history-content {
            padding: 1rem;
        }

        .history-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0 0 0.5rem 0;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .history-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #777;
            font-size: 0.85rem;
            margin-top: 0.75rem;
        }

        .creator-info {
            display: flex;
            align-items: center;
        }

        .creator-info i {
            margin-right: 0.3rem;
            color: var(--primary-color);
        }

        .category-badge {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            background-color: rgba(142, 68, 173, 0.1);
            color: var(--primary-color);
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            margin-right: 0.5rem;
        }

        .watch-info {
            margin-top: 0.5rem;
            font-size: 0.8rem;
            color: #888;
        }

        .watch-progress {
            height: 4px;
            width: 100%;
            background-color: #eee;
            border-radius: 2px;
            margin-top: 0.5rem;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            border-radius: 2px;
        }

        .completed-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: var(--accent-color);
            color: white;
            padding: 0.3rem 0.6rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 500;
            z-index: 2;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .empty-state i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 1rem;
            display: block;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: #666;
        }

        .empty-state p {
            color: #888;
            max-width: 400px;
            margin: 0 auto;
        }

        .recommendations-section {
            margin-top: 3rem;
            padding: 1.5rem;
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .section-title {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
        }

        .section-title i {
            margin-right: 0.5rem;
            color: var(--primary-color);
        }

        .recommendations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .recommendation-item {
            background-color: rgba(142, 68, 173, 0.05);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
        }

        .recommendation-item:hover {
            transform: translateY(-5px);
            background-color: rgba(142, 68, 173, 0.1);
        }

        .go-back-btn {
            display: inline-flex;
            align-items: center;
            padding: 0.7rem 1.5rem;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 25px;
            font-weight: 500;
            text-decoration: none;
            margin: 2rem 0;
            transition: var(--transition);
            box-shadow: 0 4px 8px rgba(142, 68, 173, 0.3);
        }

        .go-back-btn:hover {
            background-color: #7d2da3;
            box-shadow: 0 6px 12px rgba(142, 68, 173, 0.4);
        }

        .go-back-btn i {
            margin-right: 0.5rem;
        }

        @media (max-width: 768px) {
            .history-list {
                grid-template-columns: 1fr;
            }

            .filter-section {
                flex-direction: column;
                align-items: flex-start;
            }

            .filter-group {
                width: 100%;
                margin-bottom: 0.8rem;
            }

            select {
                width: 100%;
            }

            .stats-container {
                flex-direction: column;
            }

            .stat-card {
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1 class="page-title">Riwayat Tontonan Saya</h1>
            <p class="welcome-text">Halo, <?= htmlspecialchars($username); ?>! Berikut adalah aktivitas tontonan Anda dalam 30 hari terakhir.</p>
        </div>
    </header>

    <div class="container">
        <div class="stats-container">
            <div class="stat-card">
                <i class="fas fa-play-circle"></i>
                <div class="stat-number"><?= $stats['total_watches']; ?></div>
                <div class="stat-label">Total Tontonan</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-clock"></i>
                <div class="stat-number"><?= $formatted_duration; ?></div>
                <div class="stat-label">Waktu Menonton</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-film"></i>
                <div class="stat-number"><?= $stats['unique_content']; ?></div>
                <div class="stat-label">Konten Unik</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-calendar-alt"></i>
                <div class="stat-number"><?= date('d/m/Y', strtotime($stats['last_watch'])); ?></div>
                <div class="stat-label">Terakhir Ditonton</div>
            </div>
        </div>

        <div class="filter-section">
            <div class="filter-group">
                <label for="filter-type"><i class="fas fa-filter"></i> Filter:</label>
                <select id="filter-type" onchange="window.location.href='history.php?filter='+this.value+'&sort=<?= $sort; ?>'">
                    <option value="all" <?= $filter === 'all' ? 'selected' : ''; ?>>Semua Konten</option>
                    <option value="videos" <?= $filter === 'videos' ? 'selected' : ''; ?>>Video Saja</option>
                    <option value="images" <?= $filter === 'images' ? 'selected' : ''; ?>>Gambar Saja</option>
                    <option value="completed" <?= $filter === 'completed' ? 'selected' : ''; ?>>Selesai Ditonton</option>
                    <optgroup label="Kategori">
                        <?php foreach ($categories as $id => $name): ?>
                            <option value="<?= $id; ?>" <?= $filter == $id ? 'selected' : ''; ?>><?= htmlspecialchars($name); ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="sort-type"><i class="fas fa-sort"></i> Urutkan:</label>
                <select id="sort-type" onchange="window.location.href='history.php?filter=<?= $filter; ?>&sort='+this.value">
                    <option value="newest" <?= $sort === 'newest' ? 'selected' : ''; ?>>Terbaru</option>
                    <option value="oldest" <?= $sort === 'oldest' ? 'selected' : ''; ?>>Terlama</option>
                    <option value="duration" <?= $sort === 'duration' ? 'selected' : ''; ?>>Durasi Tonton</option>
                    <option value="popular" <?= $sort === 'popular' ? 'selected' : ''; ?>>Popularitas</option>
                </select>
            </div>
        </div>

        <?php if ($result->num_rows > 0): ?>
            <ul class="history-list">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <?php 
                        $progress = isset($row['watch_duration']) ? min(100, round(($row['watch_duration'] / 300) * 100)) : 0; // Asumsi video rata-rata 5 menit
                        $watched_time = new DateTime($row['watched_at']); 
                        $time_diff = $watched_time->diff(new DateTime());
                        
                        if ($time_diff->days > 0) {
                            $time_ago = $time_diff->days . " hari yang lalu";
                        } elseif ($time_diff->h > 0) {
                            $time_ago = $time_diff->h . " jam yang lalu";
                        } else {
                            $time_ago = $time_diff->i . " menit yang lalu";
                        }
                    ?>
                    <li class="history-item">
                        <?php if (isset($row['completed']) && $row['completed']): ?>
                            <div class="completed-badge">
                                <i class="fas fa-check"></i> Selesai
                            </div>
                        <?php endif; ?>
                        
                        <div class="history-media-container">
                            <?php if (!empty($row['media'])): ?>
                                <?php if (strpos($row['media'], '.mp4') !== false): ?>
                                    <video class="history-media">
                                        <source src="uploads/<?= htmlspecialchars($row['media']); ?>" type="video/mp4">
                                    </video>
                                    <div class="play-overlay">
                                        <i class="fas fa-play"></i>
                                    </div>
                                <?php else: ?>
                                    <img src="uploads/<?= htmlspecialchars($row['media']); ?>" class="history-media" alt="<?= htmlspecialchars(substr($row['content'], 0, 50)); ?>">
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="history-media placeholder-media">
                                    <i class="fas fa-photo-video"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="history-content">
                            <h3 class="history-title"><?= htmlspecialchars($row['content']); ?></h3>
                            
                            <div class="category-badge">
                                <?= isset($categories[$row['category']]) ? htmlspecialchars($categories[$row['category']]) : 'Umum'; ?>
                            </div>
                            
                            <div class="watch-info">
                                <span><i class="fas fa-eye"></i> Ditonton <?= $time_ago; ?></span>
                            </div>
                            
                            <?php if (strpos($row['media'], '.mp4') !== false): ?>
                                <div class="watch-progress">
                                    <div class="progress-bar" style="width: <?= $progress; ?>%"></div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="history-meta">
                                <div class="creator-info">
                                    <i class="fas fa-user-circle"></i>
                                    <span><?= htmlspecialchars($row['creator_name']); ?></span>
                                </div>
                                <a href="post.php?id=<?= $row['id']; ?>" class="watch-again-btn">
                                    <i class="fas fa-redo"></i>
                                </a>
                            </div>
                        </div>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-history"></i>
                <h3>Belum Ada Riwayat Tontonan</h3>
                <p>Mulailah menonton konten untuk melihat riwayat tontonan Anda di sini. Kami hanya menyimpan riwayat dari 30 hari terakhir.</p>
            </div>
        <?php endif; ?>

        <?php if ($recommendations && $recommendations->num_rows > 0): ?>
            <div class="recommendations-section">
                <h2 class="section-title"><i class="fas fa-lightbulb"></i> Rekomendasi Untuk Anda</h2>
                <div class="recommendations-grid">
                    <?php while ($rec = $recommendations->fetch_assoc()): ?>
                        <div class="recommendation-item">
                            <div class="history-media-container">
                                <?php if (!empty($rec['media'])): ?>
                                    <?php if (strpos($rec['media'], '.mp4') !== false): ?>
                                        <video class="history-media">
                                            <source src="uploads/<?= htmlspecialchars($rec['media']); ?>" type="video/mp4">
                                        </video>
                                        <div class="play-overlay">
                                            <i class="fas fa-play"></i>
                                        </div>
                                    <?php else: ?>
                                        <img src="uploads/<?= htmlspecialchars($rec['media']); ?>" class="history-media" alt="<?= htmlspecialchars(substr($rec['content'], 0, 50)); ?>">
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            
                            <div class="history-content">
                                <h3 class="history-title"><?= htmlspecialchars($rec['content']); ?></h3>
                                <div class="history-meta">
                                    <div class="creator-info">
                                        <i class="fas fa-user-circle"></i>
                                        <span><?= htmlspecialchars($rec['creator_name']); ?></span>
                                    </div>
                                    <span><i class="fas fa-eye"></i> <?= number_format($rec['view_count']); ?></span>
                                </div>
                                <a href="post.php?id=<?= $rec['id']; ?>" class="go-to-post">Tonton Sekarang</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endif; ?>

        <a href="index.php" class="go-back-btn">
            <i class="fas fa-arrow-left"></i> Kembali ke Beranda
        </a>
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