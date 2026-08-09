<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('pekerjaan') || Schema::hasColumn('pekerjaan', 'deskripsi')) {
            return;
        }

        Schema::table('pekerjaan', function (Blueprint $table) {
            $table->text('deskripsi')->nullable()->after('judul');
        });
    }

    public function down()
    {
        if (!Schema::hasTable('pekerjaan') || !Schema::hasColumn('pekerjaan', 'deskripsi')) {
            return;
        }

        Schema::table('pekerjaan', function (Blueprint $table) {
            $table->dropColumn('deskripsi');
        });
    }
};
