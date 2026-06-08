<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('sop_pengetahuan_lampiran')) {
            return;
        }

        Schema::create('sop_pengetahuan_lampiran', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sop_pengetahuan_id');
            $table->string('nama_file');
            $table->string('path');
            $table->string('storage_disk', 30)->default('local');
            $table->unsignedBigInteger('ukuran_file')->nullable();
            $table->string('mime_type')->nullable();
            $table->timestamps();

            $table->index('sop_pengetahuan_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('sop_pengetahuan_lampiran');
    }
};
