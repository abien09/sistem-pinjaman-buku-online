@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-9">
        <h3 class="fw-bold mb-4">Scan Pengembalian Buku</h3>

        @if(session('error')) 
            <div class="alert alert-danger shadow-sm">{{ session('error') }}</div> 
        @endif
        
        @if(session('success')) 
            <div class="alert alert-success shadow-sm">{{ session('success') }}</div> 
        @endif

        <div class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-body p-4 row">
                <!-- Area Kamera -->
                <div class="col-md-5 text-center border-end">
                    <h5 class="fw-bold mb-3 text-primary"><i class="fas fa-camera"></i> Arahkan QR Code</h5>
                    <div id="reader" class="bg-dark rounded shadow-sm" style="width: 100%;"></div>
                    <p class="text-muted small mt-2">Scan QR Buku Satuan atau QR Pengembalian Sekaligus milik member.</p>
                </div>

                <!-- Area Form Input -->
                <div class="col-md-7 d-flex flex-column justify-content-center">
                    <form action="{{ route('transactions.verify_return') }}" method="POST" id="returnForm">
                        @csrf
                        
                        <div class="alert alert-info py-2 small mb-4">
                            <strong>Info:</strong> Admin dapat memindai QR buku satu per satu atau QR massal (pengembalian sekaligus dari member).
                        </div>

                        <div class="mb-4">
                            <label class="fw-bold text-dark">Kode Eksemplar Buku / QR Pengembalian</label>
                            <input type="text" name="copy_code" id="copy_code" class="form-control form-control-lg border-success" placeholder="Scan QR atau masukkan kode..." required>
                        </div>

                        <button type="submit" class="btn btn-success w-100 fw-bold btn-lg">Verifikasi Pengembalian</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL VERIFIKASI PENGEMBALIAN (SATUAN ATAU SEKALIGUS) -->
@if(isset($showModal) && $showModal)
<div class="modal fade show" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-check-circle text-success me-2"></i> Konfirmasi Pengembalian Buku</h5>
                <a href="{{ route('transactions.return') }}" class="btn-close btn-close-white"></a>
            </div>
            
            <form action="{{ route('transactions.process_return') }}" method="POST">
                @csrf
                
                <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto;">
                    
                    {{-- JIKA PENGEMBALIAN SEKALIGUS (BULK) --}}
                    @if(isset($returnItems) && $returnItems->count() > 0)
                        <div class="alert alert-info py-2 mb-3">
                            Peminjam: <strong>{{ $member->name }}</strong> ({{ $member->member_code }})<br>
                            <small>{{ $returnItems->count() }} buku aktif akan diproses sekaligus.</small>
                        </div>

                        <input type="hidden" name="is_bulk" value="1">

                        @foreach($returnItems as $item)
                            @php 
                                $tx = $item['transaction']; 
                                $days = $item['daysBorrowed'];
                                $fee = $item['lateFee'];
                            @endphp
                            <input type="hidden" name="transaction_ids[]" value="{{ $tx->id }}">

                            <div class="border rounded-4 p-3 mb-3 bg-light shadow-sm">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <span class="badge bg-secondary font-monospace mb-1">{{ $tx->bookCopy->copy_code }}</span>
                                        <h6 class="fw-bold mb-0 text-dark">{{ $tx->bookCopy->book->title }}</h6>
                                        <small class="text-muted">Dipinjam {{ \Carbon\Carbon::parse($tx->borrow_date)->format('d M Y') }} • {{ $days }} Hari</small>
                                    </div>
                                    <div>
                                        @if($days > 30)
                                            <span class="badge bg-danger">Telat (Denda: Rp {{ number_format($fee, 0, ',', '.') }})</span>
                                        @else
                                            <span class="badge bg-success">Tidak terlambat</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="mt-2">
                                    <label class="form-label fw-bold small text-secondary">Kondisi buku:</label>
                                    <select name="conditions[{{ $tx->id }}]" class="form-select form-select-sm border-primary" required>
                                        <option value="available" selected>Bagus / layak dipinjamkan lagi</option>
                                        <option value="damaged">Rusak (denda 100% harga buku)</option>
                                        <option value="lost">Hilang (denda 100% harga buku)</option>
                                    </select>
                                </div>
                            </div>
                        @endforeach

                    {{-- JIKA PENGEMBALIAN SATUAN (SINGLE) --}}
                    @elseif(isset($transaction))
                        <input type="hidden" name="transaction_id" value="{{ $transaction->id }}">
                        
                        <div class="alert alert-info py-2 mb-3">
                            Peminjam: <strong>{{ $transaction->user->name }}</strong> ({{ $transaction->user->member_code }})
                        </div>

                        <div class="d-flex align-items-center mb-3">
                            <div class="me-3" style="width: 60px; height: 80px; flex-shrink: 0;">
                                @if($transaction->bookCopy->book->cover_image)
                                    <img src="{{ asset('storage/' . $transaction->bookCopy->book->cover_image) }}" alt="Cover" class="w-100 h-100 object-fit-cover rounded shadow-sm">
                                @else
                                    <div class="bg-secondary text-white w-100 h-100 d-flex align-items-center justify-content-center rounded small">No Cover</div>
                                @endif
                            </div>
                            <div>
                                <span class="badge bg-secondary mb-1 font-monospace">{{ $transaction->bookCopy->copy_code }}</span>
                                <h5 class="fw-bold text-dark mb-1">{{ $transaction->bookCopy->book->title }}</h5>
                                <small class="text-muted">Durasi Pinjam: <strong>{{ $daysBorrowed }} Hari</strong></small>
                            </div>
                        </div>

                        @if($daysBorrowed > 30)
                            <div class="alert alert-danger p-2 small">
                                <i class="fas fa-exclamation-triangle me-1"></i> Terlambat! Estimasi Denda Keterlambatan: <strong>Rp {{ number_format($lateFee, 0, ',', '.') }}</strong>
                            </div>
                        @else
                            <div class="alert alert-success p-2 small">
                                <i class="fas fa-check-circle me-1"></i> Tepat waktu. Tanpa denda.
                            </div>
                        @endif

                        <div class="mb-3">
                            <label class="fw-bold mb-2 small">Kondisi Fisik Buku:</label>
                            <select name="condition" class="form-select border-primary" required>
                                <option value="available" selected>Bagus / layak dipinjamkan lagi</option>
                                <option value="damaged">Rusak (denda 100% harga buku)</option>
                                <option value="lost">Hilang (denda 100% harga buku)</option>
                            </select>
                        </div>
                    @endif

                </div>

                <div class="modal-footer bg-light px-4 py-3">
                    <a href="{{ route('transactions.return') }}" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-success fw-bold px-4">Konfirmasi & Selesaikan Pengembalian</button>
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
    function onScanSuccess(decodedText, decodedResult) {
        let text = decodedText.trim();
        document.getElementById('copy_code').value = text;
        
        if (window.html5QrCode && window.html5QrCode.isScanning) {
            window.html5QrCode.stop().then(() => {
                document.getElementById('returnForm').submit();
            }).catch(err => {
                document.getElementById('returnForm').submit();
            });
        } else {
            document.getElementById('returnForm').submit();
        }
    }

    function onScanFailure(error) {
        // Abaikan error frame kosong
    }

    document.addEventListener("DOMContentLoaded", function () {
        window.html5QrCode = new Html5QrcodeScanner(
            "reader", 
            { 
                fps: 15, 
                qrbox: { width: 250, height: 250 },
                aspectRatio: 1.0
            }, 
            false
        );
        window.html5QrCode.render(onScanSuccess, onScanFailure);
    });
</script>
@endpush