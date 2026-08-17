<?php

namespace Tests\Feature;

use App\Models\Dokumen;
use App\Models\Pekerjaan;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PekerjaanRichTextControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => false,
            'filesystems.disks.r2' => [
                'driver' => 'local',
                'root' => storage_path('framework/testing/disks/r2'),
                'bucket' => 'testing',
                'key' => 'testing',
                'secret' => 'testing',
                'endpoint' => 'http://localhost',
            ],
        ]);

        DB::purge('sqlite');
        $this->createDatabaseSchema();
    }

    public function test_store_sanitizes_main_and_sub_document_descriptions(): void
    {
        $user = $this->createUser();
        $locationId = DB::table('lokasi_dokumen')->insertGetId([
            'nama_lokasi' => 'Lemari A',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->post(route('pekerjaan.store'), [
            'judul' => 'Dokumen Utama',
            'deskripsi' => '<p onclick="alert(1)"><strong>Kronologi utama</strong></p><script>alert(2)</script>',
            'lokasi_id' => $locationId,
            'tanggal_mulai_penyelesaian' => '2026-08-17',
            'tanggal_target_penyelesaian' => '2026-08-18',
            'sub_judul' => ['Sub Dokumen'],
            'sub_deskripsi' => [0 => '<p><em>Kronologi sub</em><img src="x" onerror="alert(3)"></p>'],
        ]);

        $response->assertRedirect(route('pekerjaan.index'));
        $main = Pekerjaan::whereNull('parent_id')->firstOrFail();
        $sub = Pekerjaan::where('parent_id', $main->id)->firstOrFail();

        $this->assertSame('<p><strong>Kronologi utama</strong></p>', $main->deskripsi);
        $this->assertSame('<p><em>Kronologi sub</em></p>', $sub->deskripsi);
    }

    public function test_completed_status_rejects_semantically_empty_note(): void
    {
        list($user, $pekerjaan, $dokumen) = $this->createDocumentRecords();

        $response = $this->actingAs($user)->from(route('pekerjaan.index'))->patch(
            route('pekerjaan.dokumen.status', [$pekerjaan->id, $dokumen->id]),
            [
                'status_dokumen' => Dokumen::STATUS_ARSIP,
                'keterangan_penyelesaian' => '<p><br></p>',
            ]
        );

        $response->assertRedirect(route('pekerjaan.index'));
        $response->assertSessionHasErrors('keterangan_penyelesaian');
        $this->assertSame(Dokumen::STATUS_DRAFT, $dokumen->fresh()->status_dokumen);
    }

    public function test_completed_status_saves_only_sanitized_note_markup(): void
    {
        list($user, $pekerjaan, $dokumen) = $this->createDocumentRecords();

        $response = $this->actingAs($user)->patch(
            route('pekerjaan.dokumen.status', [$pekerjaan->id, $dokumen->id]),
            [
                'status_dokumen' => Dokumen::STATUS_ARSIP,
                'keterangan_penyelesaian' => '<p onclick="alert(1)"><strong>Selesai diverifikasi</strong></p>'
                    . '<script>alert(2)</script>',
            ]
        );

        $response->assertSessionHasNoErrors();
        $dokumen->refresh();

        $this->assertSame(Dokumen::STATUS_ARSIP, $dokumen->status_dokumen);
        $this->assertSame('<p><strong>Selesai diverifikasi</strong></p>', $dokumen->keterangan_penyelesaian);
    }

    public function test_non_completed_status_does_not_require_completion_note(): void
    {
        list($user, $pekerjaan, $dokumen) = $this->createDocumentRecords();
        $borrower = $this->createUser('borrower@example.test');

        $response = $this->actingAs($user)->patch(
            route('pekerjaan.dokumen.status', [$pekerjaan->id, $dokumen->id]),
            [
                'status_dokumen' => Dokumen::STATUS_AKTIF,
                'peminjam_user_id' => $borrower->id,
                'keterangan_penyelesaian' => '',
            ]
        );

        $response->assertSessionHasNoErrors();
        $this->assertSame(Dokumen::STATUS_AKTIF, $dokumen->fresh()->status_dokumen);
    }

    public function test_store_rejects_main_document_larger_than_ten_megabytes(): void
    {
        $user = $this->createUser();
        $locationId = DB::table('lokasi_dokumen')->insertGetId([
            'nama_lokasi' => 'Lemari A',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->from(route('pekerjaan.create'))->post(route('pekerjaan.store'), [
            'judul' => 'Dokumen Besar',
            'lokasi_id' => $locationId,
            'tanggal_mulai_penyelesaian' => '2026-08-17',
            'tanggal_target_penyelesaian' => '2026-08-18',
            'dokumen' => [UploadedFile::fake()->create('dokumen-besar.pdf', 10241, 'application/pdf')],
        ]);

        $response->assertRedirect(route('pekerjaan.create'));
        $response->assertSessionHasErrors('dokumen.0');
        $this->assertDatabaseCount('pekerjaan', 0);
    }

    public function test_store_accepts_main_document_at_exactly_ten_megabytes(): void
    {
        $user = $this->createUser();
        $locationId = DB::table('lokasi_dokumen')->insertGetId([
            'nama_lokasi' => 'Lemari A',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->post(route('pekerjaan.store'), [
            'judul' => 'Dokumen Sepuluh MB',
            'lokasi_id' => $locationId,
            'tanggal_mulai_penyelesaian' => '2026-08-17',
            'tanggal_target_penyelesaian' => '2026-08-18',
            'dokumen' => [UploadedFile::fake()->create('dokumen-10mb.pdf', 10240, 'application/pdf')],
        ]);

        $response->assertRedirect(route('pekerjaan.index'));
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('pekerjaan', 1);
        $this->assertDatabaseCount('dokumen', 1);
    }

    public function test_completed_status_rejects_proof_larger_than_ten_megabytes(): void
    {
        list($user, $pekerjaan, $dokumen) = $this->createDocumentRecords();

        $response = $this->actingAs($user)->from(route('pekerjaan.index'))->patch(
            route('pekerjaan.dokumen.status', [$pekerjaan->id, $dokumen->id]),
            [
                'status_dokumen' => Dokumen::STATUS_ARSIP,
                'keterangan_penyelesaian' => '<p>Dokumen selesai diverifikasi.</p>',
                'bukti_penyelesaian' => [
                    UploadedFile::fake()->create('bukti-besar.pdf', 10241, 'application/pdf'),
                ],
            ]
        );

        $response->assertRedirect(route('pekerjaan.index'));
        $response->assertSessionHasErrors('bukti_penyelesaian.0');
        $this->assertSame(Dokumen::STATUS_DRAFT, $dokumen->fresh()->status_dokumen);
    }

    private function createDocumentRecords(): array
    {
        $user = $this->createUser();
        $pekerjaan = Pekerjaan::create([
            'judul' => 'Dokumen Uji',
            'user_id' => $user->id,
            'tanggal_mulai_penyelesaian' => '2026-08-17',
            'tanggal_target_penyelesaian' => '2026-08-18',
        ]);
        $dokumen = Dokumen::create([
            'pekerjaan_id' => $pekerjaan->id,
            'nama_file' => 'dokumen.pdf',
            'path' => 'dokumen/dokumen.pdf',
            'status_dokumen' => Dokumen::STATUS_DRAFT,
            'bukti_penyelesaian_nama_file' => 'bukti.pdf',
            'bukti_penyelesaian_path' => 'dokumen/bukti.pdf',
        ]);

        return [$user, $pekerjaan, $dokumen];
    }

    private function createUser(string $email = 'admin@example.test'): User
    {
        return User::create([
            'name' => 'Admin Uji',
            'email' => $email,
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);
    }

    private function createDatabaseSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('staff');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('lokasi_dokumen', function (Blueprint $table) {
            $table->id();
            $table->string('nama_lokasi');
            $table->timestamps();
        });

        Schema::create('pekerjaan', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('lokasi_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->unsignedBigInteger('alur_kerja_id')->nullable();
            $table->unsignedBigInteger('alur_kerja_tahap_id')->nullable();
            $table->date('tanggal_mulai_penyelesaian')->nullable();
            $table->date('tanggal_target_penyelesaian')->nullable();
            $table->timestamps();
        });

        Schema::create('dokumen', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pekerjaan_id');
            $table->string('nama_file');
            $table->string('path');
            $table->string('status_dokumen')->default(Dokumen::STATUS_DRAFT);
            $table->unsignedBigInteger('peminjam_user_id')->nullable();
            $table->timestamp('dipinjam_pada')->nullable();
            $table->string('bukti_penyelesaian_nama_file')->nullable();
            $table->string('bukti_penyelesaian_path')->nullable();
            $table->text('keterangan_penyelesaian')->nullable();
            $table->timestamp('diselesaikan_pada')->nullable();
            $table->timestamps();
        });

        Schema::create('dokumen_bukti_penyelesaian', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dokumen_id');
            $table->string('nama_file');
            $table->string('path');
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action');
            $table->text('description');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_name')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }
}
