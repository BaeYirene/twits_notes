// File: js/login_face.js
// Script untuk login dengan face recognition

document.addEventListener('DOMContentLoaded', function() {
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    const captureBtn = document.getElementById('captureBtn');
    const faceDataInput = document.getElementById('faceData');
    const enableFaceRecognition = document.getElementById('enableFaceRecognition');
    const faceRecognitionSection = document.getElementById('faceRecognitionSection');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const faceOverlay = document.createElement('canvas');
    
    // Tambahkan overlay canvas untuk menampilkan fitur wajah
    faceOverlay.id = 'faceOverlay';
    faceOverlay.className = 'position-absolute top-0 start-0 w-100 h-100';
    faceOverlay.style.zIndex = '5';
    document.querySelector('.video-container').appendChild(faceOverlay);
    
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
            // Muat model face-api.js
            await faceHelper.loadModels();
            
            // Akses kamera
            const stream = await navigator.mediaDevices.getUserMedia({ 
                video: { 
                    width: { ideal: 640 },
                    height: { ideal: 480 },
                    facingMode: 'user'
                } 
            });
            
            video.srcObject = stream;
            
            // Setup deteksi wajah real-time
            video.addEventListener('play', () => {
                // Sesuaikan ukuran overlay canvas
                faceOverlay.width = video.videoWidth;
                faceOverlay.height = video.videoHeight;
                
                // Update overlay setiap 100ms
                setInterval(() => {
                    faceHelper.drawFaceFeatures(faceOverlay, video);
                }, 100);
            });
            
        } catch (error) {
            console.error('Error accessing camera:', error);
            showAlert('Tidak dapat mengakses kamera. Pastikan Anda memberikan izin.', 'danger');
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
    captureBtn.addEventListener('click', async function() {
        // Cek apakah username sudah diisi
        if (!usernameInput.value.trim()) {
            showAlert('Harap isi username terlebih dahulu', 'warning');
            return;
        }
        
        // Capture wajah
        const imageData = faceHelper.captureImageFromVideo(video);
        faceDataInput.value = imageData;
        
        // Deteksi wajah pada gambar yang diambil
        const tempImg = new Image();
        tempImg.src = imageData;
        
        tempImg.onload = async () => {
            const detection = await faceHelper.detectFace(tempImg);
            
            if (!detection) {
                showAlert('Tidak dapat mendeteksi wajah. Silakan coba lagi.', 'warning');
                return;
            }
            
            // Visual feedback
            showCaptureSuccess();
            
            // Opsional: Verifikasi wajah dengan server
            verifyFace(usernameInput.value, imageData);
        };
    });
    
    // Fungsi untuk mendeteksi dan mengenali wajah sebelum mengirim ke server
    async function recognizeFace(username, imageData) {
    const tempImg = new Image();
    tempImg.src = imageData;
    
    tempImg.onload = async () => {
        const detection = await faceHelper.detectFace(tempImg);

        if (!detection) {
            showAlert('Wajah tidak terdeteksi. Coba lagi.', 'warning');
            return;
        }

        // **Load data wajah dari database (server)**
        const userFaceDescriptors = await fetchFaceDataFromServer(username);
        if (!userFaceDescriptors) {
            showAlert('Data wajah tidak tersedia. Silakan daftar ulang.', 'danger');
            return;
        }

        // **Ubah gambar yang baru diambil menjadi face descriptor**
        const newFaceDescriptor = await faceHelper.getFaceDescriptor(tempImg);

        if (!newFaceDescriptor) {
            showAlert('Gagal membaca wajah. Coba lagi.', 'warning');
            return;
        }

        // **Bandingkan wajah dengan data yang tersimpan**
        const distance = faceapi.euclideanDistance(userFaceDescriptors, newFaceDescriptor);
        const threshold = 0.5; // Batas minimal kecocokan (lebih rendah = lebih ketat)

        if (distance < threshold) {
            showAlert('Wajah cocok! Login berhasil.', 'success');
            verifyFace(username, imageData); // Kirim ke server untuk validasi lebih lanjut
        } else {
            showAlert('Wajah tidak cocok! Login ditolak.', 'danger');
        }
    };
}

// Fungsi untuk mengambil data wajah dari server
async function fetchFaceDataFromServer(username) {
    try {
        const response = await fetch(`get_face_data.php?username=${username}`);
        const result = await response.json();
        return result.success ? new Float32Array(result.faceData) : null;
    } catch (error) {
        console.error('Error fetching face data:', error);
        return null;
    }
}

    
    // Verifikasi wajah dengan server
    async function verifyFace(username, imageData) {
        try {
            const response = await fetch('face_verify.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    username: username,
                    faceData: imageData
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Jika verifikasi berhasil, isi otomatis password
                showAlert(`Wajah terverifikasi! Kepercayaan: ${Math.round(result.confidence * 100)}%`, 'success');
                // Jika dalam implementasi nyata, ini bisa otomatis submit form atau bypass password
                passwordInput.disabled = true;
                passwordInput.placeholder = '********';
            } else {
                showAlert(`Verifikasi wajah gagal. ${result.message}`, 'danger');
            }
        } catch (error) {
            console.error('Error verifying face:', error);
            showAlert('Terjadi kesalahan saat memverifikasi wajah', 'danger');
        }
    }
    
    // Visual feedback untuk capture berhasil
    function showCaptureSuccess() {
        const overlay = document.createElement('div');
        overlay.className = 'position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-light bg-opacity-75';
        overlay.innerHTML = '<i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>';
        
        const videoContainer = document.querySelector('.video-container');
        videoContainer.appendChild(overlay);
        
        setTimeout(() => {
            videoContainer.removeChild(overlay);
        }, 2000);
    }
    
    // Tampilkan pesan alert
    function showAlert(message, type) {
        const alertBox = document.createElement('div');
        alertBox.className = `alert alert-${type} alert-dismissible fade show mt-3`;
        alertBox.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        const form = document.querySelector('form');
        form.insertBefore(alertBox, form.firstChild);
        
        // Hapus alert setelah 5 detik
        setTimeout(() => {
            alertBox.remove();
        }, 5000);
    }
});