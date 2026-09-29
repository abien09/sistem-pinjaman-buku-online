<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MemberBookController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\MemberHistoryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CartController;
use App\Models\Book;
use App\Models\Transaction;
use App\Models\Setting;

// Redirect awal ke halaman login
Route::get('/', function () {
    return redirect()->route('login');
});

// ==========================================
// 1. RUTE TAMU (Belum Login / Guest)
// ==========================================
Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'processRegister'])->name('register.process');
    
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'processLogin'])->name('login.process');
});

// ==========================================
// 2. RUTE UTAMA TERAUTENTIKASI (Sudah Login)
// ==========================================
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard Pintar (Membedakan tampilan Admin & Member secara otomatis)
    Route::get('/dashboard', function (Request $request) {
        if (auth()->user()->role === 'admin') {
            $totalBooks = Book::count();
            $activeBorrowings = Transaction::where('status', 'borrowed')->count();
            
            $setting = Setting::where('key', 'store_status')->first();
            $storeStatus = $setting ? $setting->value : 'open';
            
            return view('dashboard', compact('totalBooks', 'activeBorrowings', 'storeStatus')); 
        }
        
        return app(MemberController::class)->index($request);
    })->name('dashboard');

    // Profil & Face Recognition (Umum untuk Member & Admin)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/face', [ProfileController::class, 'saveFace'])->name('profile.save_face');


    // ==========================================
    // 3. RUTE KHUSUS MEMBER
    // ==========================================
    Route::middleware(['auth'])->group(function () {
        // Riwayat Peminjaman & Booking Aktif (Menampilkan QR Code)
        Route::get('/member/history', [MemberHistoryController::class, 'index'])->name('member.history');
        
        // Detail Buku Member
        Route::get('/member/books/{id}', [MemberBookController::class, 'show'])->name('member.books.show');
        
        // Keranjang & Checkout Booking (QR Code Gabungan)
        Route::get('/member/cart', [CartController::class, 'index'])->name('member.cart');
        Route::post('/member/cart/add', [CartController::class, 'store'])->name('member.cart.add');
        Route::post('/member/checkout', [CartController::class, 'checkout'])->name('member.checkout');
    });


    // ==========================================
    // 4. RUTE KHUSUS ADMIN (Manajemen & Transaksi)
    // ==========================================
    // Manajemen Buku
    Route::resource('books', BookController::class);

    // Laporan & Status Toko
    Route::get('/admin/reports', [ReportController::class, 'index'])->name('admin.reports');
    Route::post('/store/toggle', function () {
        if (auth()->user()->role !== 'admin') abort(403);
        
        $setting = Setting::firstOrCreate(['key' => 'store_status']);
        $setting->value = $setting->value === 'open' ? 'closed' : 'open';
        $setting->save();
        
        return back()->with('success', 'Status operasional perpustakaan berhasil diubah!');
    })->name('store.toggle');

    // Transaksi Peminjaman (Support Single Scan & QR Gabungan Keranjang)
    Route::get('/transactions/borrow', [TransactionController::class, 'create'])->name('transactions.create');
    Route::post('/transactions/borrow', [TransactionController::class, 'store'])->name('transactions.store');
    Route::post('/transactions/verify-borrow', [TransactionController::class, 'verifyBorrowScan'])->name('transactions.verify_borrow');
    Route::post('/transactions/process-borrow', [TransactionController::class, 'processBorrow'])->name('transactions.process_borrow');

    // Transaksi Pengembalian (Return)
    Route::get('/transactions/return', [TransactionController::class, 'returnForm'])->name('transactions.return');
    Route::post('/transactions/return/verify', [TransactionController::class, 'verifyReturn'])->name('transactions.verify_return');
    Route::post('/transactions/return/process', [TransactionController::class, 'processReturn'])->name('transactions.process_return');

    // --- RUTE KELOLA MEMBER OLEH ADMIN ---
    Route::middleware(['auth'])->group(function () {
    Route::get('/admin/members', [App\Http\Controllers\MemberController::class, 'adminIndex'])->name('admin.members.index');
    Route::get('/admin/members/{id}', [App\Http\Controllers\MemberController::class, 'adminShow'])->name('admin.members.show');
});
});