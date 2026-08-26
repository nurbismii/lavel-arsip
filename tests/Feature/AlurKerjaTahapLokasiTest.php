<?php

namespace Tests\Feature;

use App\Models\AlurKerja;
use App\Models\AlurKerjaTahap;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlurKerjaTahapLokasiTest extends TestCase
{
    use RefreshDatabase;

    public function test_lokasi_setiap_tahap_bersifat_opsional_dan_disimpan_per_baris(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $payload = $this->validWorkflowPayload($admin);
        $payload['tahap'] = [
            ['urutan' => 1, 'nama' => 'Verifikasi Online', 'lokasi' => '  Remote  '],
            ['urutan' => 2, 'nama' => 'Pemeriksaan Fisik'],
        ];

        $this->actingAs($admin)
            ->post(route('alur-kerja.store'), $payload)
            ->assertSessionHasNoErrors();

        $alurKerja = AlurKerja::query()->where('nama', 'Proses Pengujian Lokasi Tahap')->firstOrFail();

        $this->assertDatabaseHas('alur_kerja_tahap', [
            'alur_kerja_id' => $alurKerja->id,
            'nama' => 'Verifikasi Online',
            'lokasi' => 'Remote',
        ]);
        $this->assertDatabaseHas('alur_kerja_tahap', [
            'alur_kerja_id' => $alurKerja->id,
            'nama' => 'Pemeriksaan Fisik',
            'lokasi' => null,
        ]);
    }

    public function test_lokasi_tahap_dapat_diperbarui_dan_ditampilkan(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $alurKerja = AlurKerja::create([
            'nama' => 'Alur Kerja Edit Tahap',
            'pemilik_utama_user_id' => $admin->id,
        ]);
        $tahap = AlurKerjaTahap::create([
            'alur_kerja_id' => $alurKerja->id,
            'urutan' => 1,
            'nama' => 'Tahap Awal',
        ]);

        $this->actingAs($admin)
            ->patch(route('alur-kerja.tahap.update', [$alurKerja, $tahap]), [
                'urutan' => 1,
                'nama' => 'Tahap Awal',
                'lokasi' => '  Gudang A  ',
            ])
            ->assertRedirect(route('alur-kerja.show', $alurKerja));

        $this->assertSame('Gudang A', $tahap->fresh()->lokasi);

        $this->actingAs($admin)
            ->get(route('alur-kerja.show', $alurKerja))
            ->assertOk()
            ->assertSee('Lokasi: Gudang A');
    }

    private function validWorkflowPayload(User $user): array
    {
        return [
            'nama' => 'Proses Pengujian Lokasi Tahap',
            'pemilik_utama_user_id' => $user->id,
            'cadangan_user_ids_present' => '1',
            'risiko' => AlurKerja::RISIKO_SEDANG,
            'status_dokumentasi' => AlurKerja::DOKUMENTASI_BELUM_LENGKAP,
        ];
    }
}
