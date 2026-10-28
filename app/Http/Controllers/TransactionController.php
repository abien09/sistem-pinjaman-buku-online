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
    // Halaman Scan Peminjaman (PRG Pattern)
    public function create()
    {
        if (auth()->user()->role !== 'admin') abort(403);
        
        $data = [];
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

    // Proses Simpan Peminjaman
    public function store(Request $request)
    {
        if (auth()->user()->role !== 'admin') abort(403);

        $request->validate([
            'member_code' => 'required|string',
            'copy_code' => 'required|string'
        ]);

        $member = User::where('member_code', $request->member_code)->where('role', 'member')->first();
        if (!$member) {
            return back()->with('error', 'Member tidak ditemukan!');
        }

        $activeBorrowedCount = Transaction::where('user_id', $member->id)->where('status', 'borrowed')->count();
        $cartCount = Cart::where('user_id', $member->id)->count();

        if (($activeBorrowedCount + $cartCount) >= 7) {
            return back()->with('error', 'User ini sudah mencapai batasan peminjaman');
        }

        $bookCopy = BookCopy::where('copy_code', $request->copy_code)->first();
        if (!$bookCopy) {
            return back()->with('error', 'Buku fisik tidak ditemukan!');
        }

        if ($bookCopy->status !== 'available') {
            return back()->with('error', 'Buku ini sedang dipinjam atau tidak tersedia!');
        }

        Transaction::create([
            'user_id' => $member->id,
            'book_copy_id' => $bookCopy->id,
            'borrow_date' => Carbon::now(),
            'due_date' => Carbon::now()->addDays(30),
            'status' => 'borrowed',
            'late_fee' => 0
        ]);

        $bookCopy->update(['status' => 'borrowed']);

        return back()->with('success', 'Peminjaman berhasil! Buku: ' . $bookCopy->book->title . ' dipinjam oleh ' . $member->name);
    }
    
    // Halaman Scan Pengembalian
    public function returnForm()
    {
        if (auth()->user()->role !== 'admin') abort(403);
        return view('transactions.return');
    }

    // Step 1: Cek Buku Satuan ATAU QR Pengembalian Sekaligus
    public function verifyReturn(Request $request)
    {
        if (auth()->user()->role !== 'admin') abort(403);

        $request->validate([
            'copy_code' => 'required|string'
        ]);

        $code = strtoupper(trim($request->copy_code));

        // Cek apakah yang di-scan adalah QR Pengembalian Sekaligus (Membawa kode MBR-xxx atau RETURN:xxx)
        $memberCode = null;
        if (str_starts_with($code, 'RETURN:')) {
            $memberCode = substr($code, strlen('RETURN:'));
        } elseif (str_starts_with($code, 'MBR-')) {
            $memberCode = $code;
        }

        if ($memberCode) {
            $member = User::where('member_code', $memberCode)->where('role', 'member')->first();
            if (!$member) {
                return back()->with('error', 'Member tidak ditemukan dari QR Pengembalian!');
            }

            // Ambil semua buku yang sedang dipinjam oleh member ini
            $transactions = Transaction::with(['user', 'bookCopy.book'])
                ->where('user_id', $member->id)
                ->where('status', 'borrowed')
                ->get();

            if ($transactions->isEmpty()) {
                return back()->with('error', 'Member ini tidak memiliki buku yang sedang dipinjam.');
            }

            // Siapkan kalkulasi denda untuk masing-masing buku
            $returnItems = $transactions->map(function($tx) {
                $borrowDate = Carbon::parse($tx->borrow_date);
                $daysBorrowed = $borrowDate->diffInDays(now());
                $lateFee = 0;
                $isMaxLateFee = false;

                if ($daysBorrowed > 30) {
                    $weeksLate = ceil(($daysBorrowed - 30) / 7);
                    $bookPrice = $tx->bookCopy->book->price;
                    $penaltyPercentage = min($weeksLate * 0.10, 1.0);
                    $lateFee = $bookPrice * $penaltyPercentage;
                    if ($penaltyPercentage == 1.0) $isMaxLateFee = true;
                }

                return [
                    'transaction' => $tx,
                    'daysBorrowed' => $daysBorrowed,
                    'lateFee' => $lateFee,
                    'isMaxLateFee' => $isMaxLateFee
                ];
            });

            return view('transactions.return', compact('returnItems', 'member'))->with('showModal', true);
        }

        // Jika scan QR Buku Satuan (BK-xxx)
        $transaction = Transaction::with(['user', 'bookCopy.book'])
            ->whereHas('bookCopy', function($query) use ($code) {
                $query->where('copy_code', $code);
            })->where('status', 'borrowed')->first();

        if (!$transaction) {
            return back()->with('error', 'Buku fisik ini tidak sedang dalam status dipinjam atau QR Code salah!');
        }

        $borrowDate = Carbon::parse($transaction->borrow_date);
        $daysBorrowed = $borrowDate->diffInDays(now());
        
        $lateFee = 0;
        $isMaxLateFee = false; 
        
        if ($daysBorrowed > 30) {
            $weeksLate = ceil(($daysBorrowed - 30) / 7);
            $bookPrice = $transaction->bookCopy->book->price; 
            $penaltyPercentage = min($weeksLate * 0.10, 1.0);
            $lateFee = $bookPrice * $penaltyPercentage;
            
            if ($penaltyPercentage == 1.0) {
                $isMaxLateFee = true;
            }
        }
        
        return view('transactions.return', compact('transaction', 'daysBorrowed', 'lateFee', 'isMaxLateFee'))->with('showModal', true);
    }

    // Step 2: Konfirmasi Final Pengembalian (Bisa Satuan atau Sekaligus)
    public function processReturn(Request $request)
    {
        if (auth()->user()->role !== 'admin') abort(403);

        // Mendukung proses massal (banyak ID) maupun satuan (satu ID)
        $request->validate([
            'transaction_id' => 'nullable|exists:transactions,id',
            'transaction_ids' => 'nullable|array',
            'transaction_ids.*' => 'exists:transactions,id',
            'conditions' => 'nullable|array',
            'condition' => 'nullable|in:available,damaged,lost'
        ]);

        $transactionIds = $request->input('transaction_ids', [$request->input('transaction_id')]);
        $conditions = $request->input('conditions', [$request->input('transaction_id') => $request->input('condition', 'available')]);

        foreach ($transactionIds as $txId) {
            if (!$txId) continue;
            
            $transaction = Transaction::with(['bookCopy.book', 'user'])->findOrFail($txId);
            $bookPrice = $transaction->bookCopy->book->price;
            $condition = $conditions[$txId] ?? 'available';

            $borrowDate = Carbon::parse($transaction->borrow_date);
            $daysBorrowed = $borrowDate->diffInDays(now());
            
            $lateFee = 0;
            if ($daysBorrowed > 30) {
                $weeksLate = ceil(($daysBorrowed - 30) / 7);
                $penaltyPercentage = min($weeksLate * 0.10, 1.0);
                $lateFee = $bookPrice * $penaltyPercentage;
            }

            $conditionFee = in_array($condition, ['damaged', 'lost']) ? $bookPrice : 0;
            $totalDenda = $lateFee + $conditionFee;

            $transaction->update([
                'return_date' => now(),
                'status' => 'returned',
                'late_fee' => $totalDenda,
                'condition_fee' => $conditionFee
            ]);

            $copyStatus = in_array($condition, ['damaged', 'lost']) ? $condition : 'available';
            $transaction->bookCopy->update(['status' => $copyStatus]);
        }

        return redirect()->route('transactions.return')->with('success', 'Pengembalian buku berhasil diproses!');
    }

    // Scan Peminjaman
    public function verifyBorrowScan(Request $request)
    {
        $memberCode = strtoupper(trim($request->member_code));
        $code = strtoupper(trim($request->code)); 

        $member = User::where('member_code', $memberCode)->where('role', 'member')->first();
        if (!$member) return back()->with('error', 'Data Member ('.$memberCode.') tidak valid!')->withInput();

        $activeBorrowedCount = Transaction::where('user_id', $member->id)->where('status', 'borrowed')->count();
        $cartCount = Cart::where('user_id', $member->id)->count();

        if (($activeBorrowedCount + $cartCount) >= 7) {
            return back()->with('error', 'User ini sudah mencapai batasan peminjaman')->withInput();
        }

        if (str_starts_with($code, 'BOOK')) {
            $transaction = BorrowTransaction::where('booking_code', $code)->first();

            if (!$transaction) return back()->with('error', 'Kode Booking tidak ditemukan.')->withInput();
            if ($transaction->user_id !== $member->id) {
                return back()->with('error', 'Peringatan Keamanan! Kode booking ini milik orang lain.')->withInput();
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

    public function processBorrow(Request $request)
    {
        $memberId = $request->type === 'cart' 
            ? BorrowTransaction::findOrFail($request->transaction_id)->user_id 
            : $request->member_id;

        $activeBorrowedCount = Transaction::where('user_id', $memberId)->where('status', 'borrowed')->count();
        $cartCount = Cart::where('user_id', $memberId)->count();

        if (($activeBorrowedCount + $cartCount) >= 7) {
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