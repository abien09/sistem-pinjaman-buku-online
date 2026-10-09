<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\BookCopy;
use App\Models\BorrowTransaction;
use App\Models\BorrowTransactionDetail;
use App\Models\Transaction; // <-- Pastikan Model Transaction di-import
use App\Models\Book; 
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    // Menampilkan halaman keranjang
    public function index()
    {
        $carts = Cart::with('book')->where('user_id', Auth::id())->get();
        return view('member.cart', compact('carts'));
    }

    // Menambah buku ke keranjang dengan batasan sisa kuota (Maksimal total 7 buku)
    public function store(Request $request)
    {
        // 0. CEK ATURAN BISNIS: HARUS VERIFIKASI EMAIL
        if (!Auth::user()->email_verified_at) {
            $msg = 'Akun Anda belum diverifikasi! Silakan verifikasi email Anda terlebih dahulu untuk meminjam buku.';
            if ($request->ajax()) return response()->json(['error' => $msg], 403);
            return back()->with('error', $msg);
        }

        $request->validate(['book_id' => 'required|exists:books,id']);
        
        $userId = Auth::id();

        // =========================================================================
        // CEK KONDISI & SISA KUOTA PEMINJAMAN (Maksimal Total 7 Buku)
        // =========================================================================
        // 1. Hitung buku yang sedang aktif dipinjam oleh user
        $activeBorrowedCount = Transaction::where('user_id', $userId)
            ->where('status', 'borrowed')
            ->count();

        // 2. Hitung buku yang sudah ada di keranjang saat ini
        $currentCartCount = Cart::where('user_id', $userId)->count();

        // Total keseluruhan buku yang sedang dipegang + yang mau dimasukkan ke keranjang
        $totalActiveBooks = $activeBorrowedCount + $currentCartCount;

        if ($totalActiveBooks >= 7) {
            $msg = 'User ini sudah mencapai batasan peminjaman';
            if ($request->ajax()) return response()->json(['error' => $msg], 400);
            return back()->with('error', $msg);
        }
        // =========================================================================

        // 1. Ambil data buku yang ingin dipinjam
        $book = Book::findOrFail($request->book_id);

        // 2. CEK ATURAN BISNIS: Buku langka / Harga di atas 1 Juta
        if ($book->is_rare || $book->price > 1000000) {
            $msg = 'Buku ini berstatus Langka/Premium dan hanya boleh dibaca di tempat.';
            if ($request->ajax()) return response()->json(['error' => $msg], 403);
            return back()->with('error', $msg);
        }

        // 3. Masukkan ke keranjang (firstOrCreate mencegah buku kembar di keranjang)
        Cart::firstOrCreate([
            'user_id' => $userId,
            'book_id' => $request->book_id
        ]);

        if ($request->ajax()) return response()->json(['success' => true]);
        return back()->with('success', 'Buku masuk ke keranjang!');
    }

    // Checkout Keranjang
    public function checkout()
    {
        // 0. CEK VERIFIKASI EMAIL UNTUK KEAMANAN GANDA
        if (!Auth::user()->email_verified_at) {
            return back()->with('error', 'Akun Anda belum diverifikasi! Silakan verifikasi email Anda terlebih dahulu.');
        }

        $userId = Auth::id();
        $cartItems = Cart::with('book')->where('user_id', $userId)->get();

        if ($cartItems->isEmpty()) {
            return back()->with('error', 'Keranjang Anda kosong.');
        }

        // Generate Kode Booking
        $bookingCode = 'BOOK-' . strtoupper(Str::random(6));
        
        // Buat Header Transaksi
        $transaction = BorrowTransaction::create([
            'booking_code' => $bookingCode,
            'user_id' => $userId,
            'status' => 'booked',
            'booking_expires_at' => now()->addHours(24),
        ]);

        foreach ($cartItems as $item) {
            // Kunci 1 Eksemplar Fisik
            $copy = BookCopy::where('book_id', $item->book_id)->where('status', 'available')->first();
            
            if ($copy) {
                $copy->update(['status' => 'booked']);

                BorrowTransactionDetail::create([
                    'borrow_transaction_id' => $transaction->id,
                    'book_copy_id' => $copy->id,
                    'status' => 'booked',
                ]);
            }
        }

        // Kosongkan keranjang
        Cart::where('user_id', $userId)->delete();

        // Redirect dengan membawa Session Kode Booking
        return redirect()->route('member.cart')->with([
            'booking_success' => 'Berhasil! Tunjukkan QR Code di bawah ini kepada Admin.',
            'booking_code' => $bookingCode
        ]);
    }   
}