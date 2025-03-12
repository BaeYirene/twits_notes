<?php
// Start session
session_start();

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Not logged in'
    ]);
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
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
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $conn->connect_error
    ]);
    exit;
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Get form data
$name = isset($_POST['fullname']) ? trim($_POST['fullname']) : '';
$bio = isset($_POST['bio']) ? trim($_POST['bio']) : '';

// Validate data
if (empty($name)) {
    echo json_encode([
        'success' => false,
        'message' => 'Name cannot be empty'
    ]);
    exit;
}

// Initialize user data array
$userData = [
    'name' => $name,
    'bio' => $bio,
    'profile_pic' => null
];

// Handle profile picture upload
$profile_pic_path = null;
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $filename = $_FILES['profile_picture']['name'];
    $filetype = pathinfo($filename, PATHINFO_EXTENSION);
    
    // Verify file extension
    if (in_array(strtolower($filetype), $allowed)) {
        // Create unique filename
        $newFilename = 'user_' . $user_id . '_' . time() . '.' . $filetype;
        $upload_dir = 'uploads/profile_pics/';
        
        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $upload_path = $upload_dir . $newFilename;
        
        // Optional: Process the image to make it square (uncomment if needed)
        /*
        $source_img = null;
        
        // Create image resource based on file type
        switch(strtolower($filetype)) {
            case 'jpg':
            case 'jpeg':
                $source_img = imagecreatefromjpeg($_FILES['profile_picture']['tmp_name']);
                break;
            case 'png':
                $source_img = imagecreatefrompng($_FILES['profile_picture']['tmp_name']);
                break;
            case 'gif':
                $source_img = imagecreatefromgif($_FILES['profile_picture']['tmp_name']);
                break;
        }
        
        if ($source_img) {
            // Get original dimensions
            $width = imagesx($source_img);
            $height = imagesy($source_img);
            
            // Find the minimum dimension
            $min_dim = min($width, $height);
            
            // Calculate crop positions
            $crop_x = ($width - $min_dim) / 2;
            $crop_y = ($height - $min_dim) / 2;
            
            // Create a square image
            $square_img = imagecreatetruecolor(250, 250);
            
            // Crop and resize
            imagecopyresampled(
                $square_img, $source_img,
                0, 0, $crop_x, $crop_y,
                250, 250, $min_dim, $min_dim
            );
            
            // Save the image
            switch(strtolower($filetype)) {
                case 'jpg':
                case 'jpeg':
                    imagejpeg($square_img, $upload_path, 90);
                    break;
                case 'png':
                    imagepng($square_img, $upload_path, 9);
                    break;
                case 'gif':
                    imagegif($square_img, $upload_path);
                    break;
            }
            
            // Free up memory
            imagedestroy($source_img);
            imagedestroy($square_img);
            
            $profile_pic_path = $upload_path;
            $userData['profile_pic'] = $profile_pic_path;
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to process image'
            ]);
            exit;
        }
        */
        
        // Move uploaded file (use this if not processing the image)
        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
            $profile_pic_path = $upload_path;
            $userData['profile_pic'] = $profile_pic_path;
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to upload image'
            ]);
            exit;
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid file type. Only JPG, JPEG, PNG and GIF are allowed'
        ]);
        exit;
    }
}

// Prepare SQL statement
if ($profile_pic_path) {
    $sql = "UPDATE users SET name = ?, bio = ?, profile_pic = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $name, $bio, $profile_pic_path, $user_id);
} else {
    $sql = "UPDATE users SET name = ?, bio = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $name, $bio, $user_id);
}

// Execute statement
if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully',
        'user' => $userData
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update profile: ' . $stmt->error
    ]);
}

// Close connection
$stmt->close();
$conn->close();
?>