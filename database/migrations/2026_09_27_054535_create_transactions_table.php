<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('book_copy_id')->constrained('book_copies')->onDelete('restrict');
                
                $table->date('borrow_date');
                $table->date('due_date');
                $table->date('return_date')->nullable();
                
                $table->enum('return_condition', ['good', 'damaged', 'lost'])->nullable();
                $table->decimal('late_fee', 15, 2)->default(0);
                $table->enum('status', ['borrowed', 'returned', 'lost'])->default('borrowed');
                
                $table->timestamps();
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
