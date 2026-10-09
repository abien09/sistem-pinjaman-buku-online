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

        // Cek Batasan 7 Buku (Dipinjam + Dibooking)
        $activeBorrowedCount = Transaction::where('user_id', $member->id)->where('status', 'borrowed')->count();
        $activeBookedCount = BorrowTransactionDetail::whereHas('borrowTransaction', function($q) use ($member) {
            $q->where('user_id', $member->id)->where('status', 'booked');
        })->count();

        if (($activeBorrowedCount + $activeBookedCount) >= 7) {
            return back()->with('error', 'User ini sudah mencapai batasan peminjaman');
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
        $isMaxLateFee = false; 
        
        if ($daysBorrowed > 30) {
            $weeksLate = ceil(($daysBorrowed - 30) / 7);
            $bookPrice = $transaction->bookCopy->book->price; 
            
            // Batasi persentase denda maksimal 1.0 (100%)
            $penaltyPercentage = min($weeksLate * 0.10, 1.0);
            $lateFee = $bookPrice * $penaltyPercentage;
            
            if ($penaltyPercentage == 1.0) {
                $isMaxLateFee = true;
            }
        }
        
        return view('transactions.return', compact('transaction', 'daysBorrowed', 'lateFee', 'isMaxLateFee'))->with('showModal', true);
    }

    // Step 2: Konfirmasi Final Pengembalian dari Modal
    public function processReturn(Request $request)
    {
        if (auth()->user()->role !== 'admin') abort(403);

        $request->validate([
            'transaction_id' => 'required|exists:transactions,id',
            'condition' => 'required|in:available,damaged,lost' 
        ]);

        $transaction = \App\Models\Transaction::with(['bookCopy.book', 'user'])->findOrFail($request->transaction_id);
        $bookPrice = $transaction->bookCopy->book->price;

        $borrowDate = \Carbon\Carbon::parse($transaction->borrow_date);
        $returnDate = now();
        $daysBorrowed = $borrowDate->diffInDays($returnDate);
        
        // 1. Hitung Denda Keterlambatan (Late Fee)
        $lateFee = 0;
        $isMaxLateFee = false;

        if ($daysBorrowed > 30) {
            $weeksLate = ceil(($daysBorrowed - 30) / 7);
            $penaltyPercentage = min($weeksLate * 0.10, 1.0);
            $lateFee = $bookPrice * $penaltyPercentage;

            if ($penaltyPercentage == 1.0) {
                $isMaxLateFee = true;
            }
        }

        // 2. Hitung Denda Kondisi Fisik (Hanya Bagus = 0, Rusak/Hilang = 100%)
        $conditionFee = 0;
        if (in_array($request->condition, ['damaged', 'lost'])) {
            $conditionFee = $bookPrice * 1.0; 
        }

        // Hitung Total Keseluruhan Denda
        $totalDenda = $lateFee + $conditionFee;

        // 3. Update Transaksi
        $transaction->update([
            'return_date' => $returnDate,
            'status' => 'returned',
            'late_fee' => $totalDenda,
            'condition_fee' => $conditionFee
        ]);

        // 4. Update Status Buku Fisik
        $copyStatus = in_array($request->condition, ['damaged', 'lost']) ? $request->condition : 'available';
        $transaction->bookCopy->update(['status' => $copyStatus]);

        // 5. Susun Pesan Flash & Peringatan Admin
        $message = 'Buku "' . $transaction->bookCopy->book->title . '" berhasil dikembalikan!';
        
        if ($totalDenda > 0) {
            $message .= ' Total tagihan denda: Rp ' . number_format($totalDenda, 0, ',', '.');
        }

        if ($isMaxLateFee) {
            $alertMsg = "PERINGATAN DENDA MAKSIMAL! Member telat lebih dari 10 minggu. Segera hubungi member ini! Email: {$transaction->user->email} | WA: {$transaction->user->phone} | Alamat: {$transaction->user->address}";
            return redirect()->route('transactions.return')->with('error', $alertMsg);
        }

        return redirect()->route('transactions.return')->with('success', $message);
    }

   // HALAMAN SCAN & PROSES PENCOCOKAN QR
    public function verifyBorrowScan(Request $request)
    {
        $memberCode = strtoupper(trim($request->member_code));
        $code = strtoupper(trim($request->code)); 

        $member = User::where('member_code', $memberCode)->where('role', 'member')->first();
        if (!$member) return back()->with('error', 'Data Member ('.$memberCode.') tidak valid!')->withInput();

        // CEK BATASAN 7 BUKU SAAT SCAN (Aman dari error relasi)
        $activeBorrowedCount = Transaction::where('user_id', $member->id)->where('status', 'borrowed')->count();
        $cartCount = Cart::where('user_id', $member->id)->count();

        if (($activeBorrowedCount + $cartCount) >= 7) {
            return back()->with('error', 'User ini sudah mencapai batasan peminjaman')->withInput();
        }

        if (str_starts_with($code, 'BOOK')) {
            $transaction = BorrowTransaction::where('booking_code', $code)->first();

            if (!$transaction) return back()->with('error', 'Kode Booking tidak ditemukan.')->withInput();
            
            if ($transaction->user_id !== $member->id) {
                return back()->with('error', 'Peringatan Keamanan! Kode booking ini milik orang lain, bukan milik '.$member->name)->withInput();
            }

            if ($transaction->status !== 'booked') return back()->with('error', 'Booking ini sudah diproses.')->withInput();

            return redirect()->route('transactions.create')->with([
                'showModal' => true,
                'type' => 'cart',
                'transaction_id' => $transaction->id,
                'member_id' => $member->id
            ]);
        } 
        
        $copy = BookCopy::where('copy_code', $code)->first();
        if (!$copy) return back()->with('error', 'Kode Buku fisik tidak dikenali.')->withInput();
        if ($copy->status !== 'available') return back()->with('error', 'Buku sedang tidak tersedia.')->withInput();

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
        // Tentukan ID member berdasarkan tipe transaksi
        $memberId = $request->type === 'cart' 
            ? BorrowTransaction::findOrFail($request->transaction_id)->user_id 
            : $request->member_id;

        // CEK BATASAN 7 BUKU SAAT KLIK VALIDASI OLEH ADMIN
        $activeBorrowedCount = Transaction::where('user_id', $memberId)->where('status', 'borrowed')->count();
        $activeBookedCount = BorrowTransactionDetail::whereHas('borrowTransaction', function($q) use ($memberId) {
            $q->where('user_id', $memberId)->where('status', 'booked');
        })->count();

        if (($activeBorrowedCount + $activeBookedCount) >= 7) {
            return redirect()->route('transactions.create')->with('error', 'User ini sudah mencapai batasan peminjaman');
        }

        if ($request->type === 'cart') {
            $borrowTransaction = BorrowTransaction::with('details.bookCopy')->findOrFail($request->transaction_id);
            $borrowTransaction->update([
                'status' => 'borrowed', 
                'borrow_date' => now()
            ]);

            foreach ($borrowTransaction->details as $detail) {
                if ($detail->bookCopy) {
                    $detail->bookCopy->update(['status' => 'borrowed']);
                }
                
                $detail->update([
                    'status' => 'borrowed', 
                    'due_date' => now()->addDays(7)
                ]);

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