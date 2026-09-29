@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h3 class="fw-bold"><i class="fas fa-history text-primary me-2"></i> Riwayat Peminjaman Buku Saya</h3>
    <p class="text-muted">Daftar seluruh riwayat peminjaman dan status pengembalian buku fisik Anda.</p>
</div>

<div class="card shadow-sm border-0 rounded-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Judul Buku</th>
                        <th>Kode Eksemplar</th>
                        <th>Tanggal Pinjam</th>
                        <th>Batas Waktu</th>
                        <th>Status</th>
                        <th class="pe-4">Denda</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($histories as $history)
                        <tr>
                            <td class="ps-4 fw-bold text-dark">{{ $history->bookCopy->book->title }}</td>
                            <td><code>{{ $history->bookCopy->copy_code }}</code></td>
                            <td>{{ \Carbon\Carbon::parse($history->borrow_date)->format('d M Y') }}</td>
                            <td>{{ \Carbon\Carbon::parse($history->due_date)->format('d M Y') }}</td>
                            <td>
                                <button type="button" class="btn btn-outline-primary btn-sm px-3 show-return-qr" 
                                    data-title="{{ $history->bookCopy->book->title }}" 
                                    data-code="{{ $history->bookCopy->copy_code }}"
                                    data-svg="{{ base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::size(200)->generate($history->bookCopy->copy_code)) }}"
                                    title="Tampilkan QR Pengembalian">
                                    <i class="fas fa-qrcode"></i>
                                </button>
                                @if($history->status === 'borrowed')
                                    <span class="badge bg-warning text-dark px-2 py-1">Sedang Dipinjam</span>
                                @else
                                    <span class="badge bg-success px-2 py-1">Dikembalikan</span>
                                @endif
                            </td>
                            <td class="pe-4 text-danger fw-bold">
                                {{ $history->late_fee > 0 ? 'Rp ' . number_format($history->late_fee, 0, ',', '.') : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="fas fa-folder-open fa-3x mb-3 text-secondary opacity-50"></i>
                                <p class="mb-0">Kamu belum pernah melakukan transaksi peminjaman buku.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($histories->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $histories->links() }}
        </div>
    @endif

        {{-- Contoh snippet untuk menampilkan QR Booking aktif di halaman riwayat --}}
@foreach($activeBookings as $booking)
    <div class="card mb-3 p-3 shadow-sm">
        <h5>Kode Booking: {{ $booking->booking_code }}</h5>
        <div class="bg-white d-inline-block p-2">
            {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(150)->generate($booking->booking_code) !!}
        </div>
        <p class="text-muted small mt-2">Tunjukkan QR ini ke Admin perpustakaan.</p>
    </div>
@endforeach
</div>


<!-- Modal QR Pengembalian -->
<div class="modal fade" id="returnQrModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content text-center rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0 justify-content-end">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0 pb-4">
                <h5 class="fw-bold mb-1">QR Pengembalian</h5>
                <p class="text-muted small mb-3" id="modalBookTitle">Judul Buku</p>
                
                <div class="bg-light p-3 rounded-3 d-inline-block mb-3" id="qrContainer">
                    <!-- SVG QR Code akan disuntikkan oleh JS di sini -->
                </div>
                
                <h5 class="font-monospace fw-bold text-primary mb-1" id="modalBookCode">BK-XXX</h5>
                <p class="text-muted small mb-0">Tunjukkan QR ini ke Admin untuk mengembalikan buku.</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const qrButtons = document.querySelectorAll('.show-return-qr');
        const returnModal = new bootstrap.Modal(document.getElementById('returnQrModal'));
        
        qrButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                // Ambil data dari tombol yang diklik
                const title = this.getAttribute('data-title');
                const code = this.getAttribute('data-code');
                const svgBase64 = this.getAttribute('data-svg');
                
                // Decode base64 menjadi SVG
                const svgHtml = atob(svgBase64);
                
                // Masukkan ke dalam modal
                document.getElementById('modalBookTitle').textContent = title;
                document.getElementById('modalBookCode').textContent = code;
                document.getElementById('qrContainer').innerHTML = svgHtml;
                
                // Tampilkan modal
                returnModal.show();
            });
        });
    });
</script>
@endpush
@endsection