// File: js/face_helper.js
// Helper functions untuk face recognition

class FaceHelper {
    constructor() {
        // Path ke models face-api.js
        this.modelsPath = 'models';
        // Toleransi pengenalan wajah (semakin kecil semakin akurat)
        this.matchThreshold = 0.6;
        // Status apakah model sudah dimuat
        this.modelsLoaded = false;
    }

    /**
     * Load model face-api.js
     */
    async loadModels() {
        if (this.modelsLoaded) return;
        
        try {
            await Promise.all([
                faceapi.nets.tinyFaceDetector.loadFromUri(this.modelsPath),
                faceapi.nets.faceLandmark68Net.loadFromUri(this.modelsPath),
                faceapi.nets.faceRecognitionNet.loadFromUri(this.modelsPath)
            ]);
            
            console.log('Face-api models loaded successfully');
            this.modelsLoaded = true;
            return true;
        } catch (error) {
            console.error('Failed to load face-api models:', error);
            return false;
        }
    }

    /**
     * Deteksi wajah pada gambar
     * @param {HTMLImageElement|HTMLVideoElement} input - Elemen input gambar/video
     * @returns {Promise} - Promise yang menghasilkan deteksi wajah
     */
    async detectFace(input) {
        if (!this.modelsLoaded) {
            await this.loadModels();
        }
        
        try {
            const detections = await faceapi.detectSingleFace(input, 
                new faceapi.TinyFaceDetectorOptions()
            ).withFaceLandmarks().withFaceDescriptor();
            
            return detections;
        } catch (error) {
            console.error('Face detection error:', error);
            return null;
        }
    }

    /**
     * Ekstrak deskriptor wajah dari gambar
     * @param {string} imageUrl - URL gambar wajah
     * @returns {Promise} - Promise yang menghasilkan deskriptor wajah
     */
    async getFaceDescriptorFromImage(imageUrl) {
        return new Promise(async (resolve, reject) => {
            const img = new Image();
            img.crossOrigin = 'anonymous';
            
            img.onload = async () => {
                const detection = await this.detectFace(img);
                if (detection) {
                    resolve(detection.descriptor);
                } else {
                    reject('No face detected in the image');
                }
            };
            
            img.onerror = (error) => {
                reject('Error loading image: ' + error);
            };
            
            img.src = imageUrl;
        });
    }

    /**
     * Bandingkan dua deskriptor wajah
     * @param {Float32Array} descriptor1 - Deskriptor wajah pertama
     * @param {Float32Array} descriptor2 - Deskriptor wajah kedua
     * @returns {number} - Tingkat kecocokan (0-1, semakin kecil semakin cocok)
     */
    compareFaces(descriptor1, descriptor2) {
        if (!descriptor1 || !descriptor2) return 1.0;
        return faceapi.euclideanDistance(descriptor1, descriptor2);
    }

    /**
     * Cek apakah dua wajah cocok
     * @param {Float32Array} descriptor1 - Deskriptor wajah pertama
     * @param {Float32Array} descriptor2 - Deskriptor wajah kedua
     * @returns {boolean} - True jika wajah cocok
     */
    isMatchingFace(descriptor1, descriptor2) {
        const distance = this.compareFaces(descriptor1, descriptor2);
        return distance < this.matchThreshold;
    }

    /**
     * Ambil gambar dari video dan konversi ke base64
     * @param {HTMLVideoElement} video - Elemen video
     * @returns {string} - Base64 string dari gambar
     */
    captureImageFromVideo(video) {
        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        
        const context = canvas.getContext('2d');
        context.drawImage(video, 0, 0, canvas.width, canvas.height);
        
        return canvas.toDataURL('image/png');
    }

    /**
     * Menampilkan fitur wajah pada canvas
     * @param {HTMLCanvasElement} canvas - Elemen canvas
     * @param {HTMLImageElement|HTMLVideoElement} input - Elemen input gambar/video
     */
    async drawFaceFeatures(canvas, input) {
        if (!this.modelsLoaded) {
            await this.loadModels();
        }
        
        const displaySize = { width: input.width, height: input.height };
        faceapi.matchDimensions(canvas, displaySize);
        
        const detections = await faceapi.detectAllFaces(input, 
            new faceapi.TinyFaceDetectorOptions()
        ).withFaceLandmarks();
        
        const resizedDetections = faceapi.resizeResults(detections, displaySize);
        canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
        faceapi.draw.drawDetections(canvas, resizedDetections);
        faceapi.draw.drawFaceLandmarks(canvas, resizedDetections);
    }
}

// Inisialisasi helper
const faceHelper = new FaceHelper();