<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Book;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        if (auth()->user()->role !== 'admin') abort(403);

        // 1. Seluruh Riwayat Peminjaman (Siapa meminjam buku apa)
        $transactions = Transaction::with(['user', 'bookCopy.book'])->latest()->paginate(10);

        // 2. Buku Paling Laris (Berdasarkan jumlah transaksi peminjaman terbanyak)
        $topBooks = Book::with('category')
            ->withCount(['copies as total_borrowed' => function($query) {
                $query->join('transactions', 'book_copies.id', '=', 'transactions.book_copy_id');
            }])
            ->orderByDesc('total_borrowed')
            ->take(5)
            ->get();

        // 3. Member Paling Sering Meminjam
        $topMembers = User::where('role', 'member')
            ->withCount('transactions')
            ->orderByDesc('transactions_count')
            ->take(5)
            ->get();

        return view('admin.reports', compact('transactions', 'topBooks', 'topMembers'));
    }
}