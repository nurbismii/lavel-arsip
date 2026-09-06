<?php

namespace App\Http\Controllers;

use App\Models\Jobdesc;
use App\Models\Team;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class JobdescController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $status = $request->input('status');
        $query = Jobdesc::visibleTo(auth()->user())->with(['team', 'pemilik'])->latest();
        if ($search !== '') $query->where(function ($q) use ($search) {
            $q->where('jabatan', 'like', "%{$search}%")->orWhere('job_code', 'like', "%{$search}%")->orWhere('departemen', 'like', "%{$search}%")->orWhere('divisi', 'like', "%{$search}%");
        });
        if (array_key_exists($status, Jobdesc::statusOptions())) $query->where('status', $status);
        return view('jobdesc.index', ['jobdescs' => $query->paginate(12)->withQueryString(), 'search' => $search, 'status' => $status, 'statusOptions' => Jobdesc::statusOptions()]);
    }

    public function create() { return view('jobdesc.create', $this->formData()); }
    public function store(Request $request)
    {
        $jobdesc = Jobdesc::create($this->validatedData($request));
        ActivityLogService::log('jobdesc.create', 'Menambahkan uraian jabatan.', $jobdesc);
        return redirect()->route('jobdesc.show', $jobdesc)->with('success', 'Uraian jabatan berhasil ditambahkan.');
    }
    public function show(Jobdesc $jobdesc)
    {
        $this->authorizeView($jobdesc);
        return view('jobdesc.show', ['jobdesc' => $jobdesc->load(['team', 'pemilik']), 'canManage' => $this->canManage($jobdesc)]);
    }
    public function print(Jobdesc $jobdesc)
    {
        $this->authorizeView($jobdesc);

        return response()->view('jobdesc.print', compact('jobdesc'))
            ->header('Cache-Control', 'private, no-store');
    }

    public function edit(Jobdesc $jobdesc)
    {
        $this->authorizeManage($jobdesc);
        return view('jobdesc.edit', array_merge($this->formData(), compact('jobdesc')));
    }
    public function update(Request $request, Jobdesc $jobdesc)
    {
        $this->authorizeManage($jobdesc);
        $jobdesc->update($this->validatedData($request, $jobdesc));
        ActivityLogService::log('jobdesc.update', 'Memperbarui uraian jabatan.', $jobdesc);
        return redirect()->route('jobdesc.show', $jobdesc)->with('success', 'Uraian jabatan berhasil diperbarui.');
    }
    public function destroy(Jobdesc $jobdesc)
    {
        $this->authorizeManage($jobdesc);
        if ($jobdesc->bagan_struktur_path) Storage::disk('public')->delete($jobdesc->bagan_struktur_path);
        $name = $jobdesc->jabatan; $jobdesc->delete();
        ActivityLogService::log('jobdesc.delete', 'Menghapus uraian jabatan.', (object) ['id' => $jobdesc->id, 'judul' => $name]);
        return redirect()->route('jobdesc.index')->with('success', 'Uraian jabatan berhasil dihapus.');
    }

    private function formData(): array
    {
        $user = auth()->user();
        $teams = $user->canAccessAllFiles() ? Team::orderBy('name')->get() : Team::whereIn('id', $user->assignedTeamIds())->orderBy('name')->get();
        return ['teams' => $teams, 'users' => $user->canAccessAllFiles() ? User::where('is_active', true)->orderBy('name')->get() : collect([$user]), 'statusOptions' => Jobdesc::statusOptions()];
    }

    private function validatedData(Request $request, ?Jobdesc $jobdesc = null): array
    {
        $data = $request->validate([
            'jabatan' => ['required', 'string', 'max:200'], 'job_code' => ['nullable', 'string', 'max:100', Rule::unique('jobdescs', 'job_code')->ignore($jobdesc)],
            'golongan_level' => ['nullable', 'string', 'max:150'], 'divisi' => ['nullable', 'string', 'max:200'], 'departemen' => ['nullable', 'string', 'max:200'], 'area' => ['nullable', 'string', 'max:200'],
            'atasan_langsung' => ['nullable', 'string', 'max:2000'], 'bawahan_langsung' => ['nullable', 'string', 'max:2000'], 'jumlah_bawahan' => ['nullable', 'integer', 'min:0', 'max:9999'], 'ringkasan_jabatan' => ['nullable', 'string', 'max:2000'],
            'bagan_struktur' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048'], 'struktur_organisasi' => ['nullable', 'array'], 'struktur_organisasi.*.atasan' => ['nullable', 'string', 'max:200'], 'struktur_organisasi.*.jumlah_atasan' => ['nullable', 'integer', 'min:0', 'max:9999'], 'struktur_organisasi.*.bawahan' => ['nullable', 'string', 'max:200'], 'struktur_organisasi.*.jumlah_bawahan' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'tugas_pokok' => ['nullable', 'array'], 'tugas_pokok.*.nama' => ['nullable', 'string', 'max:200'], 'tugas_pokok.*.rincian' => ['nullable', 'string', 'max:2000'],
            'tugas_tambahan' => ['nullable', 'string', 'max:2000'], 'output_pekerjaan' => ['nullable', 'string', 'max:2000'], 'hak' => ['nullable', 'string', 'max:2000'], 'kewajiban' => ['nullable', 'string', 'max:2000'], 'wewenang' => ['nullable', 'string', 'max:2000'],
            'hubungan_kerja' => ['nullable', 'array'], 'hubungan_kerja.*' => ['nullable', 'string', 'max:2000'], 'lingkungan_kerja' => ['nullable', 'string', 'max:2000'],
            'spesifikasi_pekerjaan' => ['nullable', 'array'], 'spesifikasi_pekerjaan.umur' => ['nullable', 'string', 'max:20'], 'spesifikasi_pekerjaan.jenis_kelamin' => ['nullable', 'array'], 'spesifikasi_pekerjaan.jenis_kelamin.*' => ['string', Rule::in(['Laki-laki', 'Perempuan'])], 'spesifikasi_pekerjaan.pendidikan' => ['nullable', 'array'], 'spesifikasi_pekerjaan.pendidikan.*.jenjang' => ['nullable', 'string', 'max:50'], 'spesifikasi_pekerjaan.pendidikan.*.jurusan' => ['nullable', 'string', 'max:200'], 'spesifikasi_pekerjaan.pengalaman' => ['nullable', 'array'], 'spesifikasi_pekerjaan.pengalaman.*' => ['string', 'max:50'], 'spesifikasi_pekerjaan.kompetensi_teknis' => ['nullable', 'string', 'max:2000'], 'spesifikasi_pekerjaan.kompetensi_manajerial' => ['nullable', 'string', 'max:2000'],
            'catatan_revisi' => ['nullable', 'array'], 'catatan_revisi.*.nomor' => ['nullable', 'string', 'max:3'], 'catatan_revisi.*.tanggal' => ['nullable', 'date'], 'catatan_revisi.*.deskripsi' => ['nullable', 'string', 'max:2000'], 'catatan_revisi.*.alasan' => ['nullable', 'string', 'max:2000'], 'catatan_revisi.*.pihak' => ['nullable', 'string', 'max:200'],
            'team_id' => ['nullable', 'integer', 'exists:teams,id'], 'pemilik_user_id' => ['required', 'integer', 'exists:users,id'], 'status' => ['required', Rule::in(array_keys(Jobdesc::statusOptions()))], 'kata_kunci' => ['nullable', 'string', 'max:500'],
        ]);
        foreach (['struktur_organisasi', 'tugas_pokok', 'catatan_revisi'] as $key) $data[$key] = $this->removeEmptyRows($data[$key] ?? []);
        $data['hubungan_kerja'] = array_filter((array) ($data['hubungan_kerja'] ?? []), function ($value) {
            return filled($value);
        });
        $spec = $data['spesifikasi_pekerjaan'] ?? []; $spec['pendidikan'] = $this->removeEmptyRows($spec['pendidikan'] ?? []); $data['spesifikasi_pekerjaan'] = array_filter($spec, fn ($value) => $value !== null && $value !== [] && $value !== '');
        if ($request->hasFile('bagan_struktur')) {
            if ($jobdesc && $jobdesc->bagan_struktur_path) Storage::disk('public')->delete($jobdesc->bagan_struktur_path);
            $data['bagan_struktur_path'] = $request->file('bagan_struktur')->store('jobdesc/struktur', 'public');
        }
        if (!auth()->user()->canAccessAllFiles()) { $data['pemilik_user_id'] = auth()->id(); if ($data['team_id'] && !in_array((int) $data['team_id'], auth()->user()->assignedTeamIds(), true)) abort(403); }
        return $data;
    }
    private function removeEmptyRows(array $rows): array { return array_values(array_filter($rows, fn ($row) => is_array($row) && collect($row)->filter(fn ($v) => is_array($v) ? !empty($v) : filled($v))->isNotEmpty())); }
    private function authorizeView(Jobdesc $jobdesc): void { abort_unless(Jobdesc::visibleTo(auth()->user())->whereKey($jobdesc->id)->exists(), 403, 'Anda tidak memiliki izin melihat uraian jabatan ini.'); }
    private function authorizeManage(Jobdesc $jobdesc): void { abort_unless($this->canManage($jobdesc), 403, 'Anda tidak memiliki izin mengubah uraian jabatan ini.'); }
    private function canManage(Jobdesc $jobdesc): bool { return auth()->user()->canAccessAllFiles() || $jobdesc->pemilik_user_id === auth()->id(); }
}
