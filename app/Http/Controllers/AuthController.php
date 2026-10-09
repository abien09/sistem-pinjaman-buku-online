<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;

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

        // 4. Generate OTP (6 Digit)
        $otp = (string) random_int(100000, 999999);

        // 5. Masukkan ke Database
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
            'otp_code' => Hash::make($otp),
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        // 6. Generate Barcode Member Unik (Contoh: MBR-00001)
        $user->update([
            'member_code' => 'MBR-' . str_pad($user->id, 5, '0', STR_PAD_LEFT)
        ]);

        // 7. Kirim Email OTP
        try {
            $this->sendOtpEmail($user->email, $user->name, $otp);
        } catch (\Throwable $e) {
            // Jika email gagal, hapus foto dan data user agar bisa daftar ulang
            Storage::disk('public')->delete($ktpPath);
            Storage::disk('public')->delete($facePath);
            $user->delete();
            return back()->withInput()->withErrors(['email' => 'Gagal mengirim email OTP: ' . $e->getMessage()]);
        }

        // 8. Simpan email ke session untuk proses verifikasi, DILARANG LANGSUNG LOGIN
        session(['pending_email' => $user->email]);

        return redirect()->route('verify')->with('success', 'Registrasi berhasil! Kode verifikasi telah dikirim ke email Anda.');
    }

    // ==========================================
    // FUNGSI BARU UNTUK VERIFIKASI OTP
    // ==========================================
    public function showVerify()
    {
        if (!session('pending_email')) {
            return redirect()->route('register');
        }
        return view('auth.verify', ['email' => session('pending_email')]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate(['otp' => 'required|digits:6'], [
            'otp.required' => 'Kode OTP wajib diisi.',
            'otp.digits' => 'Kode OTP harus 6 digit.',
        ]);

        $user = User::where('email', session('pending_email'))->first();
        
        if (!$user) {
            return redirect()->route('register')->withErrors(['email' => 'Email tidak terdaftar.']);
        }
        
        if (!$user->otp_expires_at || now()->gt($user->otp_expires_at)) {
            return back()->withErrors(['otp' => 'Kode OTP sudah kadaluarsa. Silakan klik "Kirim ulang kode".']);
        }
        
        if (!Hash::check($request->otp, $user->otp_code)) {
            return back()->withErrors(['otp' => 'Kode OTP salah.']);
        }

        // Jika Benar, Verifikasi Emailnya
        $user->forceFill([
            'email_verified_at' => now(),
            'otp_code' => null,
            'otp_expires_at' => null,
        ])->save();

        session()->forget('pending_email');

        return redirect()->route('login')->with('success', 'Email berhasil diverifikasi! Silakan login.');
    }

    public function resendOtp()
    {
        $user = User::where('email', session('pending_email'))->first();
        if (!$user) {
            return redirect()->route('register')->withErrors(['email' => 'Email tidak ditemukan.']);
        }

        $otp = (string) random_int(100000, 999999);
        $user->forceFill([
            'otp_code' => Hash::make($otp),
            'otp_expires_at' => now()->addMinutes(10),
        ])->save();

        try {
            $this->sendOtpEmail($user->email, $user->name, $otp);
        } catch (\Throwable $e) {
            return back()->withErrors(['otp' => 'Gagal mengirim ulang email.']);
        }

        return back()->with('success', 'Kode OTP baru telah dikirim ke ' . $user->email);
    }

    private function sendOtpEmail(string $email, string $name, string $otp): void
    {
        $body = "Halo {$name},\n\n"
            . "Terima kasih telah mendaftar di Perpustakaan Digital.\n"
            . "Kode verifikasi email Anda adalah:\n\n"
            . "    {$otp}\n\n"
            . "Kode berlaku selama 10 menit. Jangan bagikan kode ini kepada siapa pun.\n";

        Mail::raw($body, function ($m) use ($email) {
            $m->to($email)->subject('Kode Verifikasi - Perpustakaan Digital');
        });
    }


    // ==========================================
    // LOGIN & LOGOUT (Diperbarui)
    // ==========================================
    public function showLogin()
    {
        return view('auth.login');
    }

    public function processLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $credentials['email'])->first();
        
        if (!$user) {
            return back()->withErrors(['email' => 'Email atau Password salah.'])->onlyInput('email');
        }

        // CEK APAKAH EMAIL SUDAH DIVERIFIKASI
        if (!$user->email_verified_at) {
            session(['pending_email' => $user->email]);
            return redirect()->route('verify')->withErrors(['otp' => 'Akun belum diverifikasi. Masukkan kode OTP yang dikirim ke email Anda.']);
        }

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->route('dashboard')->with('success', 'Login berhasil!');
        }

        return back()->withErrors([
            'email' => 'Email atau Password salah.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}