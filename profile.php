<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header('Location: login.php');
    exit;
}

// Database connection
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "twits_notes";

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get user ID from session or URL
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : $_SESSION['user_id'];

// Get user data from database using prepared statement to prevent SQL injection
$sql = "SELECT id, username, email, profile_pic, name, bio, created_at FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $userData = $result->fetch_assoc();
} else {
    // User not found
    header('Location: 404.php');
    exit;
}
$stmt->close();

// Get user stats
$stats = [
    'posts' => 0,
    'followers' => 0,
    'following' => 0,
    'likes' => 0
];

// Example queries to get stats (using prepared statements)
// Get posts count
$sql = "SELECT COUNT(*) as count FROM posts WHERE user_id = ?";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $stats['posts'] = $result->fetch_assoc()['count'];
    }
    $stmt->close();
}

// Get followers count
$sql = "SELECT COUNT(*) as count FROM followers WHERE followed_id = ?";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $stats['followers'] = $result->fetch_assoc()['count'];
    }
    $stmt->close();
}

// Get following count
$sql = "SELECT COUNT(*) as count FROM followers WHERE follower_id = ?";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $stats['following'] = $result->fetch_assoc()['count'];
    }
    $stmt->close();
}

// Get likes count
$sql = "SELECT COUNT(*) as count FROM likes WHERE user_id = ?";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $stats['likes'] = $result->fetch_assoc()['count'];
    }
    $stmt->close();
}

// Close database connection
$conn->close();

// Function to format date
function formatDate($dateString) {
    $date = new DateTime($dateString);
    return 'Joined ' . $date->format('F Y');
}

// Check if this is the user's own profile
$isOwnProfile = ($_SESSION['user_id'] == $user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TwitsNotes - <?php echo htmlspecialchars($userData['name']); ?>'s Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #1DA1F2;
            --secondary-color: #14171A;
            --light-color: #E1E8ED;
            --gray-color: #657786;
            --white-color: #FFFFFF;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #F5F8FA;
            color: var(--secondary-color);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            padding: 10px 20px;
            background-color: var(--white-color);
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .back-button:hover {
            background-color: rgba(29, 161, 242, 0.1);
        }

        .back-button i {
            margin-right: 8px;
        }

        .profile-container {
            background-color: var(--white-color);
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .profile-header {
            position: relative;
            height: 200px;
            background: linear-gradient(135deg, #1DA1F2, #0D8ECF);
        }

        .profile-info {
            position: relative;
            padding: 0 30px 30px;
            text-align: center;
        }

        /* Modified avatar positioning */
        .profile-avatar {
            position: relative;
            width: 120px;
            height: 120px;
            border-radius: 60px;
            border: 4px solid var(--white-color);
            background-color: var(--white-color);
            overflow: hidden;
            margin: -60px auto 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }

        .edit-profile-btn {
            position: absolute;
            right: 20px;
            bottom: 20px;
            padding: 8px 20px;
            background-color: var(--white-color);
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
            border-radius: 30px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 5;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .edit-profile-btn:hover {
            background-color: rgba(29, 161, 242, 0.1);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .profile-name {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .profile-username {
            color: var(--gray-color);
            font-size: 16px;
            margin-bottom: 15px;
        }

        .profile-bio {
            font-size: 16px;
            line-height: 1.5;
            margin-bottom: 20px;
        }

        .profile-metadata {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        .metadata-item {
            display: flex;
            align-items: center;
            color: var(--gray-color);
        }

        .metadata-item i {
            margin-right: 5px;
        }

        .profile-stats {
            display: flex;
            border-top: 1px solid var(--light-color);
            padding-top: 20px;
        }

        .stats-item {
            flex: 1;
            text-align: center;
            padding: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .stats-item:hover {
            background-color: rgba(29, 161, 242, 0.1);
        }

        .stats-value {
            font-weight: 700;
            font-size: 18px;
            color: var(--secondary-color);
        }

        .stats-label {
            color: var(--gray-color);
            font-size: 14px;
        }

        /* Rest of CSS styles here... */
        
        .profile-content {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 20px;
            margin-top: 20px;
        }
        
        .profile-sidebar {
            background-color: var(--white-color);
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        
        .sidebar-section {
            margin-bottom: 30px;
        }
        
        .sidebar-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 15px;
            color: var(--secondary-color);
        }
        
        .sidebar-item {
            display: flex;
            align-items: center;
            padding: 10px;
            color: var(--gray-color);
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .sidebar-item:hover {
            background-color: rgba(29, 161, 242, 0.1);
            color: var(--primary-color);
        }
        
        .sidebar-item i {
            margin-right: A10px;
            width: 20px;
            text-align: center;
        }
        
        .profile-feed {
            background-color: var(--white-color);
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .feed-tabs {
            display: flex;
            border-bottom: 1px solid var(--light-color);
        }
        
        .feed-tab {
            flex: 1;
            text-align: center;
            padding: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .feed-tab:hover {
            background-color: rgba(29, 161, 242, 0.1);
        }
        
        .feed-tab.active {
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
        }
        
        .feed-empty {
            text-align: center;
            padding: 50px 20px;
            color: var(--gray-color);
        }
        
        .feed-empty i {
            font-size: 40px;
            margin-bottom: 10px;
        }
        
        .feed-empty h3 {
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        /* Status messages */
        .status-message {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            padding: 10px 20px;
            border-radius: 5px;
            display: none;
            z-index: 1000;
            font-weight: 600;
        }
        
        .status-success {
            background-color: #4CAF50;
            color: white;
        }
        
        .status-error {
            background-color: #f44336;
            color: white;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background-color: var(--white-color);
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid var(--light-color);
        }
        
        .modal-title {
            font-size: 20px;
            font-weight: 700;
        }
        
        .close-modal {
            font-size: 24px;
            color: var(--gray-color);
            cursor: pointer;
        }
        
        .close-modal:hover {
            color: var(--secondary-color);
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .avatar-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin: 0 auto 20px;
            overflow: hidden;
            border: 2px solid var(--light-color);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .avatar-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .form-input,
        .form-textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--light-color);
            border-radius: 5px;
            font-size: 16px;
        }
        
        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-file-label {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 10px;
        }
        
        .form-file-input {
            display: none;
        }
        
        .form-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn-cancel,
        .btn-save {
            padding: 10px 20px;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            border: none;
        }
        
        .btn-cancel {
            background-color: var(--light-color);
            color: var(--gray-color);
        }
        
        .btn-save {
            background-color: var(--primary-color);
            color: white;
        }
        
        @media (max-width: 768px) {
            .profile-content {
                grid-template-columns: 1fr;
            }

            .profile-avatar {
                width: 100px;
                height: 100px;
                margin-top: -50px;
            }

            .profile-stats {
                flex-wrap: wrap;
            }
            
            .stats-item {
                flex-basis: 50%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Homepage
        </a>
        
        <div class="profile-container">
            <div class="profile-header">
                <?php if ($isOwnProfile): ?>
                <button class="edit-profile-btn" id="edit-profile-btn">Edit Profile</button>
                <?php endif; ?>
            </div>
            
            <div class="profile-info">
                <!-- Avatar positioned between header and info section -->
                <div class="profile-avatar">
                    <img src="<?php echo htmlspecialchars($userData['profile_pic'] ?: 'assets/images/default-avatar.jpg'); ?>" alt="Profile Picture" id="profile-image">
                </div>
                
                <h1 class="profile-name" id="profile-name"><?php echo htmlspecialchars($userData['name']); ?></h1>
                <div class="profile-username" id="profile-username">@<?php echo htmlspecialchars($userData['username']); ?></div>
                
                <p class="profile-bio" id="profile-bio">
                    <?php echo htmlspecialchars($userData['bio'] ?: 'No bio available.'); ?>
                </p>
                
                <div class="profile-metadata">
                    <div class="metadata-item">
                        <i class="fas fa-calendar-alt"></i>
                        <span id="join-date"><?php echo formatDate($userData['created_at']); ?></span>
                    </div>
                    <?php if ($isOwnProfile): ?>
                    <div class="metadata-item">
                        <i class="fas fa-envelope"></i>
                        <span id="profile-email"><?php echo htmlspecialchars($userData['email']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="profile-stats">
                    <div class="stats-item">
                        <div class="stats-value"><?php echo number_format($stats['posts']); ?></div>
                        <div class="stats-label">Posts</div>
                    </div>
                    <div class="stats-item">
                        <div class="stats-value"><?php echo number_format($stats['followers']); ?></div>
                        <div class="stats-label">Followers</div>
                    </div>
                    <div class="stats-item">
                        <div class="stats-value"><?php echo number_format($stats['following']); ?></div>
                        <div class="stats-label">Following</div>
                    </div>
                    <div class="stats-item">
                        <div class="stats-value"><?php echo number_format($stats['likes']); ?></div>
                        <div class="stats-label">Likes</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="profile-content">
            <div class="profile-sidebar">
                <div class="sidebar-section">
                    <h3 class="sidebar-title">Account</h3>
                    <a href="profile.php" class="sidebar-item">
                        <i class="fas fa-user"></i> My Profile
                    </a>
                    <a href="bookmarks.php" class="sidebar-item">
                        <i class="fas fa-bookmark"></i> Bookmarks
                    </a>
                    <a href="history.php" class="sidebar-item">
                        <i class="fas fa-history"></i> History
                    </a>
                    <a href="settings.php" class="sidebar-item">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                </div>
                
                <?php if ($isOwnProfile): ?>
                <div class="sidebar-section">
                    <h3 class="sidebar-title">Security</h3>
                    <a href="change_password.php" class="sidebar-item">
                        <i class="fas fa-lock"></i> Change Password
                    </a>
                    <a href="face_recognition.php" class="sidebar-item">
                        <i class="fas fa-face-smile"></i> Face Recognition
                    </a>
                    <a href="privacy.php" class="sidebar-item">
                        <i class="fas fa-shield-alt"></i> Privacy
                    </a>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="profile-feed">
                <div class="feed-tabs">
                    <div class="feed-tab active">Posts</div>
                    <div class="feed-tab">Likes</div>
                    <div class="feed-tab">Comments</div>
                    <div class="feed-tab">Bookmarks</div>
                </div>
                
                <div id="posts-content">
                    <?php if ($stats['posts'] > 0): ?>
                    <!-- Posts will be loaded here via AJAX -->
                    <p class="p-4">Loading posts...</p>
                    <?php else: ?>
                    <div class="feed-empty">
                        <i class="fas fa-pen-to-square"></i>
                        <h3>No posts yet</h3>
                        <p>When you create posts, they will appear here.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Status messages -->
    <div id="status-success" class="status-message status-success"></div>
    <div id="status-error" class="status-message status-error"></div>

    <?php if ($isOwnProfile): ?>
    <!-- Edit Profile Modal -->
    <div class="modal" id="edit-profile-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Edit Profile</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <form id="edit-profile-form" enctype="multipart/form-data">
                    <div class="avatar-preview">
                        <img src="<?php echo htmlspecialchars($userData['profile_pic'] ?: 'assets/images/default-avatar.jpg'); ?>" alt="Profile Preview" id="avatar-preview-img">
                    </div>
                    
                    <div class="form-group">
                        <label for="profile-picture" class="form-file-label">
                            <i class="fas fa-camera"></i> Change Profile Picture
                        </label>
                        <input type="file" id="profile-picture" name="profile_picture" class="form-file-input" accept="image/*">
                    </div>
                    
                    <div class="form-group">
                        <label for="fullname" class="form-label">Full Name</label>
                        <input type="text" id="fullname" name="fullname" class="form-input" value="<?php echo htmlspecialchars($userData['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" id="username" name="username" class="form-input" value="<?php echo htmlspecialchars($userData['username']); ?>" disabled>
                        <small style="color: var(--gray-color);">Username cannot be changed</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" id="email" name="email" class="form-input" value="<?php echo htmlspecialchars($userData['email']); ?>" disabled>
                        <small style="color: var(--gray-color);">Email cannot be changed</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="bio" class="form-label">Bio</label>
                        <textarea id="bio" name="bio" class="form-textarea" maxlength="160"><?php echo htmlspecialchars($userData['bio'] ?: ''); ?></textarea>
                        <small style="color: var(--gray-color);">Maximum 160 characters</small>
                    </div>
                    
                    <div class="form-buttons">
                        <button type="button" class="btn-cancel" id="cancel-edit">Cancel</button>
                        <button type="submit" class="btn-save">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
        // Modal functionality
        <?php if ($isOwnProfile): ?>
        const modal = document.getElementById('edit-profile-modal');
        const editBtn = document.getElementById('edit-profile-btn');
        const closeBtn = document.querySelector('.close-modal');
        const cancelBtn = document.getElementById('cancel-edit');

        editBtn.addEventListener('click', function() {
            modal.style.display = 'flex';
        });

        function closeModal() {
            modal.style.display = 'none';
        }

        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);

        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeModal();
            }
        });

        // Profile picture preview
        const profilePictureInput = document.getElementById('profile-picture');
        const avatarPreviewImg = document.getElementById('avatar-preview-img');

        profilePictureInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    avatarPreviewImg.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });

        // Function to show status message
        function showStatusMessage(type, message) {
            const element = document.getElementById(`status-${type}`);
            element.textContent = message;
            element.style.display = 'block';
            
            // Hide after 3 seconds
            setTimeout(() => {
                element.style.display = 'none';
            }, 3000);
        }

        // Form submission
        document.getElementById('edit-profile-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form data
            const formData = new FormData(this);
            
            // Send AJAX request
            fetch('update_profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // Check if response is ok before trying to parse JSON
                if (!response.ok) {
                    throw new Error('Server responded with status: ' + response.status);
                }
                
                // First try to get the content type
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return response.json();
                } else {
                    // If not JSON, get text and throw error
                    return response.text().then(text => {
                        throw new Error('Expected JSON response but got: ' + text);
                    });
                }
            })
            .then(data => {
                if (data.success) {
                    // Update profile info
                    document.getElementById('profile-name').textContent = data.user.name;
                    document.getElementById('profile-bio').textContent = data.user.bio || 'No bio available.';
                    
                    // Update profile picture if changed
                    if (data.user.profile_pic) {
                        document.getElementById('profile-image').src = data.user.profile_pic;
                    }
                    
                    showStatusMessage('success', data.message || 'Profile updated successfully!');
                } else {
                    showStatusMessage('error', data.message || 'Error updating profile');
                }
                closeModal();
            })
            .catch(error => {
                console.error('Error:', error);
                showStatusMessage('error', 'Server error. Please check the console for details.');
            });
        });
        <?php endif; ?>

        // Tab switching functionality
        document.querySelectorAll('.feed-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelector('.feed-tab.active').classList.remove('active');
                this.classList.add('active');
                
                // In a real application, load different content based on the selected tab
                // e.g., loadTabContent(this.textContent.toLowerCase());
            });
        });

        <?php if ($stats['posts'] > 0): ?>
        // Load posts on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadPosts();
        });

        function loadPosts() {
            // In a real application, you would load posts via AJAX
            fetch('get_posts.php?user_id=<?php echo $user_id; ?>')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const postsContainer = document.getElementById('posts-content');
                        postsContainer.innerHTML = '';
                        
                        if (data.posts.length > 0) {
                            data.posts.forEach(post => {
                                // Create post HTML
                                const postElement = document.createElement('div');
                                postElement.classList.add('post-item');
                                // Add post content
                                // ...
                                postsContainer.appendChild(postElement);
                            });
                        } else {
                            postsContainer.innerHTML = `
                                <div class="feed-empty">
                                    <i class="fas fa-pen-to-square"></i>
                                    <h3>No posts yet</h3>
                                    <p>When you create posts, they will appear here.</p>
                                </div>
                            `;
                        }
                    } else {
                        document.getElementById('posts-content').innerHTML = `
                            <div class="feed-empty">
                                <i class="fas fa-exclamation-circle"></i>
                                <h3>Failed to load posts</h3>
                                <p>${data.message || 'Please try again later.'}</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('posts-content').innerHTML = `
                        <div class="feed-empty">
                            <i class="fas fa-exclamation-circle"></i>
                            <h3>Error loading posts</h3>
                            <p>Please try again later.</p>
                        </div>
                    `;
                });
        }
        <?php endif; ?>
    </script>
</body>
</html>