<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\Category;
use App\Models\Transaction;

class MemberController extends Controller
{
    // Halaman Utama / Katalog Buku untuk Member
    public function index(Request $request)
    {
        if (auth()->user()->role !== 'member') abort(403);

        $categories = Category::all();
        
        // Fitur Filter berdasarkan kategori jika diklik
        $query = Book::with('category')->withCount(['copies as available_copies_count' => function($q) {
            $q->where('status', 'available');
        }]);

        if ($request->has('category') && $request->category != '') {
            $query->where('category_id', $request->category);
        }

        $books = $query->get();

        // Ambil riwayat peminjaman user yang sedang login
        $histories = Transaction::with('bookCopy.book')
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        return view('member.catalog', compact('books', 'categories', 'histories'));
    }

    // 1. Menampilkan daftar seluruh member terdaftar khusus Admin
    public function adminIndex()
    {
        if (auth()->user()->role !== 'admin') abort(403);

        $members = \App\Models\User::where('role', 'member')->withCount('transactions')->get();
        return view('admin.members.index', compact('members'));
    }

    // 2. Menampilkan detail biodata lengkap & riwayat peminjaman member
    public function adminShow($id)
    {
        if (auth()->user()->role !== 'admin') abort(403);

        $member = \App\Models\User::with(['transactions.bookCopy.book'])->findOrFail($id);
        
        // Hitung umur jika tanggal lahir tersedia
        $age = null;
        if ($member->birth_date) {
            $age = \Carbon\Carbon::parse($member->birth_date)->age;
        }

        return view('admin.members.show', compact('member', 'age'));
    }
}