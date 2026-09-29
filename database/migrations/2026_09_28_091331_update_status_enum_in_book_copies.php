<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Memperbarui kolom enum untuk menerima 'booked'
        DB::statement("ALTER TABLE book_copies MODIFY COLUMN status ENUM('available', 'borrowed', 'booked', 'lost', 'damaged') DEFAULT 'available'");
    }

    public function down(): void
    {
        // Kembalikan seperti semula jika di-rollback
        DB::statement("ALTER TABLE book_copies MODIFY COLUMN status ENUM('available', 'borrowed', 'lost', 'damaged') DEFAULT 'available'");
    }
};