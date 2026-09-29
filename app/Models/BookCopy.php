<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BookCopy extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}