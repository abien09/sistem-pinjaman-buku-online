<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class BorrowTransactionDetail extends Model
{
    protected $table = 'borrow_transaction_details';
    protected $fillable = ['borrow_transaction_id', 'book_copy_id', 'status', 'due_date', 'returned_at', 'late_fee'];

    public function bookCopy() {
        return $this->belongsTo(BookCopy::class);
    }
}