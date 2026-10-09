<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Email - Perpus Digital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="card shadow-sm border-0" style="max-width: 400px; width: 100%;">
        <div class="card-header bg-primary text-white text-center py-3 fs-5 fw-bold">
            Verifikasi Email
        </div>
        <div class="card-body p-4">
            
            @if(session('success'))
                <div class="alert alert-success small p-2 text-center">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger small p-2 text-center">{{ $errors->first() }}</div>
            @endif

            <p class="text-center text-muted small mb-4">
                Kami telah mengirimkan 6 digit kode OTP ke email:<br>
                <strong>{{ $email }}</strong><br>
                Silakan cek kotak masuk atau folder spam Anda.
            </p>

            <form method="POST" action="{{ route('verify.check') }}">
                @csrf
                <div class="mb-3">
                    <input type="text" name="otp" class="form-control form-control-lg text-center fw-bold fs-4" 
                           maxlength="6" inputmode="numeric" placeholder="••••••" autofocus required>
                </div>
                <button type="submit" class="btn btn-primary w-100 fw-bold py-2">Verifikasi Sekarang</button>
            </form>

            <form method="POST" action="{{ route('verify.resend') }}" class="mt-3 text-center">
                @csrf
                <button type="submit" class="btn btn-link text-decoration-none small text-muted">Belum menerima kode? Kirim ulang</button>
            </form>

        </div>
    </div>
</div>

</body>
</html>