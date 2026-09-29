@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h3 class="fw-bold"><i class="fas fa-chart-line text-primary me-2"></i> Laporan & Statistik Perpustakaan</h3>
    <p class="text-muted">Analisis data peminjaman, buku terlaris, dan keaktifan member perpustakaan.</p>
</div>

<!-- STATISTIK KARTU ATAS (TOP 5 BUKU & MEMBER) -->
<div class="row mb-5">
    <!-- Buku Paling Laris -->
    <div class="col-md-6 mb-4 mb-md-0">
        <div class="card shadow-sm border-0 rounded-4 h-100">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-fire text-danger me-2"></i> Top 5 Buku Paling Laris</h5>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($topBooks as $index => $book)
                        <li class="list-group-item d-flex justify-content-between align-items-center px-4 py-3">
                            <div class="d-flex align-items-center">
                                <span class="badge bg-dark rounded-circle p-2 me-3 {{ $index == 0 ? 'bg-warning text-dark' : '' }}">{{ $index + 1 }}</span>
                                <div>
                                    <h6 class="fw-bold mb-0">{{ $book->title }}</h6>
                                    <small class="text-muted">{{ $book->author }} • <span class="text-primary">{{ $book->category->name }}</span></small>
                                </div>
                            </div>
                            <span class="badge bg-success bg-opacity-15 text-success text-white px-3 py-2 fw-bold">{{ $book->total_borrowed }}x Dipinjam</span>
                        </li>
                    @empty
                        <li class="list-group-item text-center text-muted py-4">Belum ada data peminjaman buku.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <!-- Member Paling Sering Meminjam -->
    <div class="col-md-6">
        <div class="card shadow-sm border-0 rounded-4 h-100">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-trophy text-warning me-2"></i> Top 5 Member Paling Aktif</h5>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($topMembers as $index => $member)
                        <li class="list-group-item d-flex justify-content-between align-items-center px-4 py-3">
                            <div class="d-flex align-items-center">
                                <span class="badge bg-dark rounded-circle p-2 me-3 {{ $index == 0 ? 'bg-warning text-dark' : '' }}">{{ $index + 1 }}</span>
                                <div>
                                    <h6 class="fw-bold mb-0">{{ $member->name }}</h6>
                                    <small class="text-muted font-monospace">{{ $member->member_code }} • {{ $member->email }}</small>
                                </div>
                            </div>
                            <span class="badge bg-primary bg-opacity-15 text-primary text-white px-3 py-2 fw-bold">{{ $member->transactions_count }} Transaksi</span>
                        </li>
                    @empty
                        <li class="list-group-item text-center text-muted py-4">Belum ada data aktivitas member.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- SELURUH RIWAYAT PEMINJAMAN (SIAPA MEMINJAM APA) -->
<div class="card shadow-sm border-0 rounded-4">
    <div class="card-header bg-white py-3 border-0">
        <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-history text-primary me-2"></i> Seluruh Riwayat Sirkulasi Transaksi</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Nama Peminjam</th>
                        <th>Judul Buku</th>
                        <th>Kode Eksemplar</th>
                        <th>Tanggal Pinjam</th>
                        <th>Batas Waktu</th>
                        <th>Status</th>
                        <th class="pe-4">Denda</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $trx)
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark">{{ $trx->user->name }}</div>
                                <small class="text-muted font-monospace">{{ $trx->user->member_code }}</small>
                            </td>
                            <td>
                                <div class="fw-bold">{{ $trx->bookCopy->book->title }}</div>
                            </td>
                            <td><code>{{ $trx->bookCopy->copy_code }}</code></td>
                            <td>{{ \Carbon\Carbon::parse($trx->borrow_date)->format('d M Y') }}</td>
                            <td>{{ \Carbon\Carbon::parse($trx->due_date)->format('d M Y') }}</td>
                            <td>
                                @if($trx->status === 'borrowed')
                                    <span class="badge bg-warning text-dark">Sedang Dipinjam</span>
                                @else
                                    <span class="badge bg-success">Dikembalikan</span>
                                @endif
                            </td>
                            <td class="pe-4 text-danger fw-bold">
                                {{ $trx->late_fee > 0 ? 'Rp ' . number_format($trx->late_fee, 0, ',', '.') : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Belum ada riwayat transaksi tercatat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white border-0 py-3">
        {{ $transactions->links() }}
    </div>
</div>
@endsection