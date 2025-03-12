<?php
// File: setup_face_recognition.php
// Script untuk setup awal face recognition

// Buat direktori untuk menyimpan model face-api.js jika belum ada
$modelsDir = 'models';
if (!file_exists($modelsDir)) {
    mkdir($modelsDir, 0777, true);
    mkdir($modelsDir . '/tiny_face_detector', 0777, true);
    mkdir($modelsDir . '/face_landmark_68', 0777, true);
    mkdir($modelsDir . '/face_recognition', 0777, true);
}

// Buat direktori untuk menyimpan data wajah pengguna
$faceDataDir = 'face_data';
if (!file_exists($faceDataDir)) {
    mkdir($faceDataDir, 0777, true);
}

// Buat direktori untuk menyimpan gambar sementara
$tempDir = 'temp';
if (!file_exists($tempDir)) {
    mkdir($tempDir, 0777, true);
}

echo "Direktori setup selesai. Selanjutnya, download model face-api.js dari GitHub dan letakkan di folder models.<br>";
echo "Model yang diperlukan:<br>";
echo "- tiny_face_detector_model-weights_manifest.json<br>";
echo "- tiny_face_detector_model-shard1<br>";
echo "- face_landmark_68_model-weights_manifest.json<br>";
echo "- face_landmark_68_model-shard1<br>";
echo "- face_recognition_model-weights_manifest.json<br>";
echo "- face_recognition_model-shard1<br>";
echo "- face_recognition_model-shard2<br>";
?>