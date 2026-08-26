<?php

namespace Tests\Feature;

use App\Models\AlurKerja;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlurKerjaLokasiTest extends TestCase
{
    use RefreshDatabase;

    public function test_lokasi_alur_kerja_bersifat_opsional(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->post(route('alur-kerja.store'), $this->validPayload($admin))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('alur_kerja', [
            'nama' => 'Proses Pengujian Lokasi',
            'lokasi' => null,
        ]);
    }

    public function test_lokasi_alur_kerja_disimpan_dan_ditampilkan(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $payload = $this->validPayload($admin);
        $payload['lokasi'] = '  Kantor Cabang Makassar  ';

        $response = $this->actingAs($admin)
            ->post(route('alur-kerja.store'), $payload);

        $alurKerja = AlurKerja::query()->where('nama', 'Proses Pengujian Lokasi')->firstOrFail();

        $response->assertRedirect(route('alur-kerja.show', $alurKerja));
        $this->assertSame('Kantor Cabang Makassar', $alurKerja->lokasi);

        $this->actingAs($admin)
            ->get(route('alur-kerja.show', $alurKerja))
            ->assertOk()
            ->assertSee('Lokasi pelaksanaan')
            ->assertSee('Kantor Cabang Makassar');
    }

    private function validPayload(User $user): array
    {
        return [
            'nama' => 'Proses Pengujian Lokasi',
            'pemilik_utama_user_id' => $user->id,
            'cadangan_user_ids_present' => '1',
            'risiko' => AlurKerja::RISIKO_SEDANG,
            'status_dokumentasi' => AlurKerja::DOKUMENTASI_BELUM_LENGKAP,
        ];
    }
}
