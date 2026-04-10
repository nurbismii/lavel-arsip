<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('pekerjaan') || Schema::hasColumn('pekerjaan', 'lokasi_id')) {
            return;
        }

        Schema::table('pekerjaan', function (Blueprint $table) {
            $table->unsignedBigInteger('lokasi_id')->nullable()->after('user_id');
            $table->index('lokasi_id');
        });
    }

    public function down()
    {
        if (!Schema::hasTable('pekerjaan') || !Schema::hasColumn('pekerjaan', 'lokasi_id')) {
            return;
        }

        Schema::table('pekerjaan', function (Blueprint $table) {
            $table->dropIndex(['lokasi_id']);
            $table->dropColumn('lokasi_id');
        });
    }
};
