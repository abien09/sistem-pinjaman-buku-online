<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Member Perpustakaan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                    
                    <form action="{{ route('register.process') }}" method="POST" enctype="multipart/form-data" id="registrationForm" novalidate>
                        @csrf
                        
                        <div class="row">
                            <!-- BAGIAN KIRI: Form Data Diri -->
                            <div class="col-md-6 border-end">
                                <h5>Data Identitas</h5>
                                <div class="mb-3">
                                    <label>Nama Lengkap</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label>Email</label>
                                    <input type="email" name="email" class="form-control" placeholder="Email" required>
                                </div>
                                <div class="mb-3">
                                    <label>Password</label>
                                    <div class="input-group">
                                        <input type="password" name="password" id="registerPassword" class="form-control" placeholder="Password" required>
                                        <button class="btn btn-outline-secondary" type="button" id="toggleRegisterPassword">
                                            <i class="fas fa-eye" id="registerEyeIcon"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-6 mb-3">
                                        <label for="nik">Nomor Induk Kependudukan (NIK)</label>
                                        <input type="text" name="nik" id="nik" class="form-control" inputmode="numeric" maxlength="16" required>
                                        <div class="invalid-feedback">
                                            NIK harus terdiri dari tepat 16 digit angka.
                                        </div>
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
                                    <label for="phone" class="form-label">No. HP / WhatsApp</label>
                                    <input type="tel" name="phone" id="phone" class="form-control" value="{{ old('phone') }}" placeholder="Contoh: 081234567890" inputmode="tel" maxlength="15" required>
                                    <div class="invalid-feedback">
                                        Nomor HP harus berisi 10–15 digit angka. Boleh diawali tanda +.
                                    </div>
                                    <div class="form-text">Masukkan nomor HP yang aktif dan dapat dihubungi.</div>
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

<!-- Script Face API, Validasi Input & Toggle Password -->
<script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.min.js"></script>
<script>
    // Script Toggle Show/Hide Password Register
    const toggleRegisterPassword = document.getElementById('toggleRegisterPassword');
    const registerPassword = document.getElementById('registerPassword');
    const registerEyeIcon = document.getElementById('registerEyeIcon');

    toggleRegisterPassword.addEventListener('click', function () {
        const type = registerPassword.getAttribute('type') === 'password' ? 'text' : 'password';
        registerPassword.setAttribute('type', type);
        registerEyeIcon.classList.toggle('fa-eye');
        registerEyeIcon.classList.toggle('fa-eye-slash');
    });

    // Validasi No HP dan NIK
    const registrationForm = document.getElementById('registrationForm');
    const phoneInput = document.getElementById('phone');
    const nikInput = document.getElementById('nik');

    function validatePhone() {
        const phone = phoneInput.value.trim();
        const valid = /^\+?[0-9]{10,15}$/.test(phone);

        phoneInput.classList.toggle('is-invalid', !valid);
        phoneInput.classList.toggle('is-valid', valid);
        phoneInput.setCustomValidity(valid ? '' : 'Nomor HP harus berisi 10–15 digit angka.');
        return valid;
    }

    function validateNik() {
        const nik = nikInput.value.trim();
        const valid = /^[0-9]{16}$/.test(nik);

        nikInput.classList.toggle('is-invalid', !valid);
        nikInput.classList.toggle('is-valid', valid);
        nikInput.setCustomValidity(valid ? '' : 'NIK harus terdiri dari tepat 16 digit angka.');
        return valid;
    }

    phoneInput.addEventListener('input', validatePhone);
    phoneInput.addEventListener('blur', validatePhone);

    nikInput.addEventListener('input', validateNik);
    nikInput.addEventListener('blur', validateNik);

    registrationForm.addEventListener('submit', function (event) {
        const phoneValid = validatePhone();
        const nikValid = validateNik();

        if (!phoneValid || !nikValid) {
            event.preventDefault();
            if (!phoneValid) {
                phoneInput.reportValidity();
                phoneInput.focus();
            } else {
                nikInput.reportValidity();
                nikInput.focus();
            }
        }
    });

    // Script Face API
    const video = document.getElementById('video');
    const captureBtn = document.getElementById('captureBtn');
    const submitBtn = document.getElementById('submitBtn');
    const container = document.getElementById('webcam-container');
    let currentDescriptor = null;

    Promise.all([
        faceapi.nets.ssdMobilenetv1.loadFromUri('/models'),
        faceapi.nets.faceLandmark68Net.loadFromUri('/models'),
        faceapi.nets.faceRecognitionNet.loadFromUri('/models')
    ]).then(startVideo).catch(err => {
        alert("Gagal memuat model AI. Pastikan folder models sudah ada di folder public.");
        console.error(err);
    });

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
                
                currentDescriptor = detections.descriptor;
                
                captureBtn.disabled = false;
                captureBtn.classList.replace('btn-warning', 'btn-primary');
                captureBtn.innerText = "Scan Wajah Sekarang";
            } else {
                captureBtn.disabled = true;
                captureBtn.innerText = "Wajah tidak terdeteksi";
                captureBtn.classList.replace('btn-primary', 'btn-warning');
            }
        }, 300);
    });

    captureBtn.addEventListener('click', () => {
        if (currentDescriptor) {
            document.getElementById('face_descriptor').value = JSON.stringify(Array.from(currentDescriptor));
            
            const canvasSnap = document.createElement('canvas');
            canvasSnap.width = video.videoWidth;
            canvasSnap.height = video.videoHeight;
            canvasSnap.getContext('2d').drawImage(video, 0, 0);
            document.getElementById('face_image_base64').value = canvasSnap.toDataURL('image/jpeg');

            alert('Wajah berhasil divalidasi!');
            submitBtn.disabled = false;
            captureBtn.innerText = "Wajah Terverifikasi ✅";
            captureBtn.classList.replace('btn-primary', 'btn-success');
            captureBtn.disabled = true;
        }
    });
</script>
</body>
</html>