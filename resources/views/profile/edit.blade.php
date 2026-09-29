@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-9">
        <div class="mb-4">
            <h3 class="fw-bold"><i class="fas fa-user-circle text-primary me-2"></i> Pengaturan Profil & Verifikasi Identitas</h3>
            <p class="text-muted">Kelola informasi akun, verifikasi data diri, KTP, dan biometrik wajah Anda.</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm rounded-4 mb-4" role="alert">
                <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show shadow-sm rounded-4 mb-4" role="alert">
                <i class="fas fa-exclamation-triangle me-1"></i> Terjadi kesalahan validasi. Periksa kembali form di bawah.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PATCH')

            <!-- CARD 1: INFORMASI AKUN & IDENTITAS -->
            <div class="card shadow-sm border-0 rounded-4 mb-4">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-id-card text-primary me-2"></i> Informasi Akun & Identitas</h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="fw-bold form-label">Nama Lengkap</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="fw-bold form-label">Alamat Email</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    @if($user->role === 'member')
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold form-label">ID Member Unik</label>
                                <input type="text" class="form-control bg-light font-monospace" value="{{ $user->member_code }}" disabled>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold form-label">Nomor Induk Kependudukan (NIK)</label>
                                <input type="text" name="nik" class="form-control @error('nik') is-invalid @enderror" value="{{ old('nik', $user->nik) }}" maxlength="16" placeholder="Nomor NIK...">
                                @error('nik')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold form-label">Nomor Telepon / WhatsApp</label>
                                <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}" placeholder="08xxxxxxxxxx">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold form-label">Tanggal Lahir</label>
                                <input type="date" name="birth_date" class="form-control" value="{{ old('birth_date', $user->birth_date) }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="fw-bold form-label">Alamat Lengkap</label>
                            <textarea name="address" class="form-control" rows="2" placeholder="Alamat domisili...">{{ old('address', $user->address) }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="fw-bold form-label">Foto KTP</label>
                            @if($user->ktp_image_path)
                                <div class="mb-2">
                                    <a href="{{ asset('storage/' . $user->ktp_image_path) }}" target="_blank">
                                        <img src="{{ asset('storage/' . $user->ktp_image_path) }}" alt="KTP" class="img-thumbnail rounded-3" style="height: 120px; object-fit: cover;">
                                    </a>
                                    <span class="d-block small text-muted mt-1">KTP saat ini (klik untuk memperbesar)</span>
                                </div>
                            @endif
                            <input type="file" name="ktp_image_path" class="form-control @error('ktp_image_path') is-invalid @enderror" accept="image/*">
                            @error('ktp_image_path')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="fw-bold form-label">Hak Akses (Role)</label>
                        <input type="text" class="form-control bg-light text-uppercase fw-bold" value="{{ $user->role }}" disabled>
                    </div>
                </div>
            </div>

            <!-- CARD 2: FACE RECOGNITION (KHUSUS MEMBER) -->
            @if($user->role === 'member')
            <div class="card shadow-sm border-0 rounded-4 mb-4 align-items-center justify-content-center">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-camera-retro text-primary me-2"></i> Cuplikan Face Recognition (Biometrik Wajah)</h5>
                </div>
                    <div class="col-md-6 mb-3">
                        <div class="border rounded bg-light p-2 d-flex flex-column align-items-center justify-content-center" style="height: 240px;">
                            <span class="text-muted small mb-2">Cuplikan Wajah Tersimpan:</span>
                            <div id="results">
                                @if($user->face_image_path)
                                <img src="{{ asset('storage/' . $user->face_image_path) }}" class="rounded shadow-sm" style="width: 220px; height: 160px; object-fit: cover;">
                                @else
                                    <span class="text-secondary small fst-italic">Belum ada wajah terekam</span>
                                @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- CARD 3: UBAH PASSWORD -->
            <div class="card shadow-sm border-0 rounded-4 mb-4">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-lock text-primary me-2"></i> Ubah Kata Sandi (Opsional)</h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="fw-bold form-label">Password Saat Ini</label>
                        <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" placeholder="Masukkan password lama...">
                        @error('current_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold form-label">Password Baru</label>
                            <input type="password" name="new_password" class="form-control @error('new_password') is-invalid @enderror" placeholder="Minimal 6 karakter...">
                            @error('new_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold form-label">Konfirmasi Password Baru</label>
                            <input type="password" name="new_password_confirmation" class="form-control" placeholder="Ulangi password baru...">
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-end mb-5">
                <button type="submit" class="btn btn-primary px-5 py-2 fw-bold shadow-sm">
                    <i class="fas fa-save me-1"></i> Simpan Semua Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
