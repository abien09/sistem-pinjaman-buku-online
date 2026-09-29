@extends('layouts.app')

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header bg-white">
        <h5 class="mb-0 fw-bold">Tambah Buku Baru</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('books.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label>Judul Buku</label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label>Penulis</label>
                    <input type="text" name="author" class="form-control" required>
                </div>
                <!-- TAMBAHKAN INPUT PENERBIT DI SINI -->
                <div class="mb-3">
                    <label class="fw-bold form-label">Penerbit</label>
                    <input type="text" name="publisher" class="form-control" value="{{ old('publisher') }}" placeholder="Nama penerbit buku...">
                </div>
                <div class="col-md-6 mb-3">
                    <label>Kategori</label>
                    <select name="category_id" class="form-control" required>
                        <option value="">-- Pilih Kategori --</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label>Harga Asli Buku (Rupiah) - <small class="text-danger">Disembunyikan dari Member</small></label>
                    <input type="number" name="price" class="form-control" placeholder="Contoh: 85000" required>
                </div>
                <div class="col-md-12 mb-3">
                    <label>Jumlah Fisik Buku (Eksemplar)</label>
                    <input type="number" name="number_of_copies" class="form-control" placeholder="Berapa banyak buku fisik yang ada?" min="1" required>
                </div>
                <div class="col-md-12 mb-3">
                    <label>Deskripsi (Opsional)</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>
                <div class="col-md-12 mb-3">
                    <label>Cover Buku (Opsional)</label>
                    <input type="file" name="cover_image" class="form-control" accept="image/*">
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100">Simpan dan Generate QR Eksemplar</button>
        </form>
    </div>
</div>
@endsection