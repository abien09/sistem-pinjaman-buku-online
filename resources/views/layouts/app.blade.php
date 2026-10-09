<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistem Perpustakaan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        #sidebar { min-width: 260px; max-width: 260px; transition: all 0.3s; }
        #sidebar.active { margin-left: -260px; }
        @media (max-width: 768px) {
            #sidebar { margin-left: -260px; position: fixed; height: 100vh; z-index: 1050; background: white; }
            #sidebar.active { margin-left: 0; }
        }
    </style>
</head>
<body>

<div class="d-flex" id="wrapper">
    @auth
    <nav id="sidebar" class="bg-white border-end min-vh-100 p-3 shadow-sm" style="position: sticky; top: 0; height: 100vh; overflow-y: auto;">
        <div class="sidebar-header pb-3 mb-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="fw-bold text-primary mb-0"><i class="fas fa-book-reader me-2"></i>PerpusDigital</h5>
            <button type="button" id="sidebarCloseBtn" class="btn-close d-md-none" aria-label="Close"></button>
        </div>

        <div class="list-group list-group-flush">
            <a href="{{ route('dashboard') }}" class="list-group-item list-group-item-action border-0 rounded mb-1 {{ request()->routeIs('dashboard') ? 'active bg-primary text-white' : '' }}">
                <i class="fas fa-home me-2"></i> Dashboard / Katalog
            </a>

            @if(auth()->user()->role === 'member')
                <a href="{{ route('member.history') }}" class="list-group-item list-group-item-action border-0 rounded mb-1 {{ request()->routeIs('member.history') ? 'active bg-primary text-white' : '' }}">
                    <i class="fas fa-history me-2"></i> Riwayat Peminjaman
                </a>
                <a href="{{ route('member.cart') }}" class="list-group-item list-group-item-action border-0 rounded mb-1 {{ request()->routeIs('member.cart') ? 'active bg-primary text-white' : '' }}">
                    <i class="fas fa-shopping-cart me-1"></i> Keranjang 
                    <span id="cart-badge" class="badge bg-danger rounded-pill float-end mt-1">
                        {{ \App\Models\Cart::where('user_id', auth()->id())->count() }}
                    </span>
                </a>
            @endif

            @if(auth()->user()->role === 'admin')
                <div class="text-uppercase text-muted small fw-bold px-3 mt-3 mb-2">Manajemen</div>
                <a href="{{ route('books.index') }}" class="list-group-item list-group-item-action border-0 rounded mb-1 {{ request()->routeIs('books.*') ? 'active bg-primary text-white' : '' }}">
                    <i class="fas fa-book me-2"></i> Kelola Buku
                </a>
                <a href="{{ route('admin.members.index') }}" class="list-group-item list-group-item-action border-0 rounded mb-1 {{ request()->routeIs('admin.members.*') ? 'active bg-primary text-white' : '' }}">
                    <i class="fas fa-users me-2"></i> Kelola Member
                </a>
                
                <div class="text-uppercase text-muted small fw-bold px-3 mt-3 mb-2">Sirkulasi</div>
                <a href="{{ route('transactions.create') }}" class="list-group-item list-group-item-action border-0 rounded mb-1 {{ request()->routeIs('transactions.create') ? 'active bg-primary text-white' : '' }}">
                    <i class="fas fa-camera me-2"></i> Scan Peminjaman
                </a>
                <a href="{{ route('transactions.return') }}" class="list-group-item list-group-item-action border-0 rounded mb-1 {{ request()->routeIs('transactions.return') ? 'active bg-primary text-white' : '' }}">
                    <i class="fas fa-undo me-2"></i> Scan Pengembalian
                </a>

                <div class="text-uppercase text-muted small fw-bold px-3 mt-3 mb-2">Analitik</div>
                <a href="{{ route('admin.reports') }}" class="list-group-item list-group-item-action border-0 rounded mb-1 {{ request()->routeIs('admin.reports') ? 'active bg-primary text-white' : '' }}">
                    <i class="fas fa-chart-bar me-2"></i> Laporan & Statistik
                </a>
            @endif

            <a href="{{ route('profile.edit') }}" class="list-group-item list-group-item-action border-0 rounded mb-1 mt-3 {{ request()->routeIs('profile.*') ? 'active bg-primary text-white' : '' }}">
                <i class="fas fa-user-cog me-2"></i> Pengaturan Profil
            </a>

            <div class="text-uppercase text-muted small fw-bold px-3 mt-3 mb-2">Akun</div>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="list-group-item list-group-item-action border-0 rounded text-danger bg-transparent">
                    <i class="fas fa-sign-out-alt me-2"></i> Keluar
                </button>
            </form>
        </div>
    </nav>
    @endauth

    <div id="content" class="w-100">
        <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom px-4 py-3 shadow-sm">
            <div class="container-fluid px-0">
                @auth
                <button type="button" id="sidebarCollapse" class="btn btn-outline-primary me-3">
                    <i class="fas fa-bars"></i>
                </button>
                @endauth
                <span class="navbar-brand mb-0 h6 text-secondary">Sistem Peminjaman Buku Berbasis QR Code</span>
                
                @auth
                <div class="ms-auto d-flex align-items-center">
                    <span class="fw-bold small me-2">{{ auth()->user()->name }}</span>
                    <span class="badge bg-secondary text-uppercase">{{ auth()->user()->role }}</span>
                </div>
                @endauth
            </div>
        </nav>

        <div class="container-fluid p-4">
            @yield('content')
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.all.min.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const sidebar = document.getElementById('sidebar');
        const sidebarCollapse = document.getElementById('sidebarCollapse');
        const sidebarCloseBtn = document.getElementById('sidebarCloseBtn');

        if (sidebarCollapse) {
            sidebarCollapse.addEventListener('click', function() {
                sidebar.classList.toggle('active');
            });
        }
        if (sidebarCloseBtn) {
            sidebarCloseBtn.addEventListener('click', function() {
                sidebar.classList.add('active');
            });
        }
    });

    // FUNGSI GLOBAL ADD TO CART DENGAN SWEETALERT & UPDATE BADGE
    function addToCart(bookId, btn) {
        let originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;
        
        fetch('{{ route("member.cart.add") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ book_id: bookId })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                // 1. Ganti Ikon Tombol Sesat
                btn.innerHTML = '<i class="fas fa-check"></i>';
                btn.classList.replace('btn-outline-primary', 'btn-success');
                btn.classList.replace('btn-primary', 'btn-success');

                // 2. Update Angka Merah di Sidebar Real-Time
                let badge = document.getElementById('cart-badge');
                if (badge) {
                    let currentCount = parseInt(badge.innerText) || 0;
                    badge.innerText = currentCount + 1;
                }

                // 3. Tampilkan Pop-up SweetAlert Cantik
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: 'Buku telah dimasukkan ke dalam keranjang.',
                    showConfirmButton: false,
                    timer: 1500,
                    backdrop: `rgba(0,0,0,0.4)`
                });

                // 4. Kembalikan tombol ke bentuk semula setelah 1.5 detik
                setTimeout(() => { 
                    btn.innerHTML = originalHtml; 
                    btn.classList.remove('btn-success');
                    if(btn.classList.contains('w-100')) {
                        btn.classList.add('btn-primary'); // Untuk tombol di halaman detail
                    } else {
                        btn.classList.add('btn-outline-primary'); // Untuk tombol di katalog
                    }
                    btn.disabled = false;
                }, 1500);
            } else {
                // Jika error (Email belum verified / Buku Rare / Penuh)
                Swal.fire({
                    icon: 'error',
                    title: 'Tidak Dapat Menambahkan',
                    text: data.error,
                    confirmButtonColor: '#0d6efd'
                });
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            }
        }).catch(err => {
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: 'Terjadi kesalahan sistem atau koneksi terputus.',
                confirmButtonColor: '#0d6efd'
            });
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        });
    }
</script>
@stack('scripts')
</body>
</html>