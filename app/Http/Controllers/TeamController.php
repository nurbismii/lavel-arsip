<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TeamController extends Controller
{
    public function index()
    {
        $teams = Team::withCount(['users', 'pekerjaans'])
            ->orderBy('name')
            ->paginate(10);

        return view('teams.index', compact('teams'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:teams,name'],
        ]);

        $team = Team::create($data);

        ActivityLogService::log(
            'team.create',
            'Menambahkan tim/divisi baru.',
            $team
        );

        return redirect()
            ->route('teams.index')
            ->with('success', 'Tim/divisi berhasil ditambahkan.');
    }

    public function update(Request $request, Team $team)
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('teams', 'name')->ignore($team->id),
            ],
        ]);

        $team->update($data);

        ActivityLogService::log(
            'team.update',
            'Memperbarui tim/divisi.',
            $team
        );

        return redirect()
            ->route('teams.index')
            ->with('success', 'Tim/divisi berhasil diperbarui.');
    }

    public function destroy(Team $team)
    {
        $teamName = $team->name;

        if ($team->pekerjaans()->exists()) {
            return redirect()
                ->route('teams.index')
                ->with('error', 'Tim/divisi tidak bisa dihapus karena masih dipakai pada dokumen.');
        }

        $team->users()->detach();
        $team->delete();

        ActivityLogService::log(
            'team.delete',
            'Menghapus tim/divisi.',
            (object) ['id' => $team->id, 'name' => $teamName]
        );

        return redirect()
            ->route('teams.index')
            ->with('success', 'Tim/divisi berhasil dihapus.');
    }
}
