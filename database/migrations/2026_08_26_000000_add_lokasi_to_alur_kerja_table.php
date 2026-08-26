<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('alur_kerja') || Schema::hasColumn('alur_kerja', 'lokasi')) {
            return;
        }

        Schema::table('alur_kerja', function (Blueprint $table) {
            $table->string('lokasi')->nullable()->after('deskripsi');
        });
    }

    public function down()
    {
        if (!Schema::hasTable('alur_kerja') || !Schema::hasColumn('alur_kerja', 'lokasi')) {
            return;
        }

        Schema::table('alur_kerja', function (Blueprint $table) {
            $table->dropColumn('lokasi');
        });
    }
};
