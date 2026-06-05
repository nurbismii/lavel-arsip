<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('alur_kerja')) {
            return;
        }

        Schema::create('alur_kerja', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->nullable()->unique();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->unsignedBigInteger('pemilik_utama_user_id')->nullable();
            $table->unsignedBigInteger('pemilik_cadangan_user_id')->nullable();
            $table->string('risiko', 30)->default('sedang');
            $table->string('status_dokumentasi', 40)->default('belum_lengkap');
            $table->string('status_operasional', 30)->default('aktif');
            $table->date('target_tinjauan_berikutnya')->nullable();
            $table->timestamps();

            $table->index('team_id');
            $table->index('pemilik_utama_user_id');
            $table->index('pemilik_cadangan_user_id');
            $table->index('risiko');
            $table->index('status_dokumentasi');
            $table->index('status_operasional');
        });
    }

    public function down()
    {
        Schema::dropIfExists('alur_kerja');
    }
};
