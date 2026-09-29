<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\Setting;
use Carbon\Carbon;

class MemberBookController extends Controller
{
    public function show($id)
{
    if (auth()->user()->role !== 'member') abort(403);

    $book = Book::with('category', 'copies')->findOrFail($id);

    // Gunakan now() agar otomatis mengikuti waktu simulasi (Time Travel)
    $isMonday = (date('N') == 1);
    
    $setting = Setting::where('key', 'store_status')->first();
    $storeStatus = $setting ? $setting->value : 'open';

    $isClosed = $isMonday || ($storeStatus === 'closed');
    $closedReason = $isMonday ? 'Perpustakaan libur setiap hari Senin.' : 'Perpustakaan sedang ditutup oleh Admin.';

    return view('member.book-detail', compact('book', 'isClosed', 'closedReason'));
}
}