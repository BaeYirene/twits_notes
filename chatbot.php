<?php
// Aktifkan error reporting untuk development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Mulai session
session_start();

// Fungsi untuk menangani error dan mengembalikan respons JSON
function handleError($message) {
    http_response_code(500); // Set status code ke 500 (Internal Server Error)
    echo json_encode(["error" => $message]);
    exit;
}

// Include file koneksi database dan konfigurasi
require 'db.php'; // File koneksi ke database
require 'config.php'; // File konfigurasi untuk environment variable

// Catat bahwa chatbot.php dipanggil
file_put_contents("debug_log.txt", "Chatbot dipanggil" . PHP_EOL, FILE_APPEND);

// Periksa apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    handleError("Anda harus login untuk menggunakan chatbot.");
}

$user_id = $_SESSION['user_id'];

// Periksa koneksi database
if (!$conn) {
    handleError("Gagal menghubungkan ke database.");
}

// Periksa apakah message dikirim
if (!isset($_POST['message']) || empty(trim($_POST['message']))) {
    handleError("Pesan tidak boleh kosong.");
}

$message = trim($_POST['message']);
file_put_contents("debug_log.txt", "Pesan diterima: $message" . PHP_EOL, FILE_APPEND);

// Cek apakah pertanyaan berkaitan dengan database
$response = checkDatabase($message, $user_id);
file_put_contents("debug_log.txt", "Hasil checkDatabase: " . ($response ?? "NULL") . PHP_EOL, FILE_APPEND);

// Jika tidak ada jawaban dari database, gunakan DeepSeek (AI)
if (!$response) {
    $response = askDeepSeek($message);
}

// Jika masih kosong, berikan pesan default
if (!$response) {
    $response = "Maaf, saya tidak mengerti pertanyaan Anda.";
}

file_put_contents("debug_log.txt", "Respon chatbot: $response" . PHP_EOL, FILE_APPEND);
echo json_encode(["response" => $response]);

// ===============================
// Fungsi untuk mengecek database
// ===============================
function checkDatabase($message, $user_id) {
    global $conn;

    $message = strtolower($message);

    // 1. Sapaan
    if (strpos($message, "halo") !== false || strpos($message, "hai") !== false) {
        return "Halo! Ada yang bisa saya bantu?";
    }

    // 2. Pertanyaan tentang likes berdasarkan hashtag atau caption
    if (strpos($message, "berapa banyak likes pada postingan dengan hashtag") !== false) {
        $hashtag = extract_hashtag($message); // Ambil hashtag dari pesan
        if ($hashtag) {
            $query = "SELECT p.id, p.content, COUNT(l.id) as total_likes 
                      FROM posts p 
                      LEFT JOIN likes l ON p.id = l.post_id 
                      WHERE p.content LIKE ? 
                      GROUP BY p.id";
            $stmt = $conn->prepare($query);
            $hashtag_like = "%#$hashtag%";
            $stmt->bind_param("s", $hashtag_like);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $row = $result->fetch_assoc()) {
                return "Jumlah likes pada postingan dengan hashtag #$hashtag: " . $row['total_likes'];
            } else {
                return "Tidak ada postingan dengan hashtag #$hashtag.";
            }
            $stmt->close();
        }
    }

    // 3. Pertanyaan tentang postingan dengan like terbanyak
    if (strpos($message, "postingan mana yang paling banyak likes") !== false) {
        $query = "SELECT p.id, p.content, COUNT(l.id) as total_likes 
                  FROM posts p 
                  LEFT JOIN likes l ON p.id = l.post_id 
                  GROUP BY p.id 
                  ORDER BY total_likes DESC 
                  LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $row = $result->fetch_assoc()) {
            return "Postingan dengan likes terbanyak: '" . $row['content'] . "' (Likes: " . $row['total_likes'] . ")";
        } else {
            return "Tidak ada postingan.";
        }
        $stmt->close();
    }

    // 4. Pertanyaan tentang postingan dengan view terbanyak
    if (strpos($message, "postingan mana yang paling banyak dilihat") !== false) {
        $query = "SELECT p.id, p.content, COUNT(v.id) as total_views 
                  FROM posts p 
                  LEFT JOIN views v ON p.id = v.post_id 
                  GROUP BY p.id 
                  ORDER BY total_views DESC 
                  LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $row = $result->fetch_assoc()) {
            return "Postingan dengan views terbanyak: '" . $row['content'] . "' (Views: " . $row['total_views'] . ")";
        } else {
            return "Tidak ada postingan.";
        }
        $stmt->close();
    }

    // 5. Pertanyaan tentang siapa saja yang menggunakan website ini
    if (strpos($message, "siapa saja yang menggunakan website ini") !== false) {
        $query = "SELECT username FROM users";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();

        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row['username'];
        }

        if (!empty($users)) {
            return "Pengguna website ini: " . implode(", ", $users);
        } else {
            return "Tidak ada pengguna terdaftar.";
        }
        $stmt->close();
    }

    // 6. Daftar pertanyaan yang bisa diajukan
    if (strpos($message, "apa saja yang bisa saya tanyakan") !== false) {
        return "Anda bisa menanyakan hal-hal berikut:\n" .
               "- Berapa banyak likes pada postingan dengan hashtag #...?\n" .
               "- Postingan mana yang paling banyak likes?\n" .
               "- Postingan mana yang paling banyak dilihat?\n" .
               "- Siapa saja yang menggunakan website ini?\n" .
               "- Apa saja yang bisa saya tanyakan?";
    }

    // 7. Postingan terakhir user
    if (strpos($message, "apa postingan terakhir saya") !== false) {
        $query = "SELECT content FROM posts WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $row = $result->fetch_assoc()) {
            return "Postingan terakhir Anda: " . $row['content'];
        } else {
            return "Anda belum membuat postingan.";
        }
        $stmt->close();
    }

    // 8. Tentang President University
    if (strpos($message, "apa itu president university") !== false) {
        return "President University adalah universitas swasta di Indonesia yang berlokasi di Jababeka, Cikarang. Universitas ini menawarkan berbagai program studi dan dikenal dengan pendekatan pembelajaran berbasis industri.";
    }

    // 9. Tentang Server-Side Internet Programming
    if (strpos($message, "apa itu server-side internet programming") !== false) {
        return "Server-Side Internet Programming adalah pemrograman yang dilakukan di sisi server untuk mengelola logika bisnis, interaksi dengan database, dan menghasilkan respons yang dikirim ke client. Contoh teknologi yang digunakan adalah PHP, Node.js, dan Python.";
    }

    // 10. Tentang DeepSeek AI
    if (strpos($message, "apa itu deepseek ai") !== false) {
        return "DeepSeek AI adalah platform kecerdasan buatan yang dirancang untuk membantu pengguna menemukan solusi terbaik dengan menggunakan teknologi machine learning dan natural language processing.";
    }

    // 11. Tentang Politik di Indonesia
    if (strpos($message, "bagaimana politik di indonesia") !== false) {
        return "Politik di Indonesia adalah sistem demokrasi dengan multipartai. Indonesia memiliki presiden sebagai kepala negara dan kepala pemerintahan. Pemilihan umum diadakan setiap 5 tahun untuk memilih presiden, anggota DPR, dan DPD.";
    }

    // 12. Riwayat aktivitas di website
    if (strpos($message, "apa riwayat aktivitas saya di website ini") !== false) {
        $query = "SELECT post_id, watched_at FROM history WHERE user_id = ? ORDER BY watched_at DESC LIMIT 5";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $history = [];
        while ($row = $result->fetch_assoc()) {
            $history[] = "Postingan ID " . $row['post_id'] . " pada " . $row['watched_at'];
        }

        if (!empty($history)) {
            return "Riwayat aktivitas Anda: " . implode(", ", $history);
        } else {
            return "Anda belum memiliki riwayat aktivitas.";
        }
        $stmt->close();
    }

    // 13. Informasi akun profile user
    if (strpos($message, "apa informasi akun profile saya") !== false) {
        $query = "SELECT username, email, created_at FROM users WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $row = $result->fetch_assoc()) {
            return "Informasi akun Anda:\n" .
                   "- Username: " . $row['username'] . "\n" .
                   "- Email: " . $row['email'] . "\n" .
                   "- Tanggal Bergabung: " . $row['created_at'];
        } else {
            return "Informasi akun tidak ditemukan.";
        }
        $stmt->close();
    }

    // 14. Apa yang bisa dilakukan di website ini
    if (strpos($message, "apa saja yang bisa saya lakukan di website ini") !== false) {
        return "Anda bisa melakukan hal-hal berikut di website ini:\n" .
               "- Membuat dan melihat postingan.\n" .
               "- Memberikan like atau dislike pada postingan.\n" .
               "- Mengomentari postingan.\n" .
               "- Melihat riwayat aktivitas Anda.\n" .
               "- Mengikuti pengguna lain.\n" .
               "- Menyimpan postingan sebagai bookmark.";
    }

    return null;
}

// ===============================
// Fungsi untuk mengirim pertanyaan ke DeepSeek (AI)
// ===============================
function askDeepSeek($message) {
    $api_key = getenv('DEEPSEEK_API_KEY'); // Ambil API Key dari environment variable
    if (!$api_key) {
        return "Maaf, terjadi kesalahan konfigurasi AI.";
    }

    $url = 'https://api.deepseek.com/v1/chat'; // Ganti dengan URL API DeepSeek

    // Data yang dikirim ke API
    $data = [
        'message' => $message,
        'model' => 'deepseek-chat', // Ganti dengan model yang tersedia
        'max_tokens' => 150 // Batasan panjang respons
    ];

    // Inisialisasi cURL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $api_key,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    // Eksekusi permintaan
    $response = curl_exec($ch);

    // Cek error cURL
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        file_put_contents("debug_log.txt", "Error cURL: $error_msg" . PHP_EOL, FILE_APPEND);
        curl_close($ch);
        return "Maaf, terjadi kesalahan saat menghubungi AI.";
    }

    curl_close($ch);

    // Debug: Catat respons dari API
    file_put_contents("debug_log.txt", "Response dari DeepSeek API: $response" . PHP_EOL, FILE_APPEND);

    // Decode respons JSON
    $result = json_decode($response, true);

    // Cek apakah respons valid
    if (!isset($result['response'])) {
        return "Maaf, saya tidak mengerti pertanyaan Anda.";
    }

    return $result['response'];
}

// ===============================
// Fungsi utilitas untuk ekstrak hashtag dari pesan
// ===============================
function extract_hashtag($message) {
    preg_match('/#(\w+)/', $message, $matches);
    return $matches[1] ?? null;
}
?>