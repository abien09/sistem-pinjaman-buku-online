@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h3 class="fw-bold mb-4"><i class="fas fa-users text-primary me-2"></i> Kelola Daftar Member</h3>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table align-middle table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>ID Member</th>
                            <th>Nama Lengkap</th>
                            <th>Email</th>
                            <th>No. Telp / WA</th>
                            <th>Total Peminjaman</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($members as $index => $member)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td><span class="badge bg-secondary font-monospace">{{ $member->member_code ?? '-' }}</span></td>
                            <td class="fw-bold text-dark">{{ $member->name }}</td>
                            <td>{{ $member->email }}</td>
                            <td>{{ $member->phone ?? '-' }}</td>
                            <td><span class="badge bg-primary bg-opacity-10 text-primary">{{ $member->transactions_count }} Buku</span></td>
                            <td class="text-center">
                                <a href="{{ route('admin.members.show', $member->id) }}" class="btn btn-sm btn-outline-primary fw-bold rounded-pill px-3">
                                    <i class="fas fa-eye me-1"></i> Detail
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">Belum ada member terdaftar.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection