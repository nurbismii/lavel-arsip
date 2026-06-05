<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('alur_kerja_tahap_sistem')) {
            return;
        }

        Schema::create('alur_kerja_tahap_sistem', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('alur_kerja_tahap_id');
            $table->unsignedInteger('urutan')->default(1);
            $table->string('nama_sistem')->nullable();
            $table->text('fungsi')->nullable();
            $table->text('akun')->nullable();
            $table->string('url', 500)->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index('alur_kerja_tahap_id');
            $table->index(['alur_kerja_tahap_id', 'urutan']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('alur_kerja_tahap_sistem');
    }
};
