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
                            <div class="alert alert-danger mt-3 mb-0 p-2 small border-danger border-start border-4">
                                <i class="fas fa-exclamation-triangle me-1"></i> <strong>Terlambat!</strong> (Lebih {{ $daysBorrowed - 30 }} hari).<br>
                                
                                @if(isset($isMaxLateFee) && $isMaxLateFee)
                                    <div class="mt-2 mb-1 p-2 bg-danger text-white rounded fw-bold">
                                        <i class="fas fa-ban me-1"></i> Denda mencapai batas maksimal (100% Harga Buku)!
                                    </div>
                                    <span class="text-dark d-block">Admin harap tegur member secara lisan atau hubungi kontak terdaftar.</span>
                                @endif
                                
                                <span class="d-block mt-2">Estimasi Denda: <strong class="text-danger fs-5">Rp {{ number_format($lateFee, 0, ',', '.') }}</strong></span>
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
                        <select name="condition" id="conditionSelect" class="form-select border-primary" required>
                            <option value="available" selected>✅ Bagus / Layak Dipinjamkan Lagi</option>
                            <option value="damaged">⚠️ Lecek / Rusak / Cacat (Denda 100% dari Harga Buku)</option>
                            <option value="lost">❌ Hilang / Tidak Dikembalikan Penuh</option>
                        </select>
                        <small class="text-muted d-block mt-1">Pilih "Rusak" atau "Hilang" agar buku tidak bisa dipinjam oleh member lain.</small>

                        <!-- Notifikasi Dinamis Kondisi Rusak (Sembunyi by Default) -->
                        <div id="damagedWarning" class="alert alert-warning mt-2 mb-0 p-2 small d-none border-warning border-start border-4 shadow-sm">
                            <i class="fas fa-exclamation-circle me-1"></i> <strong>Denda Kerusakan Fisik:</strong><br> 
                            Member akan dikenakan denda tambahan sebesar <strong>100% dari harga buku</strong> (Rp {{ number_format($transaction->bookCopy->book->price ?? 0, 0, ',', '.') }}).
                        </div>

                        <!-- Notifikasi Dinamis Kondisi Hilang (Sembunyi by Default) -->
                        <div id="lostInfo" class="alert alert-danger mt-2 mb-0 p-2 small d-none border-danger border-start border-4 shadow-sm">
                            <i class="fas fa-user-slash me-1"></i> <strong>Buku Dinyatakan Hilang!</strong><br> 
                            Tagihan Denda 100% (Rp {{ number_format($transaction->bookCopy->book->price ?? 0, 0, ',', '.') }}).
                            <hr class="my-1 border-danger opacity-25">
                            <div class="bg-white p-2 rounded text-dark">
                                <strong><i class="fas fa-address-card text-secondary me-1"></i> Hubungi Member:</strong><br>
                                👤 Nama: {{ $transaction->user->name }}<br>
                                📞 Telp/WA: {{ $transaction->user->phone ?? 'Tidak ada data telp' }}<br>
                                🏠 Alamat: {{ $transaction->user->address ?? 'Tidak ada data alamat' }}
                            </div>
                        </div>
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

            // 3. Beritahu admin dengan suara/alert singkat
            alert("QR Code terbaca: " + decodedText + "\nKlik OK untuk melanjutkan verifikasi.");
            document.getElementById('returnForm').submit();
        }
    }

    // Inisialisasi Scanner
    html5QrcodeScanner = new Html5QrcodeScanner("reader", { fps: 10, qrbox: {width: 250, height: 250} }, false);
    html5QrcodeScanner.render(onScanSuccess);

    // ==========================================
    // JS UNTUK MENAMPILKAN PERINGATAN KONDISI BUKU
    // ==========================================
    document.addEventListener('DOMContentLoaded', function() {
        const conditionSelect = document.getElementById('conditionSelect');
        const damagedWarning = document.getElementById('damagedWarning');
        const lostInfo = document.getElementById('lostInfo');

        if (conditionSelect) {
            conditionSelect.addEventListener('change', function() {
                // Sembunyikan notifikasi setiap ada perubahan opsi
                damagedWarning.classList.add('d-none');
                lostInfo.classList.add('d-none');

                // Tampilkan notifikasi yang sesuai
                if (this.value === 'damaged') {
                    damagedWarning.classList.remove('d-none');
                } else if (this.value === 'lost') {
                    lostInfo.classList.remove('d-none');
                }
            });
        }
    });
</script>
@endpush