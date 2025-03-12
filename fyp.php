<?php
session_start();
require 'db.php';

// Pastikan koneksi tersedia
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Ambil video dengan durasi maksimal 3 menit dan interaksi terbanyak, diacak setiap refresh
$query = "
    SELECT posts.*, 
        users.username, users.profile_pic,
        (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id AND type = 'like') AS like_count,
        (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id AND type = 'dislike') AS dislike_count,
        (SELECT COUNT(*) FROM comments WHERE comments.post_id = posts.id) AS comment_count,
        (SELECT COUNT(*) FROM views_count WHERE views_count.post_id = posts.id) AS view_count
    FROM posts
    LEFT JOIN users ON posts.user_id = users.id
    WHERE posts.media LIKE '%.mp4' 
        AND posts.duration <= 180
    ORDER BY RAND()
    LIMIT 20";

$result = $conn->query($query);
$videos = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>FYP - Twits Notes</title>
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
            height: 100vh;
            position: relative;
            overscroll-behavior-y: contain; /* Mencegah refresh browser saat swipe */
        }
        
        .header {
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 100;
            display: flex;
            justify-content: center;
            padding: 10px;
            background: linear-gradient(to bottom, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0) 100%);
        }
        
        .header h2 {
            font-size: 20px;
            font-weight: bold;
            text-align: center;
        }
        
        .back-button {
            position: fixed;
            top: 10px;
            left: 10px;
            z-index: 101;
            color: var(--text-light);
            text-decoration: none;
            font-size: 24px;
        }
        
        .videos-container {
            height: 100vh;
            width: 100vw;
            position: relative;
            overflow: hidden;
            touch-action: pan-y; /* Memungkinkan interaksi swipe */
        }
        
        .video-item {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 1; /* Memastikan semua video memiliki z-index dasar */
        }
        
        .video-item.active {
            opacity: 1;
            z-index: 10;
        }
        
        .video-wrapper {
            position: relative;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }
        
        .fullscreen-video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .video-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 20px;
            background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0) 100%);
            z-index: 20;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .user-profile {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
            border: 2px solid var(--primary-color);
        }
        
        .username {
            font-weight: bold;
            font-size: 16px;
        }
        
        .content {
            margin-bottom: 15px;
            max-height: 80px;
            overflow-y: auto;
            font-size: 14px;
            line-height: 1.4;
        }
        
        .interaction-buttons {
            display: flex;
            flex-direction: column;
            position: absolute;
            right: 15px;
            bottom: 100px;
            z-index: 30;
            align-items: center;
        }
        
        .interaction-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .interaction-btn {
            background: none;
            border: none;
            color: var(--text-light);
            font-size: 24px;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 5px;
        }
        
        .interaction-count {
            font-size: 12px;
            color: var(--text-light);
        }
        
        .loader {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1000;
            display: none;
        }
        
        .loader .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: var(--primary-color);
            animation: spin 1s linear infinite;
        }
        
        .progress-bar {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            z-index: 50;
        }
        
        .progress {
            height: 100%;
            background-color: var(--primary-color);
            width: 0%;
        }
        
        .swipe-indicator {
            position: fixed;
            left: 50%;
            transform: translateX(-50%);
            bottom: 20px;
            color: var(--text-light);
            font-size: 12px;
            opacity: 0.7;
            text-align: center;
            z-index: 30;
        }
        
        .nav-tabs {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            display: flex;
            justify-content: space-around;
            padding: 10px 0;
            background-color: rgba(0, 0, 0, 0.7);
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
        
        /* Indikator play/pause */
        .play-indicator {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: rgba(0, 0, 0, 0.5);
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
            z-index: 25;
        }
        
        .play-indicator i {
            font-size: 30px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Animation for swipe indicator */
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {transform: translateY(0);}
            40% {transform: translateY(-10px);}
            60% {transform: translateY(-5px);}
        }
        
        .bounce {
            animation: bounce 2s infinite;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>For You</h2>
    </div>
    
    <a href="index.php" class="back-button">
        <i class="fas fa-arrow-left"></i>
    </a>
    
    <div class="videos-container" id="videos-container">
        <?php 
        $count = 0;
        foreach ($videos as $row): 
            $active_class = ($count == 0) ? "active" : "";
            $profile_pic = !empty($row['profile_pic']) ? $row['profile_pic'] : 'default-profile.jpg';
            $username = !empty($row['username']) ? $row['username'] : 'user_' . $row['user_id'];
        ?>
            <div class="video-item <?= $active_class; ?>" id="post-<?= $row['id']; ?>" data-id="<?= $row['id']; ?>">
                <div class="video-wrapper">
                    <video class="fullscreen-video" loop playsinline data-id="<?= $row['id']; ?>">
                        <source src="uploads/<?= htmlspecialchars($row['media']); ?>" type="video/mp4">
                    </video>
                    
                    <div class="play-indicator">
                        <i class="fas fa-play"></i>
                    </div>
                    
                    <div class="progress-bar">
                        <div class="progress" id="progress-<?= $row['id']; ?>"></div>
                    </div>
                    
                    <div class="video-overlay">
                        <div class="user-info">
                            <img src="uploads/profiles/<?= htmlspecialchars($profile_pic); ?>" class="user-profile" alt="Profile">
                            <span class="username">@<?= htmlspecialchars($username); ?></span>
                        </div>
                        <div class="content">
                            <?= htmlspecialchars($row['content']); ?>
                        </div>
                    </div>
                    
                    <div class="interaction-buttons">
                        <div class="interaction-item">
                            <button class="interaction-btn like-btn" data-id="<?= $row['id']; ?>">
                                <i class="fas fa-heart"></i>
                            </button>
                            <span class="interaction-count likes-count" id="likes-<?= $row['id']; ?>"><?= $row['like_count']; ?></span>
                        </div>
                        
                        <div class="interaction-item">
                            <button class="interaction-btn dislike-btn" data-id="<?= $row['id']; ?>">
                                <i class="fas fa-thumbs-down"></i>
                            </button>
                            <span class="interaction-count dislikes-count" id="dislikes-<?= $row['id']; ?>"><?= $row['dislike_count']; ?></span>
                        </div>
                        
                        <div class="interaction-item">
                            <a href="comments_page.php?post_id=<?= $row['id']; ?>" class="interaction-btn comment-btn">
                                <i class="fas fa-comment"></i>
                            </a>
                            <span class="interaction-count" id="comments-<?= $row['id']; ?>"><?= $row['comment_count']; ?></span>
                        </div>
                        
                        <div class="interaction-item">
                            <button class="interaction-btn share-btn" data-id="<?= $row['id']; ?>">
                                <i class="fas fa-share"></i>
                            </button>
                            <span class="interaction-count">Share</span>
                        </div>
                        
                        <div class="interaction-item">
                            <button class="interaction-btn not-interest-btn" data-id="<?= $row['id']; ?>">
                                <i class="fas fa-times"></i>
                            </button>
                            <span class="interaction-count">Not For You</span>
                        </div>
                    </div>
                </div>
            </div>
        <?php 
        $count++;
        endforeach; 
        ?>
    </div>
    
    <div class="swipe-indicator">
        <div class="bounce">
            <i class="fas fa-chevron-up"></i>
            <div>Swipe up for next video</div>
        </div>
    </div>
    
    <div class="nav-tabs">
        <a href="fyp.php" class="nav-tab active"><i class="fas fa-home"></i></a>
        <a href="search.php" class="nav-tab"><i class="fas fa-compass"></i></a>
        <a href="upload.php" class="nav-tab"><i class="fas fa-plus-square"></i></a>
        <a href="inbox.php" class="nav-tab"><i class="fas fa-inbox"></i></a>
        <a href="profile.php" class="nav-tab"><i class="fas fa-user"></i></a>
    </div>
    
    <div class="loader">
        <div class="spinner"></div>
    </div>

    <script>
$(document).ready(function() {
    let currentIndex = 0;
    const videoItems = $('.video-item');
    const totalVideos = videoItems.length;
    let isLoading = false;
    let touchStartY = 0;
    let touchEndY = 0;
    let videoProgress = {};
    
    // Inisialisasi video pertama dengan delay untuk memastikan DOM sudah siap
    setTimeout(function() {
        playCurrentVideo();
    }, 500);

    // Hide swipe indicator after a few seconds
    setTimeout(function() {
        $('.swipe-indicator').fadeOut();
    }, 5000);

    // Touch events for swiping - Ditambahkan option {passive: true} untuk kinerja lebih baik
    document.addEventListener('touchstart', function(e) {
        touchStartY = e.changedTouches[0].screenY;
    }, {passive: true});

    document.addEventListener('touchmove', function(e) {
        // Mencegah scrolling browser normal
        e.preventDefault();
    }, {passive: false});

    document.addEventListener('touchend', function(e) {
        touchEndY = e.changedTouches[0].screenY;
        handleSwipe();
    }, {passive: true});

    // Mouse wheel event for desktop
    $(document).on('wheel', function(e) {
        e.preventDefault(); // Mencegah scrolling default
        
        if (isLoading) return;

        if (e.originalEvent.deltaY > 0) {
            // Scrolling down, go to next video
            if (currentIndex < totalVideos - 1) {
                nextVideo();
            }
        } else if (e.originalEvent.deltaY < 0) {
            // Scrolling up, go to previous video
            if (currentIndex > 0) {
                prevVideo();
            }
        }
    });

    function handleSwipe() {
        if (isLoading) return;

        const swipeDistance = touchStartY - touchEndY;
        const minSwipeDistance = 50; // Minimum distance to consider it a swipe

        if (swipeDistance > minSwipeDistance) {
            // Swiping up - next video
            if (currentIndex < totalVideos - 1) {
                nextVideo();
            }
        } else if (swipeDistance < -minSwipeDistance) {
            // Swiping down - previous video
            if (currentIndex > 0) {
                prevVideo();
            }
        } else {
            // Tap - toggle play/pause
            togglePlayPause();
        }
    }

    function togglePlayPause() {
        const currentVideo = $(videoItems[currentIndex]).find('video')[0];
        const playIndicator = $(videoItems[currentIndex]).find('.play-indicator');

        if (currentVideo.paused) {
            currentVideo.play()
                .then(() => {
                    playIndicator.find('i').removeClass('fa-play').addClass('fa-pause');
                    playIndicator.css('opacity', '1');
                    setTimeout(() => playIndicator.css('opacity', '0'), 500);
                })
                .catch(error => {
                    console.error("Play error:", error);
                    alert("Video tidak dapat diputar: " + error.message);
                });
        } else {
            currentVideo.pause();
            playIndicator.find('i').removeClass('fa-pause').addClass('fa-play');
            playIndicator.css('opacity', '1');
            setTimeout(() => playIndicator.css('opacity', '0'), 500);
        }
    }

    function nextVideo() {
        pauseCurrentVideo();
        $(videoItems[currentIndex]).removeClass('active');
        currentIndex = (currentIndex + 1) % totalVideos;
        $(videoItems[currentIndex]).addClass('active');
        playCurrentVideo();
        showLoading();
    }

    function prevVideo() {
        pauseCurrentVideo();
        $(videoItems[currentIndex]).removeClass('active');
        currentIndex = (currentIndex - 1 + totalVideos) % totalVideos;
        $(videoItems[currentIndex]).addClass('active');
        playCurrentVideo();
        showLoading();
    }

    function pauseCurrentVideo() {
        const currentVideo = $(videoItems[currentIndex]).find('video')[0];
        if (currentVideo) {
            currentVideo.pause();
            // Save current progress
            videoProgress[currentIndex] = currentVideo.currentTime / currentVideo.duration;
        }
    }

    function playCurrentVideo() {
        const postId = $(videoItems[currentIndex]).data('id');
        const currentVideo = $(videoItems[currentIndex]).find('video')[0];
        const playIndicator = $(videoItems[currentIndex]).find('.play-indicator');

        if (currentVideo) {
            // Reset progress if not already set
            if (!videoProgress[currentIndex]) {
                videoProgress[currentIndex] = 0;
            }

            // Preload video
            currentVideo.load();
            
            // Reset muted state (penting untuk autoplay di mobile)
            currentVideo.muted = false;
            
            // Set current time based on saved progress
            if (currentVideo.readyState >= 2) { // HAVE_CURRENT_DATA or better
                currentVideo.currentTime = currentVideo.duration * videoProgress[currentIndex];
            } else {
                currentVideo.addEventListener('loadedmetadata', function() {
                    currentVideo.currentTime = currentVideo.duration * videoProgress[currentIndex];
                }, { once: true });
            }

            // Play when loaded
            currentVideo.addEventListener('canplay', function() {
                hideLoading();
                // Attempt autoplay
                currentVideo.play()
                    .then(() => {
                        increaseView(postId);
                    })
                    .catch(error => {
                        console.log("Autoplay prevented:", error);
                        // Show play button when autoplay fails
                        playIndicator.css('opacity', '1');
                    });
            }, { once: true });

            // Start progress tracking
            trackVideoProgress(currentVideo, postId);

            // Add error handling
            currentVideo.onerror = function() {
                hideLoading();
                console.error("Video error:", currentVideo.error);
                alert("Gagal memuat video. Mencoba video berikutnya...");
                
                if (currentIndex < totalVideos - 1) {
                    nextVideo();
                }
            };

            // Try to play (might fail due to autoplay restrictions)
            currentVideo.play()
                .catch(error => {
                    console.log("Autoplay prevented, waiting for user interaction:", error);
                    // Show play button indicator when autoplay fails
                    playIndicator.css('opacity', '1');
                });
        }
    }

    function trackVideoProgress(video, postId) {
        // Clear any existing interval
        if (window.progressInterval) {
            clearInterval(window.progressInterval);
        }

        // Update progress bar
        window.progressInterval = setInterval(function() {
            if (!video.paused && video.duration) {
                const progressPercent = (video.currentTime / video.duration) * 100;
                $(`#progress-${postId}`).css('width', `${progressPercent}%`);

                // If video ended, go to next
                if (video.currentTime >= video.duration - 0.5) {
                    if (currentIndex < totalVideos - 1) {
                        // Small delay before moving to next
                        setTimeout(nextVideo, 300);
                    } else {
                        // Loop back to beginning if at the end
                        setTimeout(function() {
                            pauseCurrentVideo();
                            $(videoItems[currentIndex]).removeClass('active');
                            currentIndex = 0;
                            $(videoItems[currentIndex]).addClass('active');
                            playCurrentVideo();
                        }, 300);
                    }
                }
            }
        }, 100);
    }

    function showLoading() {
        isLoading = true;
        $('.loader').fadeIn();
    }

    function hideLoading() {
        isLoading = false;
        $('.loader').fadeOut();
    }

    function increaseView(post_id) {
        $.ajax({
            url: "increase_view.php",
            type: "POST",
            data: { post_id: post_id },
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    // Update view count jika elemen ada
                    const viewCountElement = $(`#views-${post_id}`);
                    if (viewCountElement.length) {
                        viewCountElement.text(response.views);
                    }
                } else {
                    console.error("Server error:", response.error || "Unknown error");
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX error:", status, error);
            }
        });
    }

    // Event handlers untuk fitur Like/Dislike
    $(document).on("click", ".like-btn, .dislike-btn", function(e) {
        e.stopPropagation(); // Prevent triggering video play/pause
        const postId = $(this).data("id");
        const type = $(this).hasClass("like-btn") ? "like" : "dislike";
        const button = $(this);

        // Visual feedback
        if (type === "like") {
            button.find('i').addClass('fas').removeClass('far');
            button.find('i').css('color', 'var(--primary-color)');
        } else {
            button.find('i').css('color', 'var(--primary-color)');
        }
        
        button.find('i').css('transform', 'scale(1.2)');
        setTimeout(() => {
            button.find('i').css('transform', 'scale(1)');
        }, 300);

        // Disable button during request
        button.prop('disabled', true);

        $.ajax({
            url: "like.php",
            type: "POST",
            data: { post_id: postId, type: type },
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    $(`#likes-${postId}`).text(response.likes);
                    $(`#dislikes-${postId}`).text(response.dislikes);
                } else {
                    console.error("Server error:", response.error || "Unknown error");
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX error:", status, error);
                console.log("Response text:", xhr.responseText);
            },
            complete: function() {
                // Re-enable button
                button.prop('disabled', false);
            }
        });
    });

   // Event handler untuk tombol Not Interested
$(document).on("click", ".not-interest-btn", function(e) {
    e.stopPropagation(); // Prevent triggering video play/pause
    const postId = $(this).data("id");
    const videoItem = $(this).closest(".video-item");
    const button = $(this);
    
    // Disable button during request
    button.prop('disabled', true);
    
    // Tampilkan feedback visual
    button.find('i').css('color', 'var(--primary-color)');
    button.find('i').css('transform', 'scale(1.2)');
    setTimeout(() => {
        button.find('i').css('transform', 'scale(1)');
    }, 300);
    
    $.ajax({
        url: "not_interest.php",
        type: "POST",
        data: { post_id: postId },
        dataType: "json",
        success: function(response) {
            if (response.success) {
                console.log("Marked as not interested:", response.message);
                
                // Langsung lanjut ke video berikutnya
                if (currentIndex < totalVideos - 1) {
                    nextVideo();
                }
                
                // Optional: Remove the video from array to avoid seeing it again in this session
                // (uncomment code below if you want to implement this)
                /*
                videoItems.splice(videoItem.index(), 1);
                totalVideos = videoItems.length;
                videoItem.remove();
                */
            } else {
                console.error("Server error:", response.error || "Unknown error");
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX error:", status, error);
        },
        complete: function() {
            // Re-enable button
            button.prop('disabled', false);
        }
    });
});

    // Event handler untuk tombol Share
    $(document).on("click", ".share-btn", function(e) {
        e.stopPropagation(); // Prevent triggering video play/pause
        alert("Fitur berbagi akan segera hadir!");
    });

    // Prevent default click on video to handle play/pause manually
    $(document).on("click", ".video-wrapper", function(e) {
        if (!$(e.target).closest('.interaction-btn, .comment-btn, a').length) {
            e.preventDefault();
            togglePlayPause();
        }
    });
    
    // Disable default touch actions in the container
    document.getElementById('videos-container').addEventListener('touchmove', function(e) {
        e.preventDefault();
    }, { passive: false });
});
    </script>
</body>
</html>