@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12 mb-4">
        <h3 class="fw-bold"><i class="fas fa-tachometer-alt me-2 text-primary"></i> Dashboard Admin</h3>
        <p class="text-muted">Selamat datang kembali, {{ auth()->user()->name }}. Ini adalah ringkasan sistem hari ini.</p>
    </div>

    <!-- Card Status Toko -->
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm border-0 h-100 {{ $storeStatus === 'open' ? 'bg-success text-white' : 'bg-danger text-white' }}">
            <div class="card-body text-center d-flex flex-column justify-content-center">
                <h5 class="card-title mb-2">Status Operasional</h5>
                <h2 class="fw-bold mb-3">
                    @if($storeStatus === 'open')
                        <i class="fas fa-door-open me-2"></i> BUKA
                    @else
                        <i class="fas fa-door-closed me-2"></i> TUTUP
                    @endif
                </h2>
                
                <form action="{{ route('store.toggle') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn {{ $storeStatus === 'open' ? 'btn-outline-light' : 'btn-light text-danger fw-bold' }} btn-sm w-100">
                        Ubah menjadi {{ $storeStatus === 'open' ? 'TUTUP' : 'BUKA' }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Card Total Buku -->
    <div class="col-md-4 mb-4">
        <div class="card bg-info text-white shadow-sm border-0 h-100">
            <div class="card-body text-center d-flex flex-column justify-content-center">
                <h5 class="card-title mb-2">Total Judul Buku</h5>
                <h1 class="fw-bold display-4 mb-0">{{ $totalBooks }}</h1>
                <p class="mb-0 small opacity-75">Telah terdaftar di katalog</p>
            </div>
        </div>
    </div>

    <!-- Card Peminjaman Aktif -->
    <div class="col-md-4 mb-4">
        <div class="card bg-warning text-dark shadow-sm border-0 h-100">
            <div class="card-body text-center d-flex flex-column justify-content-center">
                <h5 class="card-title mb-2">Peminjaman Aktif</h5>
                <h1 class="fw-bold display-4 mb-0">{{ $activeBorrowings }}</h1>
                <p class="mb-0 small opacity-75">Buku fisik belum dikembalikan</p>
            </div>
        </div>
    </div>
</div>
@endsection