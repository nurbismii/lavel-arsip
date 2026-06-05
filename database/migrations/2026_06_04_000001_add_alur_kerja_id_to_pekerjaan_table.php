<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('pekerjaan') || Schema::hasColumn('pekerjaan', 'alur_kerja_id')) {
            return;
        }

        Schema::table('pekerjaan', function (Blueprint $table) {
            $table->unsignedBigInteger('alur_kerja_id')->nullable()->after('team_id');
            $table->index('alur_kerja_id');
        });
    }

    public function down()
    {
        if (!Schema::hasTable('pekerjaan') || !Schema::hasColumn('pekerjaan', 'alur_kerja_id')) {
            return;
        }

        Schema::table('pekerjaan', function (Blueprint $table) {
            $table->dropIndex(['alur_kerja_id']);
            $table->dropColumn('alur_kerja_id');
        });
    }
};
