<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    // Menampilkan halaman registrasi
    public function showRegister()
    {
        return view('auth.register');
    }

    // Memproses data registrasi
    public function processRegister(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'nik' => 'required|numeric|unique:users,nik',
            'birth_date' => 'required|date',
            'address' => 'required',
            'phone' => 'required',
            'ktp_image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'face_descriptor' => 'required', // Array biometrik wajah
            'face_image_base64' => 'required' // Snapshot wajah
        ]);

        // 2. Simpan Foto KTP
        $ktpPath = $request->file('ktp_image')->store('ktp_images', 'public');

        // 3. Simpan Snapshot Wajah (dari Base64 Webcam)
        $image_parts = explode(";base64,", $request->face_image_base64);
        $image_base64 = base64_decode($image_parts[1]);
        $facePath = 'faces/face_' . time() . '.jpg';
        Storage::disk('public')->put($facePath, $image_base64);

        // 4. Masukkan ke Database
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'nik' => $request->nik,
            'birth_date' => $request->birth_date,
            'address' => $request->address,
            'phone' => $request->phone,
            'ktp_image_path' => $ktpPath,
            'face_image_path' => $facePath,
            'face_descriptor' => $request->face_descriptor,
            'role' => 'member',
        ]);

        // 5. Generate Barcode Member Unik (Contoh: MBR-00001)
        $user->update([
            'member_code' => 'MBR-' . str_pad($user->id, 5, '0', STR_PAD_LEFT)
        ]);

        // 6. Langsung Login-kan user setelah berhasil daftar
        Auth::login($user);

        return redirect()->route('dashboard')->with('success', 'Registrasi Berhasil! Selamat datang di perpustakaan.');
    }

    // Menampilkan Halaman Login
    public function showLogin()
    {
        return view('auth.login');
    }

    // Memproses Login
    public function processLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            
            // Redirect sesuai role (nanti admin dan member beda tampilan)
            return redirect()->route('dashboard')->with('success', 'Login berhasil!');
        }

        return back()->withErrors([
            'email' => 'Email atau Password salah.',
        ])->onlyInput('email');
    }

    // Memproses Logout
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}