<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('pekerjaan')) {
            Schema::create('pekerjaan', function (Blueprint $table) {
                $table->integer('id', true);
                $table->string('judul', 225);
                $table->integer('parent_id')->nullable();
                $table->integer('user_id');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('dokumen')) {
            Schema::create('dokumen', function (Blueprint $table) {
                $table->integer('id', true);
                $table->integer('pekerjaan_id');
                $table->string('nama_file', 225);
                $table->text('path');
                $table->enum('status_dokumen', ['draft', 'aktif', 'arsip'])->default('draft');
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        // These legacy core tables may predate migration history and must never be dropped automatically.
    }
};
