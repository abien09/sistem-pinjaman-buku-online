<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;;
use Illuminate\Support\Facades\Hash;
use App\Models\Setting;
use App\Models\Category;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Bintang Admin',
            'email' => 'admin@perpustakaan.com',
            'password' => Hash::make('admin123'), // Password untuk login
            'role' => 'admin',
            'nik' => '3171070909050002',
            'member_code' => 'ADM-00001',
        ]);

        Setting::create([
            'key' => 'store_status',
            'value' => 'open' // Nilai default toko buka
        ]);

        //Buat Kategori Dummy untuk Buku
        Category::create(['name' => 'Fiksi', 'slug' => 'fiksi']);
        Category::create(['name' => 'Sains & Teknologi', 'slug' => 'sains-teknologi']);
    }
}
