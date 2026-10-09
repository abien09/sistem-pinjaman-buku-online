<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'author',
        'publisher',
        'category_id',
        'description',
        'cover_image',
        'price',
        'is_rare'
    ];

    protected $hidden = [
        'price'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_rare' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function copies()
    {
        return $this->hasMany(BookCopy::class);
    }

    public function getAvailableCopiesCountAttribute()
    {
        return $this->copies()
            ->where('status', 'available')
            ->count();
    }

    // Buku rare atau harga > 1 juta = hanya baca di tempat
    public function isReadOnly(): bool
    {
        return $this->is_rare || $this->price > 1000000;
    }
}