<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Member Perpustakaan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .webcam-container { position: relative; width: 300px; height: 225px; margin: 0 auto; border: 2px solid #ccc; border-radius: 10px; overflow: hidden; background-color: #000; }
        video { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; }
        canvas { position: absolute; top: 0; left: 0; }
    </style>
</head>
<body>
<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white text-center h4">
                    Pendaftaran Member Baru
                </div>
                <div class="card-body">
                    <!-- TAMPILAN ERROR VALIDASI -->
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <strong>Pendaftaran Gagal:</strong>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <!-- PERHATIKAN: enctype="multipart/form-data" wajib ada karena kita upload file KTP -->
                    <form action="{{ route('register.process') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="row">
                            <!-- BAGIAN KIRI: Form Data Diri -->
                            <div class="col-md-6 border-end">
                                <h5>1. Data Identitas</h5>
                                <div class="mb-3">
                                    <label>Nama Lengkap</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label>Email & Password (Untuk Login)</label>
                                    <div class="input-group">
                                        <input type="email" name="email" class="form-control" placeholder="Email" required>
                                        <input type="password" name="password" class="form-control" placeholder="Password" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-6 mb-3">
                                        <label>Nomor Induk Kependudukan (NIK)</label>
                                        <input type="number" name="nik" class="form-control" required>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <label>Tanggal Lahir</label>
                                        <input type="date" name="birth_date" class="form-control" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label>Alamat Lengkap</label>
                                    <textarea name="address" class="form-control" rows="2" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label>No. HP / WhatsApp</label>
                                    <input type="text" name="phone" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label>Upload Foto KTP</label>
                                    <input type="file" name="ktp_image" class="form-control" accept="image/*" required>
                                </div>
                            </div>

                            <!-- BAGIAN KANAN: Kamera Face Recognition -->
                            <div class="col-md-6 text-center">
                                <h5>2. Verifikasi Wajah Biometrik</h5>
                                <p class="text-muted small">Pastikan wajah Anda terlihat jelas dan terang. Tunggu hingga kotak hijau muncul.</p>
                                
                                <div class="webcam-container mb-3" id="webcam-container">
                                    <video id="video" autoplay muted playsinline></video>
                                </div>

                                <button type="button" id="captureBtn" class="btn btn-warning fw-bold mb-3" disabled>
                                    Memuat AI Kamera...
                                </button>
                                
                                <!-- Input tersembunyi untuk dikirim ke Controller -->
                                <input type="hidden" name="face_descriptor" id="face_descriptor" required>
                                <input type="hidden" name="face_image_base64" id="face_image_base64" required>
                                
                                <hr>
                                <button type="submit" class="btn btn-success w-100 btn-lg" id="submitBtn" disabled>
                                    Selesaikan Pendaftaran
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script Face API -->
<script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.min.js"></script>
<script>
    const video = document.getElementById('video');
    const captureBtn = document.getElementById('captureBtn');
    const submitBtn = document.getElementById('submitBtn');
    const container = document.getElementById('webcam-container');
    let currentDescriptor = null;

    // 1. Load File AI Models dari folder public/models/
    Promise.all([
        faceapi.nets.ssdMobilenetv1.loadFromUri('/models'),
        faceapi.nets.faceLandmark68Net.loadFromUri('/models'),
        faceapi.nets.faceRecognitionNet.loadFromUri('/models')
    ]).then(startVideo).catch(err => {
        alert("Gagal memuat model AI. Pastikan folder models sudah ada di folder public.");
        console.error(err);
    });

    // 2. Menyalakan Webcam
    function startVideo() {
        navigator.mediaDevices.getUserMedia({ video: true })
            .then(stream => { 
                video.srcObject = stream; 
                captureBtn.innerText = "Mencari Wajah...";
            })
            .catch(err => {
                captureBtn.innerText = "Kamera tidak diizinkan!";
                console.error(err);
            });
    }

    // 3. Deteksi Wajah Real-time (Kotak Hijau)
    video.addEventListener('play', () => {
        const canvas = faceapi.createCanvasFromMedia(video);
        container.append(canvas);
        const displaySize = { width: video.clientWidth, height: video.clientHeight };
        faceapi.matchDimensions(canvas, displaySize);

        setInterval(async () => {
            const detections = await faceapi.detectSingleFace(video).withFaceLandmarks().withFaceDescriptor();
            canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
            
            if (detections) {
                const resizedDetections = faceapi.resizeResults(detections, displaySize);
                faceapi.draw.drawDetections(canvas, resizedDetections);
                
                // Simpan metrik biometrik ke memori
                currentDescriptor = detections.descriptor;
                
                // Aktifkan tombol Ambil Wajah
                captureBtn.disabled = false;
                captureBtn.classList.replace('btn-warning', 'btn-primary');
                captureBtn.innerText = "Scan Wajah Sekarang";
            } else {
                captureBtn.disabled = true;
                captureBtn.innerText = "Wajah tidak terdeteksi";
                captureBtn.classList.replace('btn-primary', 'btn-warning');
            }
        }, 300); // scan setiap 300ms
    });

    // 4. Tombol Scan Ditekan
    captureBtn.addEventListener('click', () => {
        if (currentDescriptor) {
            // A. Simpan biometrik ke input hidden sebagai JSON array
            document.getElementById('face_descriptor').value = JSON.stringify(Array.from(currentDescriptor));
            
            // B. Simpan screenshot wajah saat itu juga
            const canvasSnap = document.createElement('canvas');
            canvasSnap.width = video.videoWidth;
            canvasSnap.height = video.videoHeight;
            canvasSnap.getContext('2d').drawImage(video, 0, 0);
            document.getElementById('face_image_base64').value = canvasSnap.toDataURL('image/jpeg');

            // C. Ubah status UI
            alert('Wajah berhasil divalidasi!');
            submitBtn.disabled = false; // Tombol Submit aktif!
            captureBtn.innerText = "Wajah Terverifikasi ✅";
            captureBtn.classList.replace('btn-primary', 'btn-success');
            captureBtn.disabled = true; // Kunci tombol kamera agar tidak berulang
        }
    });
</script>
</body>
</html>