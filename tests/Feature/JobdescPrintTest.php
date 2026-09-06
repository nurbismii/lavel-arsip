<?php

namespace Tests\Feature;

use App\Models\Jobdesc;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobdescPrintTest extends TestCase
{
    use RefreshDatabase;

    public function test_print_requires_login_and_document_visibility(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_STAFF]);
        $jobdesc = Jobdesc::create(['jabatan' => 'HR Generalist', 'pemilik_user_id' => $owner->id, 'status' => 'draft']);

        $this->get(route('jobdesc.print', $jobdesc))->assertRedirect(route('login'));
        $stranger = User::factory()->create(['role' => User::ROLE_STAFF]);
        $this->actingAs($stranger)->get(route('jobdesc.print', $jobdesc))->assertForbidden();
        $this->actingAs($owner)->get(route('jobdesc.print', $jobdesc))->assertOk();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin)->get(route('jobdesc.print', $jobdesc))->assertOk();
    }

    public function test_team_member_can_print_shared_document(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_STAFF]);
        $member = User::factory()->create(['role' => User::ROLE_STAFF]);
        $team = Team::create(['name' => 'HRD']);
        $member->teams()->attach($team);
        $jobdesc = Jobdesc::create(['jabatan' => 'HR', 'pemilik_user_id' => $owner->id, 'team_id' => $team->id, 'status' => 'draft']);
        $this->actingAs($member)->get(route('jobdesc.print', $jobdesc))->assertOk();
    }

    public function test_print_preserves_zero_and_escapes_text_and_handles_empty_sections(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_STAFF]);
        $jobdesc = Jobdesc::create([
            'jabatan' => 'HR Generalist', 'pemilik_user_id' => $owner->id, 'status' => 'draft',
            'jumlah_bawahan' => 0, 'ringkasan_jabatan' => '<script>unsafe()</script>',
            'spesifikasi_pekerjaan' => ['pendidikan' => [['jenjang' => 'S1', 'jurusan' => 'Psikologi']]],
        ]);
        $response = $this->actingAs($owner)->get(route('jobdesc.print', $jobdesc));
        $response->assertOk()->assertSee('I. IDENTITAS JABATAN')->assertSee('XI. CATATAN REVISI')
            ->assertSee('Belum ada catatan revisi.')->assertSee('Psikologi')
            ->assertSee('&lt;script&gt;unsafe()&lt;/script&gt;', false)
            ->assertDontSee('<script>unsafe()</script>', false)->assertSee('>0</td>', false);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->actingAs($owner)->get(route('jobdesc.show', $jobdesc))->assertSee(route('jobdesc.print', $jobdesc));
        $this->actingAs($owner)->get(route('jobdesc.index'))->assertSee(route('jobdesc.print', $jobdesc));
    }
}
