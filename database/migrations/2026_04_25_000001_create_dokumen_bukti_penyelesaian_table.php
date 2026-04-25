<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('dokumen_bukti_penyelesaian')) {
            Schema::create('dokumen_bukti_penyelesaian', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('dokumen_id');
                $table->string('nama_file');
                $table->string('path');
                $table->timestamps();

                $table->index('dokumen_id');
            });
        }

        if (
            Schema::hasTable('dokumen')
            && Schema::hasColumn('dokumen', 'bukti_penyelesaian_path')
            && Schema::hasColumn('dokumen', 'bukti_penyelesaian_nama_file')
        ) {
            DB::table('dokumen')
                ->whereNotNull('bukti_penyelesaian_path')
                ->orderBy('id')
                ->get()
                ->each(function ($dokumen) {
                    $exists = DB::table('dokumen_bukti_penyelesaian')
                        ->where('dokumen_id', $dokumen->id)
                        ->where('path', $dokumen->bukti_penyelesaian_path)
                        ->exists();

                    if ($exists) {
                        return;
                    }

                    DB::table('dokumen_bukti_penyelesaian')->insert([
                        'dokumen_id' => $dokumen->id,
                        'nama_file' => $dokumen->bukti_penyelesaian_nama_file ?: basename($dokumen->bukti_penyelesaian_path),
                        'path' => $dokumen->bukti_penyelesaian_path,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                });
        }
    }

    public function down()
    {
        Schema::dropIfExists('dokumen_bukti_penyelesaian');
    }
};
