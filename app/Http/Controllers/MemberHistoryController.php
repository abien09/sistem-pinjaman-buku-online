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
    $userId = auth()->id();
    
    // Urutkan status 'borrowed' agar selalu di atas, lalu berdasarkan tanggal terbaru
    $histories = Transaction::with(['bookCopy.book'])
        ->where('user_id', $userId)
        ->orderByRaw("CASE WHEN status = 'borrowed' THEN 1 ELSE 2 END")
        ->orderBy('created_at', 'desc')
        ->paginate(10);

    $activeBookings = BorrowTransaction::where('user_id', $userId)
        ->where('status', 'booked')
        ->get();

    return view('member.history', compact('histories', 'activeBookings'));
}
}