@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-9">
        <h3 class="fw-bold mb-4">Scan QR Member & Buku </h3>

        @if(session('error')) 
            <div class="alert alert-danger shadow-sm">{{ session('error') }}</div> 
        @endif
        
        @if(session('success')) 
            <div class="alert alert-success shadow-sm">{{ session('success') }}</div> 
        @endif

        <div class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-body p-4 row">
                <!-- Kamera -->
                <div class="col-md-5 text-center border-end">
                    <h5 class="fw-bold mb-3 text-primary"><i class="fas fa-camera"></i> Arahkan QR Code</h5>
                    <div id="reader" class="bg-dark rounded shadow-sm" style="width: 100%;"></div>
                    <p class="text-muted small mt-2">Kamera otomatis membedakan QR Member dan QR Buku.</p>
                </div>

                <!-- Form 2 Langkah -->
                <div class="col-md-7 d-flex flex-column justify-content-center">
                    <form action="{{ route('transactions.verify_borrow') }}" method="POST" id="verifyBorrowForm">
                        @csrf
                        
                        <div class="alert alert-info py-2 small mb-4">
                            <strong>Aturan:</strong> Pindai QR Member terlebih dahulu, setelah terisi baru pindai QR Buku/Booking.
                        </div>

                        <!-- 1. Input Member -->
                        <div class="mb-4">
                            <label class="fw-bold text-dark"><span class="badge bg-secondary me-1">1</span> ID Member (MBR-xxx)</label>
                            <input type="text" name="member_code" id="member_code" value="{{ old('member_code') }}" class="form-control form-control-lg border-primary" placeholder="Menunggu scan member..." readonly required>
                        </div>
                        
                        <!-- 2. Input Buku/Booking -->
                        <div class="mb-4">
                            <label class="fw-bold text-dark"><span class="badge bg-secondary me-1">2</span> Kode Peminjaman (BK-xxx / BOOK-xxx)</label>
                            <input type="text" name="code" id="code" value="{{ old('code') }}" class="form-control form-control-lg border-success" placeholder="Menunggu scan buku..." required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 fw-bold btn-lg">Validasi Peminjaman</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL VERIFIKASI (MUNCUL JIKA SCAN BERHASIL VALIDASI DI CONTROLLER) -->
@if(isset($showModal) && $showModal)
<div class="modal fade show" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Konfirmasi Peminjaman</h5>
                <a href="{{ route('transactions.create') }}" class="btn-close btn-close-white"></a>
            </div>
            
            <form action="{{ route('transactions.process_borrow') }}" method="POST">
                @csrf
                <input type="hidden" name="type" value="{{ $type }}">
                
                <div class="modal-body">
                    <div class="alert alert-info py-2">
                        Peminjam: <strong>{{ $member->name }}</strong> ({{ $member->member_code }})
                    </div>

                    @if($type === 'cart')
                        <input type="hidden" name="transaction_id" value="{{ $transaction->id }}">
                        <p class="fw-bold mb-2">Daftar Buku (Booking Keranjang):</p>
                        <ul class="list-group list-group-flush mb-3">
                            @foreach($items as $item)
                                <li class="list-group-item px-0">
                                    <i class="fas fa-book me-2 text-primary"></i> 
                                    {{ $item->bookCopy->book->title }} <br>
                                    <small class="text-muted ms-4">Kode Fisik: {{ $item->bookCopy->copy_code }}</small>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <input type="hidden" name="book_copy_id" value="{{ $copy->id }}">
                        <input type="hidden" name="member_id" value="{{ $member->id }}"> 
                        
                        <p class="fw-bold mb-2">Buku Satuan:</p>
                        <div class="border rounded p-2 bg-light">
                            <i class="fas fa-book me-2 text-primary"></i> {{ $copy->book->title }} <br>
                            <small class="text-muted ms-4">Kode Fisik: {{ $copy->copy_code }}</small>
                        </div>
                    @endif
                </div>

                <div class="modal-footer">
                    <a href="{{ route('transactions.create') }}" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-success fw-bold">Setujui Peminjaman</button>
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
        let text = decodedText.trim().toUpperCase();
        
        // 1. JIKA YANG DISCAN ADALAH MEMBER (MBR-)
        if (text.startsWith('MBR-')) {
            document.getElementById('member_code').value = text;
            if (navigator.vibrate) navigator.vibrate(100); 
            console.log("Member terbaca: " + text);
            return; 
        } 
        
        // 2. JIKA YANG DISCAN ADALAH BUKU ATAU BOOKING (BK- / BOOK-)
        else if (text.startsWith('BK-') || text.startsWith('BOOK-')) {
            let memberField = document.getElementById('member_code').value;
            
            if (memberField === "") {
                alert("Mohon Scan QR Member dahulu !!!");
                return;
            }
            
            document.getElementById('code').value = text;
            
            if (window.html5QrCode && window.html5QrCode.isScanning) {
                window.html5QrCode.stop().then(() => {
                    document.getElementById('verifyBorrowForm').submit();
                }).catch(err => {
                    document.getElementById('verifyBorrowForm').submit();
                });
            } else {
                document.getElementById('verifyBorrowForm').submit();
            }
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