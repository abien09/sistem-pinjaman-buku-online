@extends('layouts.app')

@section('content')
<div class="mb-3">
    <a href="{{ route('dashboard') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Kembali ke Katalog</a>
</div>

<!-- PERINGATAN JIKA PERPUSTAKAAN TUTUP / HARI SENIN -->
@php
    $isMonday = now()->isMonday();
    $setting = \App\Models\Setting::where('key', 'store_status')->first();
    $storeStatus = $setting ? $setting->value : 'open';
    $isClosed = $isMonday || ($storeStatus === 'closed');
    $closedReason = $isMonday ? 'Perpustakaan libur setiap hari Senin.' : 'Perpustakaan sedang ditutup oleh Admin.';
@endphp

@if($isClosed)
    <div class="alert alert-danger shadow-sm rounded-4 p-4 mb-4 d-flex align-items-center">
        <i class="fas fa-store-slash fa-2x me-3"></i>
        <div>
            <h5 class="fw-bold mb-1">Perpustakaan Sedang Tutup</h5>
            <p class="mb-0">{{ $closedReason }} Saat ini Anda hanya dapat melihat informasi buku (Read-Only) dan belum dapat melakukan peminjaman fisik.</p>
        </div>
    </div>
@endif

<!-- KARTU UTAMA INFORMASI BUKU -->
<div class="card shadow-sm border-0 rounded-4 mb-4">
    <div class="card-body p-4">
        <div class="row">
            <!-- Kolom Kiri: Gambar Cover Buku -->
            <div class="col-md-3 text-center mb-3 mb-md-0">
                <div class="bg-light rounded-4 overflow-hidden shadow-sm" style="height: 340px;">
                    @if($book->cover_image)
                        <img src="{{ asset('storage/' . $book->cover_image) }}" alt="Cover" class="w-100 h-100 object-fit-cover">
                    @else
                        <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                            <i class="fas fa-book fa-4x"></i>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Kolom Kanan: Judul, Penulis, Sinopsis, dan Keterangan -->
            <div class="col-md-9 d-flex flex-column justify-content-between">
                <div>
                    <span class="badge bg-dark mb-2">{{ $book->category->name }}</span>
                    <h2 class="fw-bold text-dark mb-2">{{ $book->title }}</h2>
                    <p class="text-muted mb-3 fs-5">
                        <i class="fas fa-pen-nib me-2 text-primary"></i> {{ $book->author }}
                        <i class="fas fa-building me-1 text-success"></i> Penerbit: <strong>{{ $book->publisher ?? '-' }}</strong>
                    </p>

                    <hr>
                    
                    <!-- Sinopsis / Deskripsi -->
                    <h6 class="fw-bold text-secondary text-uppercase small mb-2">Sinopsis Buku:</h6>
                    <p class="text-secondary" style="line-height: 1.6;">
                        {{ $book->description ?? 'Tidak ada sinopsis atau deskripsi untuk buku ini.' }}
                    </p>
                </div>

                <!-- Tepat di bawah sinopsis: Keterangan & Ketentuan Peminjaman -->
                <div class="mt-4 pt-3 border-top bg-light p-3 rounded-4">
                    <h6 class="fw-bold text-dark mb-2"><i class="fas fa-info-circle text-primary me-2"></i> Ketentuan & Informasi Peminjaman:</h6>
                    <div class="row text-muted small">
                        <div class="col-md-6 mb-1">
                            <i class="fas fa-clock text-warning me-1"></i> Batas maksimal peminjaman: <strong>30 hari</strong>
                        </div>
                        <div class="col-md-6 mb-1">
                            <i class="fas fa-exclamation-triangle text-danger me-1"></i> Denda keterlambatan: <strong>10% dari harga buku/minggu</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- BAGIAN BAWAH: DAFTAR EKSEMPLAR & QR CODE -->
<div class="card shadow-sm border-0 rounded-4">
    <div class="card-header bg-white py-3 border-0">
        <h5 class="fw-bold mb-0"><i class="fas fa-qrcode me-2 text-primary"></i> Status Fisik & QR Code Eksemplar</h5>
        <p class="text-muted small mb-0">Gunakan QR Code di bawah untuk keperluan identifikasi atau peminjaman eksemplar fisik.</p>
    </div>
    <div class="card-body px-4 pb-4">
        <div class="row">
            @foreach($book->copies as $index => $copy)
                <div class="col-md-3 mb-3">
                    <div class="border rounded-4 p-3 bg-light text-center position-relative">
                        <span class="badge bg-dark mb-2">Eksemplar #{{ $index + 1 }}</span>
                        
                        <div class="mb-2 bg-white d-inline-block p-2 rounded shadow-sm">
                            {!! SimpleSoftwareIO\QrCode\Facades\QrCode::size(110)->generate($copy->copy_code) !!}
                        </div>
                        <h6 class="fw-bold text-primary mb-2 font-monospace">{{ $copy->copy_code }}</h6>

                        @if($copy->status === 'available')
                            <span class="badge bg-success text-white px-3 py-1 small">Tersedia</span>
                        @else
                            <span class="badge bg-warning text-white px-3 py-1 small">Dipinjam Orang Lain</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection