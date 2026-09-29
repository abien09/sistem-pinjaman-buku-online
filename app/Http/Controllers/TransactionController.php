<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\User;
use App\Models\BookCopy;
use Carbon\Carbon;
use App\Models\BorrowTransaction;
use App\Models\BorrowTransactionDetail;
use App\Models\Cart;
use Illuminate\Support\Str;

class TransactionController extends Controller
{
    // Halaman Scan Peminjaman (SUDAH DIPERBAIKI DENGAN PRG PATTERN)
    public function create()
    {
        if (auth()->user()->role !== 'admin') abort(403);
        
        $data = [];
        // Tangkap data dari proses POST (verifyBorrowScan) untuk memunculkan modal
        if (session('showModal')) {
            $type = session('type');
            $member = User::find(session('member_id'));

            if ($type === 'cart') {
                $transaction = BorrowTransaction::with(['user', 'details.bookCopy.book'])->find(session('transaction_id'));
                $data = [
                    'showModal' => true,
                    'type' => 'cart',
                    'transaction' => $transaction,
                    'items' => $transaction->details,
                    'member' => $member
                ];
            } else {
                $copy = BookCopy::with('book')->find(session('copy_id'));
                $data = [
                    'showModal' => true,
                    'type' => 'single',
                    'copy' => $copy,
                    'member' => $member
                ];
            }
        }
        
        return view('transactions.borrow-scan', $data);
    }

    // Proses Simpan Peminjaman (Fungsi Lama)
    public function store(Request $request)
    {
        if (auth()->user()->role !== 'admin') abort(403);

        $request->validate([
            'member_code' => 'required|string',
            'copy_code' => 'required|string'
        ]);

        // 1. Cari Member
        $member = User::where('member_code', $request->member_code)->where('role', 'member')->first();
        if (!$member) {
            return back()->with('error', 'Member tidak ditemukan!');
        }

        // 2. Cari Eksemplar Buku
        $bookCopy = BookCopy::where('copy_code', $request->copy_code)->first();
        if (!$bookCopy) {
            return back()->with('error', 'Buku fisik tidak ditemukan!');
        }

        // 3. Cek Status Ketersediaan Buku
        if ($bookCopy->status !== 'available') {
            return back()->with('error', 'Buku ini sedang dipinjam atau tidak tersedia!');
        }

        // 4. Simpan Transaksi (Batas pinjam 7 hari)
        Transaction::create([
            'user_id' => $member->id,
            'book_copy_id' => $bookCopy->id,
            'borrow_date' => Carbon::now(),
            'due_date' => Carbon::now()->addDays(30),
            'status' => 'borrowed',
            'late_fee' => 0
        ]);

        // 5. Ubah Status Buku menjadi Dipinjam
        $bookCopy->update(['status' => 'borrowed']);

        return back()->with('success', 'Peminjaman berhasil! Buku: ' . $bookCopy->book->title . ' dipinjam oleh ' . $member->name);
    }
    
    // Halaman Scan Pengembalian (Tampilkan View)
    public function returnForm()
    {
        if (auth()->user()->role !== 'admin') abort(403);
        return view('transactions.return');
    }

    // Step 1: Cek Buku & Munculkan Popup Modal Verifikasi
    public function verifyReturn(Request $request)
    {
        if (auth()->user()->role !== 'admin') abort(403);

        $request->validate([
            'copy_code' => 'required|string'
        ]);

        $transaction = Transaction::with(['user', 'bookCopy.book'])
            ->whereHas('bookCopy', function($query) use ($request) {
                $query->where('copy_code', $request->copy_code);
            })->where('status', 'borrowed')->first();

            if (!$transaction) {
                return back()->with('error', 'Buku fisik ini tidak sedang dalam status dipinjam atau QR Code salah!');
            }

        // Kalkulasi durasi & denda untuk ditampilkan di popup
        $borrowDate = \Carbon\Carbon::parse($transaction->borrow_date);
        $returnDate = now();
        $daysBorrowed = $borrowDate->diffInDays($returnDate);
        
        $lateFee = 0;
        $weeksLate = 0;
        if ($daysBorrowed > 30) {
            $weeksLate = ceil(($daysBorrowed - 30) / 7);
            $bookPrice = $transaction->bookCopy->book->price; 
            $lateFee = ($bookPrice * 0.10) * $weeksLate;
        }

        // Kirim data kembali ke view bersama flag agar modal otomatis terbuka
        return view('transactions.return', compact('transaction', 'daysBorrowed', 'lateFee'))->with('showModal', true);
    }

    // Step 2: Konfirmasi Final Pengembalian dari Modal
    public function processReturn(Request $request)
    {
        if (auth()->user()->role !== 'admin') abort(403);

        $request->validate([
            'transaction_id' => 'required|exists:transactions,id',
            'condition' => 'required|in:available,damaged,lost' // Memastikan kondisi fisik diinput
        ]);

        $transaction = Transaction::with('bookCopy.book')->findOrFail($request->transaction_id);

        $borrowDate = \Carbon\Carbon::parse($transaction->borrow_date);
        $returnDate = now();
        $daysBorrowed = $borrowDate->diffInDays($returnDate);
        
        // Kalkulasi Denda Keterlambatan
        $lateFee = 0;
        if ($daysBorrowed > 30) { // Sesuai aturan lama Anda
            $weeksLate = ceil(($daysBorrowed - 30) / 7);
            $bookPrice = $transaction->bookCopy->book->price; 
            $lateFee = ($bookPrice * 0.10) * $weeksLate;
        }

        // Update Transaksi menjadi 'returned'
        $transaction->update([
            'return_date' => $returnDate,
            'status' => 'returned',
            'late_fee' => $lateFee
        ]);

        // UBAH STATUS BUKU SESUAI HASIL PENGECEKAN ADMIN (Bagus / Rusak / Hilang)
        $transaction->bookCopy->update(['status' => $request->condition]);

        $message = 'Buku "' . $transaction->bookCopy->book->title . '" berhasil dikembalikan!';
        if ($lateFee > 0) {
            $message .= ' Terdapat denda keterlambatan: Rp ' . number_format($lateFee, 0, ',', '.');
        }
        
        if ($request->condition === 'damaged') {
            $message .= ' (Catatan: Buku dalam kondisi Rusak).';
        } elseif ($request->condition === 'lost') {
            $message .= ' (Catatan: Buku dilaporkan Hilang).';
        }

        return redirect()->route('transactions.return')->with('success', $message);
    }

    // HALAMAN SCAN & PROSES PENCOCOKAN QR (SUDAH DIPERBAIKI DENGAN PRG PATTERN)
    public function verifyBorrowScan(Request $request)
    {
        $memberCode = strtoupper(trim($request->member_code));
        $code = strtoupper(trim($request->code)); 

        // 1. Verifikasi Data Member Dulu (Aturan Dosen)
        // Tambahkan ->withInput() agar ketikan member tidak hilang saat ada error barcode buku
        $member = User::where('member_code', $memberCode)->where('role', 'member')->first();
        if (!$member) return back()->with('error', 'Data Member ('.$memberCode.') tidak valid!')->withInput();

        // 2. SKENARIO KERANJANG (BOOK-xxx)
        if (str_starts_with($code, 'BOOK')) {
            $transaction = BorrowTransaction::where('booking_code', $code)->first();

            if (!$transaction) return back()->with('error', 'Kode Booking tidak ditemukan.')->withInput();
            
            // CEK KEAMANAN: Apakah booking ini milik member yang scan KTP/Kartu-nya?
            if ($transaction->user_id !== $member->id) {
                return back()->with('error', 'Peringatan Keamanan! Kode booking ini milik orang lain, bukan milik '.$member->name)->withInput();
            }

            if ($transaction->status !== 'booked') return back()->with('error', 'Booking ini sudah diproses.')->withInput();

            // AMAN! Redirect dengan session (Menghindari 405 Method Not Allowed)
            return redirect()->route('transactions.create')->with([
                'showModal' => true,
                'type' => 'cart',
                'transaction_id' => $transaction->id,
                'member_id' => $member->id
            ]);
        } 
        
        // 3. SKENARIO SINGLE (BK-xxx)
        $copy = BookCopy::where('copy_code', $code)->first();
        if (!$copy) return back()->with('error', 'Kode Buku fisik tidak dikenali.')->withInput();
        if ($copy->status !== 'available') return back()->with('error', 'Buku sedang tidak tersedia.')->withInput();

        // AMAN! Redirect dengan session
        return redirect()->route('transactions.create')->with([
            'showModal' => true,
            'type' => 'single',
            'copy_id' => $copy->id,
            'member_id' => $member->id
        ]);
    }

// Proses Persetujuan Admin (Single & Cart)
    public function processBorrow(Request $request)
    {
        if ($request->type === 'cart') {$borrowTransaction = BorrowTransaction::with('details.bookCopy')->findOrFail($request->transaction_id);$borrowTransaction->update([
                'status' => 'borrowed', 
                'borrow_date' => now()
            ]);

            foreach ($borrowTransaction->details as$detail) {
                if ($detail->bookCopy) {$detail->bookCopy->update(['status' => 'borrowed']);
                }
                
                $detail->update([
                    'status' => 'borrowed', 
                    'due_date' => now()->addDays(7)
                ]);

                // Masukkan ke tabel `transactions` utama agar muncul di riwayat member
                Transaction::create([
                    'user_id' => $borrowTransaction->user_id,
                    'book_copy_id' => $detail->book_copy_id,
                    'borrow_date' => now(),
                    'due_date' => now()->addDays(30),
                    'status' => 'borrowed',
                    'late_fee' => 0
                ]);
            }

            return redirect()->route('transactions.create')->with('success', 'Peminjaman Keranjang Berhasil Disetujui dan Masuk Riwayat!');
        
        } else {
            $copy = BookCopy::findOrFail($request->book_copy_id);
            
            Transaction::create([
                'user_id' => $request->member_id, 
                'book_copy_id' => $copy->id,
                'borrow_date' => now(),
                'due_date' => now()->addDays(30),
                'status' => 'borrowed',
                'late_fee' => 0
            ]);
            
            $copy->update(['status' => 'borrowed']);

            return redirect()->route('transactions.create')->with('success', 'Peminjaman Single Berhasil Disetujui!');
        }
    }
}