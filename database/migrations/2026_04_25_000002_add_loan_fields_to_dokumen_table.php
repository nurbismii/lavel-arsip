<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('dokumen')) {
            return;
        }

        Schema::table('dokumen', function (Blueprint $table) {
            if (!Schema::hasColumn('dokumen', 'peminjam_user_id')) {
                $table->unsignedBigInteger('peminjam_user_id')->nullable()->after('status_dokumen');
                $table->index('peminjam_user_id');
            }

            if (!Schema::hasColumn('dokumen', 'dipinjam_pada')) {
                $table->timestamp('dipinjam_pada')->nullable()->after('peminjam_user_id');
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('dokumen')) {
            return;
        }

        Schema::table('dokumen', function (Blueprint $table) {
            if (Schema::hasColumn('dokumen', 'peminjam_user_id')) {
                $table->dropIndex(['peminjam_user_id']);
                $table->dropColumn('peminjam_user_id');
            }

            if (Schema::hasColumn('dokumen', 'dipinjam_pada')) {
                $table->dropColumn('dipinjam_pada');
            }
        });
    }
};
