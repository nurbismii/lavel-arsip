<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    public function index()
    {
        $users = User::with('teams')
            ->orderByRaw("CASE role WHEN 'admin' THEN 0 WHEN 'manager' THEN 1 WHEN 'supervisor' THEN 2 ELSE 3 END")
            ->orderBy('name')
            ->paginate(10);
        $teams = Team::orderBy('name')->get();
        $roleOptions = User::roleOptions();

        return view('users.index', compact('users', 'teams', 'roleOptions'));
    }

    public function update(Request $request, User $user)
    {
        $admin = auth()->user();

        if ((int) $admin->id === (int) $user->id) {
            return redirect()
                ->route('users.index')
                ->with('error', 'Role dan status akun Anda sendiri tidak dapat diubah dari menu ini.');
        }

        $data = $request->validate([
            'role' => ['required', Rule::in(array_keys(User::roleOptions()))],
            'is_active' => ['required', Rule::in(['0', '1'])],
            'team_ids' => ['nullable', 'array'],
            'team_ids.*' => ['integer', 'exists:teams,id'],
        ]);

        $user->update([
            'role' => $data['role'],
            'is_active' => (bool) $data['is_active'],
        ]);
        $user->teams()->sync($data['team_ids'] ?? []);
        $user->load('teams');

        ActivityLogService::log(
            'user.update',
            'Mengubah jabatan menjadi ' . $user->role_label . ', tim menjadi ' . ($user->teams->pluck('name')->implode(', ') ?: '-') . ', dan status akun menjadi ' . ($user->is_active ? 'aktif' : 'nonaktif') . '.',
            $user
        );

        return redirect()
            ->route('users.index')
            ->with('success', 'Data user berhasil diperbarui.');
    }
}
