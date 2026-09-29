<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel Keranjang Temporary (Sisi Member)
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('book_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        // 2. Tabel Transaksi Header (Booking / Umbrella Transaction)
        Schema::create('borrow_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code')->unique(); // Misal: BOOK-20260928-001
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['booked', 'borrowed', 'completed', 'cancelled'])->default('booked');
            $table->timestamp('booking_expires_at'); // Batas 1x24 jam
            $table->timestamp('borrow_date')->nullable();
            $table->timestamps();
        });

        // 3. Tabel Detail Transaksi (Mengikat Eksemplar Fisik & Pengembalian 1 per 1)
        Schema::create('borrow_transaction_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('borrow_transaction_id')->constrained()->onDelete('cascade');
            $table->foreignId('book_copy_id')->constrained()->onDelete('cascade');
            $table->date('due_date')->nullable(); // Batas 30 hari dari tanggal pinjam
            $table->timestamp('returned_at')->nullable(); // Diisi saat buku dikembalikan 1 per 1
            $table->decimal('late_fee', 10, 2)->default(0);
            $table->enum('status', ['booked', 'borrowed', 'returned'])->default('booked');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('borrow_transaction_details');
        Schema::dropIfExists('borrow_transactions');
        Schema::dropIfExists('carts');
    }
};