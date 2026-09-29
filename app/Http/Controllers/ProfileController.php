<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = auth()->user();
        return view('profile.edit', compact('user'));
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'current_password' => ['nullable', 'required_with:new_password', 'current_password'],
            'new_password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ];

        // Validasi tambahan khusus member
        if ($user->role === 'member') {
            $rules['nik'] = ['nullable', 'string', 'max:16'];
            $rules['phone'] = ['nullable', 'string', 'max:15'];
            $rules['address'] = ['nullable', 'string'];
            $rules['birth_date'] = ['nullable', 'date'];
            $rules['ktp_image_path'] = ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'];
        }

        $request->validate($rules, [
            'current_password.current_password' => 'Password lama yang Anda masukkan salah.',
            'new_password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;

        if ($user->role === 'member') {
            $user->nik = $request->nik;
            $user->phone = $request->phone;
            $user->address = $request->address;
            $user->birth_date = $request->birth_date;

            // Upload Foto KTP menggunakan kolom yang sudah ada: ktp_image_path
            if ($request->hasFile('ktp_image_path')) {
                if ($user->ktp_image_path && Storage::disk('public')->exists($user->ktp_image_path)) {
                    Storage::disk('public')->delete($user->ktp_image_path);
                }
                $path = $request->file('ktp_image_path')->store('ktp_photos', 'public');
                $user->ktp_image_path = $path;
            }
        }

        if ($request->filled('new_password')) {
            $user->password = Hash::make($request->new_password);
        }

        $user->save();

        return back()->with('success', 'Profil dan identitas berhasil diperbarui!');
    }

    // Endpoint AJAX simpan Face Recognition menggunakan kolom: face_image_path
    public function saveFace(Request $request)
    {
        $user = auth()->user();
        if ($user->role !== 'member') return response()->json(['success' => false]);

        $request->validate([
            'face_image' => 'required|string'
        ]);

        $imgData = $request->face_image;
        list($type, $imgData) = explode(';', $imgData);
        list(, $imgData) = explode(',', $imgData);
        $imgData = base64_decode($imgData);

        // Simpan file gambar wajah
        $fileName = 'faces/user_' . $user->id . '_' . time() . '.png';
        Storage::disk('public')->put($fileName, $imgData);

        if ($user->face_image_path && Storage::disk('public')->exists($user->face_image_path)) {
            Storage::disk('public')->delete($user->face_image_path);
        }
        
        $user->face_image_path = $fileName;
        $user->save();

        return response()->json(['success' => true, 'message' => 'Cuplikan wajah berhasil direkam!']);
    }
}