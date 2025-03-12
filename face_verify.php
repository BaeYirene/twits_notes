<?php
// File: face_verify.php
// API untuk verifikasi wajah

session_start();
require_once 'db.php';

// Headers untuk JSON response
header('Content-Type: application/json');

// Fungsi untuk membandingkan wajah menggunakan Euclidean Distance
function euclideanDistance($vector1, $vector2) {
    if (count($vector1) !== count($vector2)) {
        return INF; // Jika panjang vektor tidak sama, kembalikan jarak tidak valid
    }

    $sum = 0;
    for ($i = 0; $i < count($vector1); $i++) {
        $sum += pow($vector1[$i] - $vector2[$i], 2);
    }
    return sqrt($sum);
}

// Handle request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi request
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['username']) || !isset($data['faceDescriptor'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Data tidak lengkap'
        ]);
        exit;
    }

    $username = $data['username'];
    $faceDescriptor = json_decode($data['faceDescriptor'], true);

    // Ambil data wajah dari database
    $sql = "SELECT id, face_data_path, face_descriptor FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'User tidak ditemukan'
        ]);
        exit;
    }

    $user = $result->fetch_assoc();

    if (empty($user['face_data_path']) || !file_exists($user['face_data_path']) || empty($user['face_descriptor'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Data wajah tidak tersedia'
        ]);
        exit;
    }

    // Load descriptor wajah dari database
    $storedDescriptor = json_decode($user['face_descriptor'], true);

    // Perbandingan wajah menggunakan Euclidean Distance
    $distance = euclideanDistance($storedDescriptor, $faceDescriptor);
    $threshold = 0.5; // Semakin kecil, semakin ketat verifikasinya

    if ($distance < $threshold) {
        $_SESSION['username'] = $username;
        echo json_encode([
            'success' => true,
            'message' => 'Verifikasi wajah berhasil',
            'distance' => $distance,
            'threshold' => $threshold
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Wajah tidak cocok! Login gagal.',
            'distance' => $distance,
            'threshold' => $threshold
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Method tidak diizinkan'
    ]);
}
?>
