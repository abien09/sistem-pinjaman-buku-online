@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold m-0"><i class="fas fa-user-circle text-primary me-2"></i> Detail Informasi Member</h3>
        <a href="{{ route('admin.members.index') }}" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="fas fa-arrow-left me-1"></i> Kembali
        </a>
    </div>

    <div class="row">
        <!-- Kolom Kiri: Biodata Lengkap -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0 rounded-4 h-100">
                <div class="card-body text-center p-4">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="fas fa-user fa-2x"></i>
                    </div>
                    <h4 class="fw-bold mb-1">{{ $member->name }}</h4>
                    <span class="badge bg-secondary font-monospace mb-3">{{ $member->member_code ?? 'Tanpa ID' }}</span>
                    
                    <hr class="my-3">

                    <div class="text-start small">
                        <p class="mb-2"><strong><i class="fas fa-envelope text-muted me-2"></i> Email:</strong> {{ $member->email }}</p>
                        <p class="mb-2"><strong><i class="fas fa-id-card text-muted me-2"></i> NIK:</strong> {{ $member->nik ?? '-' }}</p>
                        <p class="mb-2"><strong><i class="fas fa-phone text-muted me-2"></i> No. Telp / WA:</strong> {{ $member->phone ?? '-' }}</p>
                        <p class="mb-2"><strong><i class="fas fa-calendar-alt text-muted me-2"></i> Tanggal Lahir:</strong> {{ $member->birth_date ? \Carbon\Carbon::parse($member->birth_date)->format('d M Y') : '-' }}</p>
                        <p class="mb-2"><strong><i class="fas fa-birthday-cake text-muted me-2"></i> Umur:</strong> {{ $age ? $age . ' Tahun' : '-' }}</p>
                        <p class="mb-0"><strong><i class="fas fa-map-marker-alt text-muted me-2"></i> Alamat Lengkap:</strong> <br><span class="text-muted">{{ $member->address ?? '-' }}</span></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Riwayat Buku yang Dipinjam -->
        <div class="col-md-8 mb-4">
            <div class="card shadow-sm border-0 rounded-4 h-100">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3"><i class="fas fa-history text-primary me-2"></i> Riwayat Peminjaman Buku</h5>
                    
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Judul Buku</th>
                                    <th>Kode Fisik</th>
                                    <th>Tanggal Pinjam</th>
                                    <th>Batas Tempo</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($member->transactions as $trx)
                                <tr>
                                    <td>
                                        <span class="fw-bold d-block">{{ $trx->bookCopy->book->title ?? 'Buku Dihapus' }}</span>
                                        <small class="text-muted">Penulis: {{ $trx->bookCopy->book->author ?? '-' }}</small>
                                    </td>
                                    <td><span class="font-monospace">{{ $trx->bookCopy->copy_code ?? '-' }}</span></td>
                                    <td>{{ \Carbon\Carbon::parse($trx->borrow_date)->format('d/m/Y') }}</td>
                                    <td>{{ $trx->due_date ? \Carbon\Carbon::parse($trx->due_date)->format('d/m/Y') : '-' }}</td>
                                    <td>
                                        @if($trx->status == 'borrowed')
                                            <span class="badge bg-warning text-dark">Dipinjam</span>
                                        @elseif($trx->status == 'returned')
                                            <span class="badge bg-success">Dikembalikan</span>
                                        @else
                                            <span class="badge bg-secondary">{{ ucfirst($trx->status) }}</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="fas fa-book-open fa-2x mb-2 opacity-50"></i>
                                        <p class="mb-0">Member ini belum memiliki riwayat peminjaman buku.</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection