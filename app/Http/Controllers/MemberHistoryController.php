<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\BorrowTransaction; // Pastikan model ini di-import
use Illuminate\Support\Facades\Auth;

class MemberHistoryController extends Controller
{
    public function index()
    {
        // 1. Ambil history peminjaman single (yang lama)
        $histories = Transaction::with('bookCopy.book')
                        ->where('user_id', Auth::id())
                        ->orderBy('created_at', 'desc')
                        ->paginate(10);

        // 2. Ambil data Booking Keranjang yang masih aktif
        $activeBookings = BorrowTransaction::where('user_id', Auth::id())
                        ->where('status', 'booked')
                        ->get();

        return view('member.history', compact('histories', 'activeBookings'));
    }
}