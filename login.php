<?php
session_start();
require_once 'db.php';

$error = '';

// Proses login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['login'])) {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $rememberMe = isset($_POST['remember_me']); // Cek apakah Remember Me dicentang

        if (empty($username) || empty($password)) {
            $error = "Silakan isi username dan password";
        } else {
            // Face recognition check
            if (isset($_POST['face_data']) && !empty($_POST['face_data'])) {
                $faceData = $_POST['face_data'];

                // Decode base64 image
                $faceData = str_replace('data:image/png;base64,', '', $faceData);
                $faceData = str_replace(' ', '+', $faceData);
                $faceImage = base64_decode($faceData);

                // Save captured image temporarily
                $tempImagePath = 'temp/face_' . time() . '.png';
                file_put_contents($tempImagePath, $faceImage);

                // Face recognition verification (Simulasi sementara)
                $faceVerified = true; // Gantilah dengan validasi sebenarnya

                if (!$faceVerified) {
                    $error = "Verifikasi wajah gagal. Coba lagi.";
                    unlink($tempImagePath);
                }
            }

            // Database authentication
            $sql = "SELECT id, username, password FROM users WHERE username = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();
                if (password_verify($password, $row['password'])) {
                    $_SESSION['logged_in'] = true;
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['username'] = $row['username'];

                    // Jika Remember Me dicentang, buat cookies
                    if ($rememberMe) {
                        setcookie("user_id", $row['id'], time() + (86400 * 30), "/"); // 30 hari
                        setcookie("username", $row['username'], time() + (86400 * 30), "/"); // 30 hari
                    }

                    // Remove temporary face image if exists
                    if (isset($tempImagePath)) {
                        unlink($tempImagePath);
                    }

                    header("Location: index.php");
                    exit;
                } else {
                    $error = "Password tidak valid";
                }
            } else {
                $error = "Username tidak ditemukan";
            }
        }
    }
}

// Cek apakah user sudah login melalui cookie
if (isset($_COOKIE['user_id']) && isset($_COOKIE['username'])) {
    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = $_COOKIE['user_id'];
    $_SESSION['username'] = $_COOKIE['username'];
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Autentikasi</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #1cc88a;
            --dark-color: #5a5c69;
            --light-color: #f8f9fc;
        }
        
        body {
            background: linear-gradient(135deg, #7886C7, #2D336B);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', sans-serif;
        }
        
        .card {
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            border: none;
            overflow: hidden;
        }
        
        .card-header {
            background-color: var(--light-color);
            border-bottom: none;
            text-align: center;
            padding: 20px;
        }
        
        .card-body {
            padding: 30px;
        }
        
        .logo {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 10px 20px;
            font-weight: bold;
            border-radius: 10px;
        }
        
        .btn-primary:hover {
            background-color: #3a5ccc;
            border-color: #3a5ccc;
        }
        
        .form-control {
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 15px;
        }
        
        .video-container {
            width: 100%;
            height: 250px;
            background-color: #f5f5f5;
            margin: 20px 0;
            border-radius: 10px;
            overflow: hidden;
            position: relative;
        }
        
        #video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 10px;
        }
        
        #canvas {
            display: none;
        }
        
        .face-recognition-toggle {
            margin-bottom: 20px;
            cursor: pointer;
        }
        
        .camera-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 3rem;
            color: rgba(255, 255, 255, 0.7);
            z-index: 1;
        }
        
        .capture-btn {
            position: absolute;
            bottom: 15px;
            left: 50%;
            transform: translateX(-50%);
            background-color: var(--secondary-color);
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            color: white;
            font-size: 1.2rem;
            z-index: 10;
        }
        
        .alert {
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-10 col-lg-12 col-md-9">
                <div class="card">
                    <div class="card-header">
                        <div class="logo">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h2 class="fw-bold text-dark">Selamat Datang</h2>
                        <p class="text-muted">Silakan login ke akun Anda</p>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <div class="mb-3">
                                <label for="username" class="form-label fw-bold">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" name="username" id="username" class="form-control" placeholder="Masukkan username Anda">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label fw-bold">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" name="password" id="password" class="form-control" placeholder="Masukkan password Anda">
                                </div>
                            </div>
                            
                            <div class="face-recognition-toggle mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="enableFaceRecognition">
                                    <label class="form-check-label" for="enableFaceRecognition">Gunakan Face Recognition</label>
                                </div>
                            </div>
                            
                            <div id="faceRecognitionSection" style="display: none;">
                                <div class="video-container">
                                    <div class="camera-overlay">
                                        <i class="fas fa-user-circle"></i>
                                    </div>
                                    <video id="video" autoplay playsinline></video>
                                    <button type="button" id="captureBtn" class="capture-btn"><i class="fas fa-camera"></i></button>
                                </div>
                                <canvas id="canvas"></canvas>
                                <input type="hidden" name="face_data" id="faceData">
                            </div>
                            
                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" name="login" class="btn btn-primary btn-lg">
                                    <i class="fas fa-sign-in-alt me-2"></i> Login
                                </button>
                            </div>
                            
                            <div class="text-center mt-4">
                                <p>Belum punya akun? <a href="register.php" class="text-decoration-none fw-bold">Daftar Sekarang</a></p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Face-api.js library for face recognition -->
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    
    <script>
        // Face recognition functionality
        document.addEventListener('DOMContentLoaded', function() {
            const video = document.getElementById('video');
            const canvas = document.getElementById('canvas');
            const context = canvas.getContext('2d');
            const captureBtn = document.getElementById('captureBtn');
            const faceData = document.getElementById('faceData');
            const enableFaceRecognition = document.getElementById('enableFaceRecognition');
            const faceRecognitionSection = document.getElementById('faceRecognitionSection');
            
            // Toggle face recognition section
            enableFaceRecognition.addEventListener('change', function() {
                if (this.checked) {
                    faceRecognitionSection.style.display = 'block';
                    startVideo();
                } else {
                    faceRecognitionSection.style.display = 'none';
                    stopVideo();
                }
            });
            
            // Start video stream
            async function startVideo() {
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                    video.srcObject = stream;
                    
                    // Load face-api models
                    await Promise.all([
                        faceapi.nets.tinyFaceDetector.loadFromUri('models'),
                        faceapi.nets.faceLandmark68Net.loadFromUri('models'),
                        faceapi.nets.faceRecognitionNet.loadFromUri('models')
                    ]);
                    
                    console.log('Face-api models loaded');
                } catch (error) {
                    console.error('Error accessing camera:', error);
                    alert('Tidak dapat mengakses kamera. Pastikan Anda memberikan izin.');
                }
            }
            
            // Stop video stream
            function stopVideo() {
                if (video.srcObject) {
                    const tracks = video.srcObject.getTracks();
                    tracks.forEach(track => track.stop());
                    video.srcObject = null;
                }
            }
            
            // Capture image from video
            captureBtn.addEventListener('click', function() {
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                context.drawImage(video, 0, 0, canvas.width, canvas.height);
                
                // Convert canvas to base64 image data
                const imageData = canvas.toDataURL('image/png');
                faceData.value = imageData;
                
                // Visual feedback that face was captured
                const overlay = document.createElement('div');
                overlay.className = 'position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-light bg-opacity-75';
                overlay.innerHTML = '<i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>';
                
                const videoContainer = document.querySelector('.video-container');
                videoContainer.appendChild(overlay);
                
                setTimeout(() => {
                    videoContainer.removeChild(overlay);
                }, 2000);
            });
        });
    </script>
</body>
</html>