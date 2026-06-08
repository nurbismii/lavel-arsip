<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('sop_pengetahuan')) {
            return;
        }

        Schema::create('sop_pengetahuan', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->nullable()->unique();
            $table->string('judul');
            $table->string('jenis', 40)->default('sop');
            $table->text('ringkasan')->nullable();
            $table->longText('konten')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->unsignedBigInteger('alur_kerja_id')->nullable();
            $table->unsignedBigInteger('alur_kerja_tahap_id')->nullable();
            $table->unsignedBigInteger('pemilik_user_id')->nullable();
            $table->string('status', 30)->default('draft');
            $table->string('tingkat_kepentingan', 30)->default('normal');
            $table->date('tanggal_berlaku')->nullable();
            $table->date('target_tinjauan_berikutnya')->nullable();
            $table->string('kata_kunci', 500)->nullable();
            $table->timestamps();

            $table->index('jenis');
            $table->index('team_id');
            $table->index('alur_kerja_id');
            $table->index('alur_kerja_tahap_id');
            $table->index('pemilik_user_id');
            $table->index('status');
            $table->index('tingkat_kepentingan');
            $table->index('target_tinjauan_berikutnya');
        });
    }

    public function down()
    {
        Schema::dropIfExists('sop_pengetahuan');
    }
};
