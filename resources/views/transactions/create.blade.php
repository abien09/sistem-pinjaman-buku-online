@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <h3 class="fw-bold mb-4">Scan Peminjaman Buku</h3>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4 row">
                <!-- Area Kamera -->
                <div class="col-md-6 text-center border-end">
                    <h5 class="fw-bold mb-3"><i class="fas fa-camera"></i> Scanner Aktif</h5>
                    <div id="reader" style="width: 100%;" class="bg-dark rounded overflow-hidden shadow-sm"></div>
                    <p class="small text-muted mt-2">Arahkan QR Code Member, lalu QR Code Buku ke kamera.</p>
                </div>

                <!-- Area Form Input (Otomatis Terisi) -->
                <div class="col-md-6">
                    <form action="{{ route('transactions.store') }}" method="POST">
                        @csrf
                        <div class="mb-4">
                            <label class="fw-bold">1. Kode Member (QR/Manual)</label>
                            <input type="text" name="member_code" id="member_code" class="form-control form-control-lg bg-light" placeholder="Scan QR Member..." required>
                        </div>
                        <div class="mb-4">
                            <label class="fw-bold">2. Kode Eksemplar Buku (QR/Manual)</label>
                            <input type="text" name="copy_code" id="copy_code" class="form-control form-control-lg bg-light" placeholder="Scan QR Buku..." required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100">Proses Peminjaman</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode"></script>
<script>
    function onScanSuccess(decodedText, decodedResult) {
        // Cek apakah hasil scan itu kode Member atau kode Buku
        if (decodedText.startsWith('MBR-')) {
            document.getElementById('member_code').value = decodedText;
            document.getElementById('member_code').classList.add('is-valid');
            alert('Member terdeteksi: ' + decodedText + '\nSekarang scan QR Buku!');
        } else if (decodedText.startsWith('BK-')) {
            document.getElementById('copy_code').value = decodedText;
            document.getElementById('copy_code').classList.add('is-valid');
            alert('Buku terdeteksi: ' + decodedText + '\nKlik Proses Peminjaman!');
        } else {
            console.log('QR tidak dikenali sistem: ' + decodedText);
        }
    }

    // Mulai kamera (kamera belakang/utama jika di HP, atau webcam di laptop)
    let html5QrcodeScanner = new Html5QrcodeScanner("reader", { fps: 10, qrbox: {width: 250, height: 250} }, false);
    html5QrcodeScanner.render(onScanSuccess);
</script>
@endpush