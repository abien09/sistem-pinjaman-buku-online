@extends('layouts.app')

@section('content')

@php
    $isMondayNow = (date('N') == 1);
    $globalSetting = \App\Models\Setting::where('key', 'store_status')->first();
    $currentStatus = $globalSetting ? $globalSetting->value : 'open';
    $isSystemClosed = $isMondayNow || ($currentStatus === 'closed');
@endphp

@if($isSystemClosed)
    <div class="alert alert-danger shadow-sm rounded-4 p-3 mb-4 d-flex align-items-center">
        <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
        <div>
            <strong>Perpustakaan Sedang Tutup!</strong> 
            {{ $isMondayNow ? 'Hari ini hari Senin (Libur operasional mingguan).' : 'Admin sedang menutup perpustakaan secara manual.' }} Layanan peminjaman fisik tidak tersedia.
        </div>
    </div>
@endif

<div class="p-5 mb-4 rounded-4 shadow-sm text-white position-relative overflow-hidden" style="background: linear-gradient(135deg, #0d6efd 0%, #0099ff 100%);">
    <div class="row align-items-start position-relative z-1">
        <div class="col-lg-7 mb-4 mb-lg-0">
            <h1 class="display-5 fw-bold mb-3">Jelajahi Dunia Pengetahuan 🚀</h1>
            <p class="fs-6 mb-0 opacity-90" style="line-height: 1.6;">Selamat datang, {{ auth()->user()->name }}. Temukan buku favoritmu, pinjam dengan mudah menggunakan verifikasi QR Code, dan perluas wawasanmu hari ini.</p>
        </div>
        <div class="col-lg-5 text-center text-lg-end">
            <div class="bg-white text-dark p-3 rounded-4 shadow-sm d-inline-block text-center">
                <div class="bg-light p-2 rounded-3 mb-2 d-inline-block">
                    {!! SimpleSoftwareIO\QrCode\Facades\QrCode::size(200)->generate(auth()->user()->member_code) !!}
                </div>
                <div>
                    <span class="d-block text-muted small fw-semibold">ID Member Anda</span>
                    <strong class="text-primary fs-5 font-monospace">{{ auth()->user()->member_code }}</strong>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="mb-4">
    <h5 class="fw-bold mb-3"><i class="fas fa-tags me-2 text-primary"></i> Kategori Pilihan</h5>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('dashboard') }}" class="btn {{ !request('category') ? 'btn-dark' : 'btn-outline-dark' }} rounded-pill px-4">Semua Buku</a>
        @foreach($categories as $cat)
            <a href="{{ route('dashboard', ['category' => $cat->id]) }}" class="btn {{ request('category') == $cat->id ? 'btn-dark' : 'btn-outline-dark' }} rounded-pill px-4">
                {{ $cat->name }}
            </a>
        @endforeach
    </div>
</div>

<div class="mb-5">
    <h5 class="fw-bold mb-3"><i class="fas fa-book-open me-2 text-primary"></i> Daftar Katalog Buku</h5>
    
    <div class="row">
        @forelse($books as $book)
            <div class="col-md-3 mb-4">
                <div class="card h-100 shadow-sm border-0 rounded-4 overflow-hidden position-relative">
                    
                    {{-- Label Badge Khusus Buku Langka/Premium di atas Cover --}}
                    @if($book->is_rare || $book->price > 1000000)
                        <div class="position-absolute top-0 start-0 z-2 m-2">
                            <span class="badge bg-warning text-dark fw-bold px-2 py-1 shadow-sm" style="font-size: 9px;">
                                <i class="fas fa-star me-1"></i> BUKU LANGKA / PREMIUM
                            </span>
                        </div>
                    @endif

                    <div class="bg-light text-center position-relative" style="height: 220px; overflow: hidden;">
                        @if($book->cover_image)
                            <img src="{{ asset('storage/' . $book->cover_image) }}" alt="{{ $book->title }}" class="w-100 h-100 object-fit-cover">
                        @else
                            <div class="d-flex align-items-center justify-content-center h-100 text-muted bg-secondary bg-opacity-10">
                                <i class="fas fa-book fa-3x"></i>
                            </div>
                        @endif
                        
                        <span class="position-absolute bottom-0 start-0 m-2 badge bg-dark bg-opacity-75 backdrop-blur">
                            {{ $book->category->name }}
                        </span>
                    </div>

                    <div class="card-body d-flex flex-column">
                        <h6 class="fw-bold mb-1 text-truncate">
                            <a href="{{ route('member.books.show', $book->id) }}" class="text-dark text-decoration-none stretched-link">
                                {{ $book->title }}
                            </a>
                        </h6>
                        <p class="text-muted small mb-2"><i class="fas fa-pen-nib me-1"></i> {{ $book->author }}</p>
                        
                        <div class="mt-auto">
                            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                                
                                {{-- KONDISIONAL TAMPILAN JIKA BUKU READ-ONLY / LANGKA --}}
                                @if($book->isReadOnly())
                                    <span class="badge bg-warning text-dark fw-bold px-2 py-1 shadow-sm" style="font-size: 11px;">
                                        HANYA BACA DI TEMPAT
                                    </span>

                                    <button type="button" class="btn btn-secondary btn-sm px-3 position-relative" style="z-index: 2;" disabled title="Buku Hanya Bisa Dibaca di Tempat">
                                        <i class="fas fa-book-reader"></i>
                                    </button>
                                @else
                                    <span class="small text-muted">Stok Fisik:</span>
                                    @if($book->available_copies_count > 0)
                                        <span id="stock-{{ $book->id }}"
                                              class="badge {{ $book->available_copies_count > 0 ? 'bg-success' : 'bg-danger' }}">
                                            {{ $book->available_copies_count > 0
                                                ? $book->available_copies_count . ' Tersedia'
                                                : 'Stok Habis' }}
                                        </span>
                                    @else
                                    <span
                                        id="stock-{{ $book->id }}"
                                        class="badge bg-danger bg-opacity-10 text-danger fw-bold px-2 py-1"
                                    >
                                        Habis
                                    </span>
                                    @endif

                                    <button type="button" class="btn btn-outline-primary btn-sm px-3 position-relative" style="z-index: 2;" onclick="addToCart({{ $book->id }}, this)" title="Tambah ke Keranjang">
                                        <i class="fas fa-cart-plus"></i>
                                    </button>
                                @endif

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-warning text-center p-4 rounded-4">
                    Belum ada buku yang tersedia untuk kategori ini.
                </div>
            </div>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script>
    function addToCart(bookId, btn) {
        const originalHtml = btn.innerHTML;
        const originalClasses = btn.className;

        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;

        fetch('{{ route("member.cart.add") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                book_id: bookId
            })
        })
        .then(async response => {
            const data = await response.json();

            if (!response.ok) {
                throw new Error(
                    data.error || data.message || 'Gagal menambahkan buku.'
                );
            }

            return data;
        })
        .then(data => {
            if (data.success) {
                // UPDATE STOK BUKU LANGSUNG BERKURANG TANPA REFRESH
                const stockElement = document.getElementById('stock-' + bookId);

                if (stockElement && data.new_stock !== undefined) {
                    const newStock = parseInt(data.new_stock);

                    stockElement.textContent = newStock > 0 
                        ? newStock + ' Tersedia' 
                        : 'Stok Habis';

                    stockElement.className = newStock > 0 
                        ? 'badge bg-success' 
                        : 'badge bg-danger';
                }

                // Tampilkan ikon centang sementara
                btn.innerHTML = '<i class="fas fa-check"></i>';
                btn.classList.remove('btn-outline-primary', 'btn-primary');
                btn.classList.add('btn-success');

                // Update badge jumlah keranjang di sidebar
                const badge = document.getElementById('cart-badge');
                if (badge) {
                    const currentCount = parseInt(badge.innerText) || 0;
                    badge.innerText = currentCount + 1;
                }

                // Notifikasi berhasil
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: data.message || 'Buku berhasil dimasukkan ke keranjang.',
                    showConfirmButton: false,
                    timer: 1500,
                    backdrop: 'rgba(0,0,0,0.4)'
                });

                // Kembalikan tombol seperti semula setelah 1.5 detik
                setTimeout(() => {
                    btn.innerHTML = originalHtml;
                    btn.className = originalClasses;
                    
                    // Jika stok habis setelah ditambahkan, nonaktifkan tombol
                    if (data.new_stock !== undefined && parseInt(data.new_stock) <= 0) {
                        btn.disabled = true;
                    } else {
                        btn.disabled = false;
                    }
                }, 1500);
            } else {
                throw new Error(data.error || 'Buku gagal ditambahkan.');
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: error.message || 'Terjadi kesalahan sistem.',
                confirmButtonColor: '#0d6efd'
            });

            // Pulihkan tombol jika terjadi kesalahan
            btn.innerHTML = originalHtml;
            btn.className = originalClasses;
            btn.disabled = false;
            
            console.error('Kesalahan addToCart:', error);
        });
    }
</script>
@endpush