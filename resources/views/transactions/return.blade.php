@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <h3 class="fw-bold mb-4">Scan Pengembalian Buku</h3>

        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4 row">
                <!-- Area Kamera -->
                <div class="col-md-6 text-center border-end">
                    <h5 class="fw-bold mb-3"><i class="fas fa-camera text-primary"></i> Scanner Aktif</h5>
                    <div id="reader" style="width: 100%;" class="bg-dark rounded overflow-hidden shadow-sm"></div>
                    <p class="small text-muted mt-2">Arahkan stiker QR Code Buku ke kamera.</p>
                </div>

                <!-- Area Form Input -->
                <div class="col-md-6 d-flex flex-column justify-content-center">
                    <form id="returnForm" action="{{ route('transactions.verify_return') }}" method="POST">
                        @csrf
                        <div class="mb-4">
                            <label class="fw-bold">Kode Eksemplar Buku (QR/Manual)</label>
                            <input type="text" name="copy_code" id="copy_code" class="form-control form-control-lg bg-light" placeholder="Scan QR Buku..." required>
                        </div>
                        <!-- Tombol Submit Manual yang Aman dari Lempar Session -->
                        <button type="submit" id="submitBtn" class="btn btn-success btn-lg w-100 shadow-sm">
                            <i class="fas fa-search me-1"></i> Verifikasi Pengembalian
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL POPUP VERIFIKASI PENGEMBALIAN -->
@if(isset($showModal) && $showModal)
<div class="modal fade show" id="returnModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-check-circle text-success me-2"></i> Konfirmasi Pengembalian Buku</h5>
                <a href="{{ route('transactions.return') }}" class="btn-close btn-close-white"></a>
            </div>
            <form action="{{ route('transactions.process_return') }}" method="POST">
                @csrf
                <input type="hidden" name="transaction_id" value="{{ $transaction->id }}">
                <div class="modal-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="me-3" style="width: 70px; height: 90px; flex-shrink: 0;">
                            @if($transaction->bookCopy->book->cover_image)
                                <img src="{{ asset('storage/' . $transaction->bookCopy->book->cover_image) }}" alt="Cover" class="w-100 h-100 object-fit-cover rounded shadow-sm">
                            @else
                                <div class="bg-secondary text-white w-100 h-100 d-flex align-items-center justify-content-center rounded small">No Cover</div>
                            @endif
                        </div>
                        <div>
                            <span class="badge bg-secondary mb-1 font-monospace">{{ $transaction->bookCopy->copy_code }}</span>
                            <h5 class="fw-bold text-dark mb-1">{{ $transaction->bookCopy->book->title }}</h5>
                            <p class="text-muted small mb-0"><i class="fas fa-user me-1"></i> Peminjam: <strong class="text-dark">{{ $transaction->user->name }}</strong></p>
                        </div>
                    </div>

                    <hr>

                    <!-- Rincian Waktu & Denda -->
                    <div class="bg-light p-3 rounded-3 mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">Tanggal Peminjaman:</span>
                            <strong class="small">{{ \Carbon\Carbon::parse($transaction->borrow_date)->format('d M Y') }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">Total Durasi Pinjam:</span>
                            <strong class="small text-primary">{{ $daysBorrowed }} Hari</strong>
                        </div>

                        @if($daysBorrowed > 30)
                            <div class="alert alert-danger mt-3 mb-0 p-2 small">
                                <i class="fas fa-exclamation-triangle me-1"></i> <strong>Terlambat!</strong> Melebihi batas 30 hari (Lebih {{ $daysBorrowed - 30 }} hari).<br>
                                Estimasi Denda: <strong class="text-danger fs-6">Rp {{ number_format($lateFee, 0, ',', '.') }}</strong>
                            </div>
                        @else
                            <div class="alert alert-success mt-3 mb-0 p-2 small">
                                <i class="fas fa-check-circle me-1"></i> Peminjaman tepat waktu (Batas maksimal 30 hari). <strong>Tanpa Denda.</strong>
                            </div>
                        @endif
                    </div>
                    <!-- Pengecekan Kondisi Fisik oleh Admin -->
                    <div class="mb-3 text-start">
                        <label class="fw-bold mb-2">Kondisi Fisik Buku Saat Diterima:</label>
                        <select name="condition" class="form-select border-primary" required>
                            <option value="available" selected>✅ Bagus / Layak Dipinjamkan Lagi</option>
                            <option value="damaged">⚠️ Rusak / Cacat</option>
                            <option value="lost">❌ Hilang / Tidak Dikembalikan Penuh</option>
                        </select>
                        <small class="text-muted">Pilih "Rusak" atau "Hilang" agar buku tidak bisa dipinjam oleh member lain.</small>
                    </div>
                </div>
                <div class="modal-footer bg-light px-4 py-3">
                    <a href="{{ route('transactions.return') }}" class="btn btn-outline-secondary">Batal</a>
                    <button type="submit" class="btn btn-success px-4 fw-bold"><i class="fas fa-check me-1"></i> Konfirmasi & Selesaikan Pengembalian</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode"></script>
<script>
    let html5QrcodeScanner;

    function onScanSuccess(decodedText, decodedResult) {
        if (decodedText.startsWith('BK-')) {
            // 1. Masukkan kode eksemplar ke dalam input text
            document.getElementById('copy_code').value = decodedText;
            
            // 2. Matikan kamera scanner agar bersih
            if (html5QrcodeScanner) {
                html5QrcodeScanner.clear().catch(error => {
                    console.error("Gagal menghentikan scanner.", error);
                });
            }

            // 3. Beritahu admin dengan suara/alert singkat, lalu biarkan Admin klik tombol "Verifikasi Pengembalian" 
            // ATAU kita beri jeda setengah detik agar token aman sebelum dikirim secara aman
            alert("QR Code terbaca: " + decodedText + "\nKlik OK untuk melanjutkan verifikasi.");
            document.getElementById('returnForm').submit();
        }
    }

    // Inisialisasi Scanner
    html5QrcodeScanner = new Html5QrcodeScanner("reader", { fps: 10, qrbox: {width: 250, height: 250} }, false);
    html5QrcodeScanner.render(onScanSuccess);
</script>
@endpush