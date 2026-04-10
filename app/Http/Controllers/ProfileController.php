<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit', [
            'user' => auth()->user(),
        ]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'current_password' => ['nullable', 'string'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $isUpdatingPassword = filled($data['password'] ?? null);

        if ($isUpdatingPassword) {
            if (!filled($data['current_password'] ?? null)) {
                return redirect()
                    ->back()
                    ->withInput($request->except(['current_password', 'password', 'password_confirmation']))
                    ->withErrors([
                        'current_password' => 'Password saat ini wajib diisi untuk mengganti password.',
                    ]);
            }

            if (!Hash::check($data['current_password'], $user->password)) {
                return redirect()
                    ->back()
                    ->withInput($request->except(['current_password', 'password', 'password_confirmation']))
                    ->withErrors([
                        'current_password' => 'Password saat ini tidak sesuai.',
                    ]);
            }
        }

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
        ];

        if ($isUpdatingPassword) {
            $payload['password'] = Hash::make($data['password']);
        }

        $user->update($payload);

        ActivityLogService::log(
            'profile.update',
            $isUpdatingPassword ? 'Memperbarui profil dan password akun.' : 'Memperbarui profil akun.',
            $user
        );

        return redirect()
            ->route('profile.edit')
            ->with('success', 'Profil berhasil diperbarui.');
    }
}
