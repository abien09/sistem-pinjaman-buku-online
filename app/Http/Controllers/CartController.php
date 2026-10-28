<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\BookCopy;
use App\Models\BorrowTransaction;
use App\Models\BorrowTransactionDetail;
use App\Models\Transaction; 
use App\Models\Book; 
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    // Menampilkan halaman keranjang
    public function index()
    {
        $carts = Cart::with('book')->where('user_id', Auth::id())->get();
        return view('member.cart', compact('carts'));
    }

    // Menambah buku ke keranjang (AJAX / Normal)
    public function store(Request $request)
    {
        if (!Auth::user()->email_verified_at) {
            $msg = 'Akun Anda belum diverifikasi! Silakan verifikasi email Anda terlebih dahulu.';

            if ($request->ajax()) {
                return response()->json(['error' => $msg], 403);
            }

            return back()->with('error', $msg);
        }

        $request->validate([
            'book_id' => 'required|exists:books,id'
        ]);

        $userId = Auth::id();

        $activeBorrowedCount = Transaction::where('user_id', $userId)
            ->where('status', 'borrowed')
            ->count();

        $currentCartCount = Cart::where('user_id', $userId)->count();

        if (($activeBorrowedCount + $currentCartCount) >= 7) {
            $msg = 'User ini sudah mencapai batasan peminjaman.';

            if ($request->ajax()) {
                return response()->json(['error' => $msg], 400);
            }

            return back()->with('error', $msg);
        }

        $book = Book::with('copies')->findOrFail($request->book_id);

        if ($book->isReadOnly()) {
            $msg = 'Buku ini hanya boleh dibaca di tempat.';

            if ($request->ajax()) {
                return response()->json(['error' => $msg], 403);
            }

            return back()->with('error', $msg);
        }

        $exists = Cart::where('user_id', $userId)
            ->where('book_id', $request->book_id)
            ->exists();

        if ($exists) {
            $msg = 'Buku ini sudah ada di dalam keranjang Anda.';

            if ($request->ajax()) {
                return response()->json(['error' => $msg], 400);
            }

            return back()->with('error', $msg);
        }

        try {
            $remainingStock = DB::transaction(function () use ($request, $userId) {
                $copy = BookCopy::where('book_id', $request->book_id)
                    ->where('status', 'available')
                    ->lockForUpdate()
                    ->first();

                if (!$copy) {
                    throw new \RuntimeException('Stok buku sudah habis.');
                }

                Cart::create([
                    'user_id' => $userId,
                    'book_id' => $request->book_id
                ]);

                $copy->update([
                    'status' => 'booked'
                ]);

                return BookCopy::where('book_id', $request->book_id)
                    ->where('status', 'available')
                    ->count();
            });
        } catch (\RuntimeException $e) {
            $msg = $e->getMessage();

            if ($request->ajax()) {
                return response()->json(['error' => $msg], 400);
            }

            return back()->with('error', $msg);
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Buku berhasil dimasukkan ke keranjang!',
                'new_stock' => $remainingStock
            ]);
        }

        return back()->with('success', 'Buku masuk ke keranjang!');
    }

   // Membatalkan / Menghapus Buku dari Keranjang
    public function destroy($id)
    {
        // 1. Cari data keranjang berdasarkan ID dan User yang sedang login
        $cart = Cart::where('user_id', Auth::id())->findOrFail($id);
        
        // 2. Cari eksemplar fisik buku yang sebelumnya di-booking (dikunci)
        $bookCopy = BookCopy::where('book_id', $cart->book_id)
                            ->where('status', 'booked')
                            ->first();
        
        // 3. Jika eksemplar fisik ditemukan, kembalikan statusnya menjadi available (stok bertambah)
        if ($bookCopy) {
            $bookCopy->update(['status' => 'available']);
        }

        // 4. Hapus data dari database keranjang
        $cart->delete();

        return redirect()->route('member.cart')->with('success', 'Buku berhasil dibatalkan dari keranjang.');
    }

    // Checkout Keranjang
    public function checkout()
    {
        if (!Auth::user()->email_verified_at) {
            return back()->with('error', 'Akun Anda belum diverifikasi!');
        }

        $userId = Auth::id();
        $cartItems = Cart::with('book')->where('user_id', $userId)->get();

        if ($cartItems->isEmpty()) {
            return back()->with('error', 'Keranjang Anda kosong.');
        }

        $bookingCode = 'BOOK-' . strtoupper(Str::random(6));
        
        $transaction = BorrowTransaction::create([
            'booking_code' => $bookingCode,
            'user_id' => $userId,
            'status' => 'booked',
            'booking_expires_at' => now()->addHours(24),
        ]);

        foreach ($cartItems as $item) {
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

        Cart::where('user_id', $userId)->delete();

        return redirect()->route('member.cart')->with([
            'booking_success' => 'Berhasil! Tunjukkan QR Code di bawah ini kepada Admin.',
            'booking_code' => $bookingCode
        ]);
    }   
}