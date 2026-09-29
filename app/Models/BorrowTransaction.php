<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class BorrowTransaction extends Model
{
    // WAJIB ADA: Mencegah error salah baca nama tabel
    protected $table = 'borrow_transactions'; 
    protected $fillable = ['booking_code', 'user_id', 'status', 'booking_expires_at', 'borrow_date'];

    public function user() {
        return $this->belongsTo(User::class);
    }
    public function details() {
        return $this->hasMany(BorrowTransactionDetail::class, 'borrow_transaction_id');
    }
}