<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('jobdescs', function (Blueprint $table) {
            $table->id();
            $table->string('jabatan');
            $table->string('job_code', 100)->nullable()->unique();
            $table->string('golongan_level', 150)->nullable();
            $table->string('divisi', 200)->nullable();
            $table->string('departemen', 200)->nullable();
            $table->string('area', 200)->nullable();
            $table->text('atasan_langsung')->nullable();
            $table->text('bawahan_langsung')->nullable();
            $table->unsignedInteger('jumlah_bawahan')->nullable();
            $table->text('ringkasan_jabatan')->nullable();
            $table->string('bagan_struktur_path')->nullable();
            $table->json('struktur_organisasi')->nullable();
            $table->json('tugas_pokok')->nullable();
            $table->text('tugas_tambahan')->nullable();
            $table->text('output_pekerjaan')->nullable();
            $table->text('hak')->nullable();
            $table->text('kewajiban')->nullable();
            $table->text('wewenang')->nullable();
            $table->json('hubungan_kerja')->nullable();
            $table->text('lingkungan_kerja')->nullable();
            $table->json('spesifikasi_pekerjaan')->nullable();
            $table->json('catatan_revisi')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->unsignedBigInteger('pemilik_user_id');
            $table->string('status', 30)->default('draft');
            $table->string('kata_kunci', 500)->nullable();
            $table->timestamps();

            $table->index(['team_id', 'status']);
            $table->index('pemilik_user_id');
            $table->index('jabatan');
        });
    }

    public function down()
    {
        Schema::dropIfExists('jobdescs');
    }
};
