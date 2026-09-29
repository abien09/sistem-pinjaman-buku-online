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
        Schema::create('borrow_transaction_details', function (Blueprint $table) {
        $table->id();
        $table->foreignId('borrow_transaction_id')->constrained('borrow_transactions')->onDelete('cascade');
        $table->foreignId('book_copy_id')->constrained('book_copies')->onDelete('cascade');
        $table->enum('status', ['booked', 'borrowed', 'returned'])->default('booked');
        $table->timestamp('due_date')->nullable();
        $table->timestamp('returned_at')->nullable();
        $table->integer('late_fee')->default(0);
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('borrow_transaction_details');
    }
};
