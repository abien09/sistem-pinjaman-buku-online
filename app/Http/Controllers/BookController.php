<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Category;
use Illuminate\Support\Str;

class BookController extends Controller
{
    // Menampilkan daftar buku
    public function index()
    {
        // Hanya Admin yang boleh mengakses ini
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Akses Ditolak');
        }

        $books = Book::with('category')->withCount('copies')->get();
        return view('books.index', compact('books'));
    }

    // Form tambah buku
    public function create()
    {
        if (auth()->user()->role !== 'admin') abort(403);
        
        $categories = Category::all();
        return view('books.create', compact('categories'));
    }

    // Menyimpan buku & generate eksemplar
public function store(Request $request)
    {
        if (auth()->user()->role !== 'admin') abort(403);

        $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'number_of_copies' => 'required|integer|min:1',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048' // Validasi gambar
        ]);

        // Proses Upload Gambar (Jika ada)
        $coverPath = null;
        if ($request->hasFile('cover_image')) {
            $coverPath = $request->file('cover_image')->store('book_covers', 'public');
        }

        // 1. Simpan Data Buku Utama
        $book = Book::create([
            'title' => $request->title,
            'author' => $request->author,
            'category_id' => $request->category_id,
            'description' => $request->description,
            'price' => $request->price,
            'cover_image' => $coverPath, // Simpan path gambar ke database
        ]);

        // 2. Generate Eksemplar & QR Code
        for ($i = 1; $i <= $request->number_of_copies; $i++) {
            BookCopy::create([
                'book_id' => $book->id,
                'copy_code' => 'BK-' . $book->id . '-C' . $i . '-' . strtoupper(\Illuminate\Support\Str::random(4)),
                'status' => 'available'
            ]);
        }

        return redirect()->route('books.index')->with('success', 'Buku dan eksemplar berhasil ditambahkan!');
    }

    // Menampilkan detail buku beserta gambar QR Code Eksemplarnya
    public function show($id)
    {
        if (auth()->user()->role !== 'admin') abort(403);

        $book = Book::with('copies')->findOrFail($id);
        return view('books.show', compact('book'));
    }

// Menampilkan halaman form edit buku beserta daftar eksemplar fisiknya
    public function edit($id)
    {
        if (auth()->user()->role !== 'admin') abort(403);
        
        $book = \App\Models\Book::with('copies')->findOrFail($id);
        $categories = \App\Models\Category::all();
        
        return view('books.edit', compact('book', 'categories'));
    }

    // Proses memperbarui data buku dan menambah/mengatur stok eksemplar
    public function update(Request $request, $id)
    {
        if (auth()->user()->role !== 'admin') abort(403);

        $book = \App\Models\Book::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'additional_stock' => 'nullable|integer|min:1', // Input untuk menambah stok baru
        ]);

        // 1. Update Informasi Dasar Buku
        $book->update([
            'title' => $request->title,
            'author' => $request->author,
            'category_id' => $request->category_id,
        ]);

        // 2. Jika Admin menambahkan stok baru, generate eksemplar & QR Code baru
        if ($request->filled('additional_stock') && $request->additional_stock > 0) {
            for ($i = 0; $i < $request->additional_stock; $i++) {
                \App\Models\BookCopy::create([
                    'book_id' => $book->id,
                    'copy_code' => 'BK-' . $book->id . '-' . strtoupper(\Illuminate\Support\Str::random(6)),
                    'status' => 'available'
                ]);
            }
        }

        return redirect()->route('books.index')->with('success', 'Data buku dan stok eksemplar berhasil diperbarui!');
    }
}