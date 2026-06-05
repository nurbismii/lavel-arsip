<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('alur_kerja_tahap')) {
            return;
        }

        Schema::create('alur_kerja_tahap', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('alur_kerja_id');
            $table->unsignedInteger('urutan')->default(1);
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->text('aplikasi_digunakan')->nullable();
            $table->text('akun_digunakan')->nullable();
            $table->text('pic_terkait')->nullable();
            $table->text('kontak_pic')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index('alur_kerja_id');
            $table->index(['alur_kerja_id', 'urutan']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('alur_kerja_tahap');
    }
};
