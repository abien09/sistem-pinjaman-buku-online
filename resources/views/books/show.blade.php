@extends('layouts.app')

@section('content')
<div class="mb-3">
    <a href="{{ route('books.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <h5 class="fw-bold">{{ $book->title }}</h5>
                <p class="text-muted mb-1"><i class="fas fa-pen-nib"></i> {{ $book->author }}</p>
                <p class="text-muted"><i class="fas fa-tags"></i> {{ $book->category->name }}</p>
                <hr>
                <p class="mb-0"><strong>Harga Dasar:</strong> Rp {{ number_format($book->price, 0, ',', '.') }}</p>
                <!-- Tampilkan Gambar Jika Ada -->
                @if($book->cover_image)
                    <div class="text-center mt-3">
                        <img src="{{ asset('storage/' . $book->cover_image) }}" alt="Cover Buku" class="img-fluid rounded shadow-sm" style="max-height: 250px;">
                    </div>
                @else
                    <div class="alert alert-secondary text-center mt-3 small">Tidak ada gambar sampul</div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white fw-bold">
                Daftar QR Code Eksemplar Fisik
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach($book->copies as $index => $copy)
                        <div class="col-md-4 text-center mb-4">
                            <div class="border rounded p-3 bg-light">
                                <span class="badge bg-dark mb-2">Buku ke-{{ $index + 1 }}</span>
                                <div class="mb-2 bg-white d-inline-block p-2 shadow-sm">
                                    {!! SimpleSoftwareIO\QrCode\Facades\QrCode::size(120)->generate($copy->copy_code) !!}
                                </div>
                                <h6 class="fw-bold text-primary mb-1">{{ $copy->copy_code }}</h6>
                                
                                @if($copy->status === 'available')
                                    <span class="badge bg-success">Tersedia</span>
                                @elseif($copy->status === 'borrowed')
                                    <span class="badge bg-warning text-dark">Sedang Dipinjam</span>
                                @else
                                    <span class="badge bg-danger">{{ ucfirst($copy->status) }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection