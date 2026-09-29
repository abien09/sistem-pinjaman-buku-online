<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\BookCopy;
use App\Models\BorrowTransaction;
use App\Models\BorrowTransactionDetail;
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

    // Menambah buku ke keranjang (Bisa Anda pasang di halaman detail buku)
    public function store(Request $request)
        {
            $request->validate(['book_id' => 'required|exists:books,id']);
            
            $count = Cart::where('user_id', \Illuminate\Support\Facades\Auth::id())->count();
            if ($count >= 5) {
                if ($request->ajax()) return response()->json(['error' => 'Keranjang maksimal 5 buku!'], 400);
                return back()->with('error', 'Keranjang maksimal 5 buku!');
            }

            Cart::firstOrCreate([
                'user_id' => \Illuminate\Support\Facades\Auth::id(),
                'book_id' => $request->book_id
            ]);

            if ($request->ajax()) return response()->json(['success' => true]);
            return back()->with('success', 'Buku masuk ke keranjang!');
        }

    // Checkout Keranjang
    public function checkout()
    {
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