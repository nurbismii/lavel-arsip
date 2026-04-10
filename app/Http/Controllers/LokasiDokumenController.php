<?php

namespace App\Http\Controllers;

use App\Models\Lokasi;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LokasiDokumenController extends Controller
{
    public function index()
    {
        $lokasis = Lokasi::withCount('pekerjaans')
            ->orderBy('nama_lokasi')
            ->paginate(10);

        return view('lokasi_dokumen.index', compact('lokasis'));
    }

    public function create()
    {
        return view('lokasi_dokumen.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama_lokasi' => ['required', 'string', 'max:255', 'unique:lokasi_dokumen,nama_lokasi'],
        ]);

        $lokasi = Lokasi::create($data);

        ActivityLogService::log(
            'lokasi.create',
            'Menambahkan lokasi dokumen baru.',
            $lokasi
        );

        return redirect()
            ->route('lokasi-dokumen.index')
            ->with('success', 'Lokasi dokumen berhasil ditambahkan.');
    }

    public function edit(Lokasi $lokasi)
    {
        return view('lokasi_dokumen.edit', compact('lokasi'));
    }

    public function update(Request $request, Lokasi $lokasi)
    {
        $data = $request->validate([
            'nama_lokasi' => [
                'required',
                'string',
                'max:255',
                Rule::unique('lokasi_dokumen', 'nama_lokasi')->ignore($lokasi->id),
            ],
        ]);

        $lokasi->update($data);

        ActivityLogService::log(
            'lokasi.update',
            'Memperbarui lokasi dokumen.',
            $lokasi
        );

        return redirect()
            ->route('lokasi-dokumen.index')
            ->with('success', 'Lokasi dokumen berhasil diperbarui.');
    }

    public function destroy(Lokasi $lokasi)
    {
        $namaLokasi = $lokasi->nama_lokasi;

        if ($lokasi->pekerjaans()->exists()) {
            return redirect()
                ->route('lokasi-dokumen.index')
                ->with('error', 'Lokasi tidak bisa dihapus karena masih dipakai pada pekerjaan.');
        }

        $lokasi->delete();

        ActivityLogService::log(
            'lokasi.delete',
            'Menghapus lokasi dokumen.',
            (object) ['id' => $lokasi->id, 'nama_lokasi' => $namaLokasi]
        );

        return redirect()
            ->route('lokasi-dokumen.index')
            ->with('success', 'Lokasi dokumen berhasil dihapus.');
    }
}
