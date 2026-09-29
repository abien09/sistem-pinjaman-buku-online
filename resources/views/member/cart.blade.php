@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h3 class="fw-bold mb-4"><i class="fas fa-shopping-cart text-primary me-2"></i> Keranjang Peminjaman</h3>

    @if(session('booking_code'))
        <div class="alert alert-success text-center shadow-sm rounded-4 mb-4 p-4 border-2 border-success">
            <h4 class="fw-bold text-success"><i class="fas fa-check-circle"></i> {{ session('booking_success') }}</h4>
            <div class="bg-white d-inline-block p-3 rounded border my-3">
                {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(200)->generate(session('booking_code')) !!}
            </div>
            <h3 class="fw-bold font-monospace">{{ session('booking_code') }}</h3>
            <p class="text-danger small mb-0">Batas Waktu Pengambilan ke Admin: 1x24 Jam</p>
        </div>
    @endif

    <div class="card shadow-sm rounded-4 border-0">
        <div class="card-body p-4">
            @if(isset($carts) && $carts->count() > 0)
                <div class="table-responsive mb-4">
                    <table class="table align-middle">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" colspan="2">Detail Buku</th>
                                <th scope="col">Penulis</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($carts as $cart)
                            <tr>
                                <td style="width: 80px;">
                                    @if($cart->book->cover_image)
                                        <img src="{{ asset('storage/' . $cart->book->cover_image) }}" class="rounded shadow-sm" style="width: 60px; height: 80px; object-fit: cover;">
                                    @else
                                        <div class="bg-secondary bg-opacity-10 rounded d-flex align-items-center justify-content-center" style="width: 60px; height: 80px;">
                                            <i class="fas fa-book text-muted"></i>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <h6 class="fw-bold mb-1">{{ $cart->book->title }}</h6>
                                    <span class="badge bg-primary bg-opacity-10 text-primary">{{ $cart->book->category->name ?? 'Umum' }}</span>
                                </td>
                                <td class="text-muted">{{ $cart->book->author }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <form action="{{ route('member.checkout') }}" method="POST" class="text-end">
                    @csrf
                    <p class="text-muted small mb-2">Total: <strong>{{ $carts->count() }} Buku</strong> (Maks. 5)</p>
                    <button type="submit" class="btn btn-primary btn-lg fw-bold px-5 rounded-pill shadow-sm">
                        <i class="fas fa-qrcode me-2"></i> Buat QR Booking Sekarang
                    </button>
                </form>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-box-open fa-4x text-muted mb-3 opacity-50"></i>
                    <h5 class="text-muted">Keranjang masih kosong</h5>
                    <p class="text-muted mb-4">Silakan cari buku di katalog terlebih dahulu.</p>
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-primary rounded-pill px-4">Cari Buku</a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection