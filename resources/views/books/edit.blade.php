@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="mb-3">
            <a href="{{ route('books.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Kembali</a>
        </div>

        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-white py-3 border-0">
                <h4 class="fw-bold mb-0"><i class="fas fa-edit text-primary me-2"></i> Edit Data Buku</h4>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('books.update', $book->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="fw-bold form-label">Judul Buku</label>
                        <input type="text" name="title" class="form-control" value="{{ old('title', $book->title) }}" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold form-label">Penulis</label>
                            <input type="text" name="author" class="form-control" value="{{ old('author', $book->author) }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold form-label">Penerbit</label>
                            <input type="text" name="publisher" class="form-control" value="{{ old('publisher', $book->publisher) }}" placeholder="Nama penerbit...">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold form-label">Kategori</label>
                            <select name="category_id" class="form-select" required>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ $book->category_id == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Harga Buku (Rp)</label>
                            <input type="number" name="price" class="form-control" value="{{ $book->price }}" required>
                            <small class="text-muted">Tanpa titik (Contoh: 1500000)</small>
                        </div>
                    </div>

                    <!-- Input Checkbox Buku Langka -->
                    <div class="mb-4 form-check bg-light p-3 rounded">
                        <input
                            type="checkbox"
                            name="is_rare"
                            value="1"
                            class="form-check-input ms-1 me-2"
                            id="isRare"
                            {{ old('is_rare', $book->is_rare) ? 'checked' : '' }}
                        >

                        <label class="form-check-label text-danger fw-bold" for="isRare">
                            Tandai sebagai Buku Langka/Premium
                            (Hanya bisa dibaca di tempat)
                        </label>
                    </div>

                    <!-- Informasi Stok & Eksemplar Fisik Saat Ini -->
                    <div class="mb-4 border-top pt-3">
                        <label class="fw-bold mb-2">Daftar Eksemplar Fisik & QR Code (Eksisting)</label>
                        <ul class="list-group mb-3">
                            @forelse($book->copies as $copy)
                                <li class="list-group-item d-flex justify-content-between align-items-center py-1">
                                    <span class="font-monospace fw-bold text-primary">{{ $copy->copy_code }}</span>
                                    <span class="badge bg-{{ $copy->status == 'available' ? 'success' : 'secondary' }}">
                                        {{ ucfirst($copy->status) }}
                                    </span>
                                </li>
                            @empty
                                <li class="list-group-item text-muted">Belum ada eksemplar fisik terdaftar.</li>
                            @endforelse
                        </ul>

                        <!-- Input Tambah Stok Baru -->
                        <label class="fw-bold text-success">Tambah Stok Buku Fisik Baru (Opsional)</label>
                        <input type="number" name="additional_stock" class="form-control" placeholder="Masukkan jumlah buku fisik tambahan..." min="0" value="0">
                        <small class="text-muted">Sistem akan otomatis menghasilkan kode QR eksemplar baru.</small>
                    </div>

                    <div class="mb-3 border-top pt-3">
                        <label class="fw-bold form-label">Ganti Cover Buku (Opsional)</label>
                        @if($book->cover_image)
                            <div class="mb-2">
                                <img src="{{ asset('storage/' . $book->cover_image) }}" class="rounded shadow-sm" style="height: 100px; object-fit: cover;">
                            </div>
                        @endif
                        <input type="file" name="cover_image" class="form-control" accept="image/*">
                    </div>

                    <div class="mb-4">
                        <label class="fw-bold form-label">Sinopsis / Deskripsi</label>
                        <textarea name="description" class="form-control" rows="4">{{ old('description', $book->description) }}</textarea>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">
                            <i class="fas fa-save me-1"></i> Perbarui Buku
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection