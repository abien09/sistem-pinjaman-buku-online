<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Book extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    
    // ATURAN BISNIS: Sembunyikan harga dari member
    protected $hidden = ['price']; 

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function copies()
    {
        return $this->hasMany(BookCopy::class);
    }

    // Helper untuk menghitung eksemplar yang tersedia
    public function getAvailableCopiesCountAttribute()
    {
        return $this->copies()->where('status', 'available')->count();
    }
}