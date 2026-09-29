@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold">Manajemen Buku</h3>
    <a href="{{ route('books.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Buku</a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <table class="table table-hover">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Cover</th>
                    <th>Judul Buku</th>
                    <th>Penulis</th>
                    <th>Kategori</th>
                    <th>Jumlah Eksemplar</th>
                    <th>Edit Buku</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($books as $book)
                <tr>
                        <td>{{ $book->id }}</td>
                        <!-- Tambahan untuk menampilkan gambar thumbnail -->
                <td>
                    @if($book->cover_image)
                        <img src="{{ asset('storage/' . $book->cover_image) }}" alt="Cover" class="img-thumbnail shadow-sm" style="width: 50px; height: 70px; object-fit: cover;">
                    @else
                        <div class="bg-secondary text-white text-center rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 70px; font-size: 10px;">
                            No Cover
                        </div>
                    @endif
                </td>
                    <td>{{ $book->title }}</td>
                    <td>{{ $book->author }}</td>
                    <td>{{ $book->category->name }}</td>
                    <td><span class="badge bg-info text-dark">{{ $book->copies_count }} Fisik</span></td>
                    <td>
                        <a href="{{ route('books.edit', $book->id) }}" class="btn btn-sm btn-warning text-white me-1" title="Edit Buku">
                            <i class="fas fa-edit"></i>
                        </a>
                    </td>
                    <td>
                        <a href="{{ route('books.show', $book->id) }}" class="btn btn-sm btn-success">Lihat QR</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted">Belum ada data buku.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection