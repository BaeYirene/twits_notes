<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TwitsNotes - User Profile</title>
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
            padding: 0 30px 30px;
            text-align: center;
        }

        /* Modified: Moved avatar to be centered above the username */
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 60px;
            border: 4px solid var(--white-color);
            background-color: var(--white-color);
            overflow: hidden;
            margin: -60px auto 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
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
        }

        .edit-profile-btn:hover {
            background-color: rgba(29, 161, 242, 0.1);
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

        .profile-content {
            margin-top: 20px;
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 20px;
        }

        .profile-sidebar {
            background-color: var(--white-color);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .sidebar-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--light-color);
        }

        .sidebar-section {
            margin-bottom: 20px;
        }

        .sidebar-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            color: var(--secondary-color);
            text-decoration: none;
        }

        .sidebar-item i {
            width: 30px;
            color: var(--primary-color);
        }

        .profile-feed {
            background-color: var(--white-color);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .feed-tabs {
            display: flex;
            border-bottom: 1px solid var(--light-color);
            margin-bottom: 20px;
        }

        .feed-tab {
            padding: 15px 20px;
            font-weight: 600;
            cursor: pointer;
            position: relative;
        }

        .feed-tab.active {
            color: var(--primary-color);
        }

        .feed-tab.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 3px;
            background-color: var(--primary-color);
            border-radius: 3px 3px 0 0;
        }

        .feed-empty {
            padding: 60px 0;
            text-align: center;
            color: var(--gray-color);
        }

        .feed-empty i {
            font-size: 48px;
            margin-bottom: 15px;
            color: var(--light-color);
        }

        .feed-empty h3 {
            font-size: 20px;
            margin-bottom: 10px;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: var(--white-color);
            width: 90%;
            max-width: 500px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            position: relative;
        }

        .modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid var(--light-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 20px;
            font-weight: 700;
        }

        .close-modal {
            font-size: 24px;
            cursor: pointer;
            color: var(--gray-color);
        }

        .modal-body {
            padding: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .form-input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--light-color);
            border-radius: 5px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 2px rgba(29, 161, 242, 0.2);
        }

        .form-input:disabled {
            background-color: #f5f5f5;
            cursor: not-allowed;
        }

        .form-textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--light-color);
            border-radius: 5px;
            font-size: 16px;
            min-height: 100px;
            resize: vertical;
            transition: all 0.3s ease;
        }

        .form-textarea:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 2px rgba(29, 161, 242, 0.2);
        }

        .form-file-input {
            display: none;
        }

        .form-file-label {
            display: inline-flex;
            align-items: center;
            padding: 10px 15px;
            background-color: var(--white-color);
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
            border-radius: 30px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .form-file-label:hover {
            background-color: rgba(29, 161, 242, 0.1);
        }

        .form-file-label i {
            margin-right: 8px;
        }

        .avatar-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            overflow: hidden;
            margin: 10px auto 20px;
            border: 3px solid var(--light-color);
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .avatar-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .form-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-cancel {
            padding: 10px 20px;
            background-color: var(--white-color);
            color: var(--gray-color);
            border: 1px solid var(--light-color);
            border-radius: 30px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-cancel:hover {
            background-color: #f5f5f5;
        }

        .btn-save {
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: var(--white-color);
            border: none;
            border-radius: 30px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-save:hover {
            background-color: #0D8ECF;
        }

        .status-message {
            padding: 10px 15px;
            margin: 10px 0;
            border-radius: 5px;
            display: none;
        }

        .status-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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
                <button class="edit-profile-btn" id="edit-profile-btn">Edit Profile</button>
            </div>
            
            <div class="profile-info">
                <!-- Modified: Moved avatar above username -->
                <div class="profile-avatar">
                    <img src="/api/placeholder/120/120" alt="Profile Picture" id="profile-image">
                </div>
                
                <h1 class="profile-name" id="profile-name">John Doe</h1>
                <div class="profile-username" id="profile-username">@johndoe</div>
                
                <p class="profile-bio" id="profile-bio">
                    Welcome to my TwitsNotes profile! I share thoughts, ideas, and interesting content.
                </p>
                
                <div class="profile-metadata">
                    <div class="metadata-item">
                        <i class="fas fa-calendar-alt"></i>
                        <span id="join-date">Joined March 2025</span>
                    </div>
                    <div class="metadata-item">
                        <i class="fas fa-envelope"></i>
                        <span id="profile-email">johndoe@example.com</span>
                    </div>
                </div>
                
                <div class="profile-stats">
                    <div class="stats-item">
                        <div class="stats-value">0</div>
                        <div class="stats-label">Posts</div>
                    </div>
                    <div class="stats-item">
                        <div class="stats-value">0</div>
                        <div class="stats-label">Followers</div>
                    </div>
                    <div class="stats-item">
                        <div class="stats-value">0</div>
                        <div class="stats-label">Following</div>
                    </div>
                    <div class="stats-item">
                        <div class="stats-value">0</div>
                        <div class="stats-label">Likes</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="profile-content">
            <div class="profile-sidebar">
                <div class="sidebar-section">
                    <h3 class="sidebar-title">Account</h3>
                    <a href="#" class="sidebar-item">
                        <i class="fas fa-user"></i> My Profile
                    </a>
                    <a href="#" class="sidebar-item">
                        <i class="fas fa-bookmark"></i> Bookmarks
                    </a>
                    <a href="#" class="sidebar-item">
                        <i class="fas fa-history"></i> History
                    </a>
                    <a href="#" class="sidebar-item">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                </div>
                
                <div class="sidebar-section">
                    <h3 class="sidebar-title">Security</h3>
                    <a href="#" class="sidebar-item">
                        <i class="fas fa-lock"></i> Change Password
                    </a>
                    <a href="#" class="sidebar-item">
                        <i class="fas fa-face-smile"></i> Face Recognition
                    </a>
                    <a href="#" class="sidebar-item">
                        <i class="fas fa-shield-alt"></i> Privacy
                    </a>
                </div>
            </div>
            
            <div class="profile-feed">
                <div class="feed-tabs">
                    <div class="feed-tab active">Posts</div>
                    <div class="feed-tab">Likes</div>
                    <div class="feed-tab">Comments</div>
                    <div class="feed-tab">Bookmarks</div>
                </div>
                
                <div class="feed-empty">
                    <i class="fas fa-pen-to-square"></i>
                    <h3>No posts yet</h3>
                    <p>When you create posts, they will appear here.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Status messages -->
    <div id="status-success" class="status-message status-success"></div>
    <div id="status-error" class="status-message status-error"></div>

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
                        <img src="/api/placeholder/100/100" alt="Profile Preview" id="avatar-preview-img">
                    </div>
                    
                    <div class="form-group">
                        <label for="profile-picture" class="form-file-label">
                            <i class="fas fa-camera"></i> Change Profile Picture
                        </label>
                        <input type="file" id="profile-picture" name="profile_picture" class="form-file-input" accept="image/*">
                    </div>
                    
                    <div class="form-group">
                        <label for="fullname" class="form-label">Full Name</label>
                        <input type="text" id="fullname" name="fullname" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" id="username" name="username" class="form-input" disabled>
                        <small style="color: var(--gray-color);">Username cannot be changed</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" id="email" name="email" class="form-input" disabled>
                        <small style="color: var(--gray-color);">Email cannot be changed</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="bio" class="form-label">Bio</label>
                        <textarea id="bio" name="bio" class="form-textarea" maxlength="160"></textarea>
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

    <script>
        // Sample user data - in a real application, this would come from your backend
        let userData = {
            id: 1,
            username: "johndoe",
            email: "johndoe@example.com",
            profile_pic: "/api/placeholder/120/120",
            name: "John Doe",
            bio: "Welcome to my TwitsNotes profile! I share thoughts, ideas, and interesting content.",
            created_at: "2025-01-15 10:30:45"
        };

        // Function to format date
        function formatDate(dateString) {
            const date = new Date(dateString);
            const month = date.toLocaleString('default', { month: 'long' });
            const year = date.getFullYear();
            return `Joined ${month} ${year}`;
        }

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

        // Populate user data
        document.addEventListener('DOMContentLoaded', function() {
            // Check if user is logged in (This would be handled by PHP)
            // For demo purposes, we're using the sample data
            // In reality, you'd get this data from PHP session or AJAX call
            
            // In a real application, you might do:
            // fetch('get_user_data.php')
            //   .then(response => response.json())
            //   .then(data => {
            //     userData = data;
            //     populateUserData();
            //   });
            
            populateUserData();
        });

        function populateUserData() {
            // Populate profile page
            document.getElementById('profile-name').textContent = userData.name;
            document.getElementById('profile-username').textContent = '@' + userData.username;
            document.getElementById('profile-bio').textContent = userData.bio;
            document.getElementById('profile-email').textContent = userData.email;
            document.getElementById('join-date').textContent = formatDate(userData.created_at);
            document.getElementById('profile-image').src = userData.profile_pic;
            
            // Populate edit form
            document.getElementById('fullname').value = userData.name;
            document.getElementById('username').value = userData.username;
            document.getElementById('email').value = userData.email;
            document.getElementById('bio').value = userData.bio;
            document.getElementById('avatar-preview-img').src = userData.profile_pic;
        }

        // Tab switching functionality
        document.querySelectorAll('.feed-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelector('.feed-tab.active').classList.remove('active');
                this.classList.add('active');
                // In a real application, you would load different content based on the selected tab
            });
        });

        // Modal functionality
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

        // Form submission
        document.getElementById('edit-profile-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // In a real application, you would send the form data to the server
            // using AJAX or fetch API
            
            // Example of how to get form data
            const formData = new FormData(this);
            
            // In a real application, you would do:
            // fetch('update_profile.php', {
            //     method: 'POST',
            //     body: formData
            // })
            // .then(response => response.json())
            // .then(data => {
            //     if (data.success) {
            //         userData = data.user;
            //         populateUserData();
            //         showStatusMessage('success', 'Profile updated successfully!');
            //     } else {
            //         showStatusMessage('error', data.message || 'Error updating profile');
            //     }
            //     closeModal();
            // })
            // .catch(error => {
            //     showStatusMessage('error', 'Network error. Please try again.');
            //     console.error('Error:', error);
            // });
            
            // For demo purposes:
            // Update user data object (in a real application, this would happen after server response)
            userData.name = formData.get('fullname');
            userData.bio = formData.get('bio');
            
            // Update profile page with new data
            document.getElementById('profile-name').textContent = userData.name;
            document.getElementById('profile-bio').textContent = userData.bio;
            
            // If a new profile picture was uploaded
            const profilePicture = formData.get('profile_picture');
            if (profilePicture && profilePicture.size > 0) {
                // In a real application, after the server processes the image:
                // userData.profile_pic = 'new_profile_pic_url';
                // document.getElementById('profile-image').src = userData.profile_pic;
                
                // For demo, we can use the FileReader to show the selected image
                const reader = new FileReader();
                reader.onload = function(e) {
                    userData.profile_pic = e.target.result;
                    document.getElementById('profile-image').src = userData.profile_pic;
                };
                reader.readAsDataURL(profilePicture);
            }
            
            // Close the modal
            closeModal();
            
            // Show success message
            showStatusMessage('success', 'Profile updated successfully!');
        });
    </script>
</body>
</html>