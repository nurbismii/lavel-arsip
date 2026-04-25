<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('pekerjaan')) {
            Schema::table('pekerjaan', function (Blueprint $table) {
                if (!Schema::hasColumn('pekerjaan', 'tanggal_mulai_penyelesaian')) {
                    $column = $table->date('tanggal_mulai_penyelesaian')->nullable();

                    if (Schema::hasColumn('pekerjaan', 'team_id')) {
                        $column->after('team_id');
                    }
                }

                if (!Schema::hasColumn('pekerjaan', 'tanggal_target_penyelesaian')) {
                    $table->date('tanggal_target_penyelesaian')->nullable()->after('tanggal_mulai_penyelesaian');
                    $table->index('tanggal_target_penyelesaian');
                }
            });
        }

        if (Schema::hasTable('dokumen')) {
            Schema::table('dokumen', function (Blueprint $table) {
                if (!Schema::hasColumn('dokumen', 'bukti_penyelesaian_nama_file')) {
                    $table->string('bukti_penyelesaian_nama_file')->nullable()->after('status_dokumen');
                }

                if (!Schema::hasColumn('dokumen', 'bukti_penyelesaian_path')) {
                    $table->string('bukti_penyelesaian_path')->nullable()->after('bukti_penyelesaian_nama_file');
                }

                if (!Schema::hasColumn('dokumen', 'keterangan_penyelesaian')) {
                    $table->text('keterangan_penyelesaian')->nullable()->after('bukti_penyelesaian_path');
                }

                if (!Schema::hasColumn('dokumen', 'diselesaikan_pada')) {
                    $table->timestamp('diselesaikan_pada')->nullable()->after('keterangan_penyelesaian');
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('dokumen')) {
            Schema::table('dokumen', function (Blueprint $table) {
                foreach ([
                    'bukti_penyelesaian_nama_file',
                    'bukti_penyelesaian_path',
                    'keterangan_penyelesaian',
                    'diselesaikan_pada',
                ] as $column) {
                    if (Schema::hasColumn('dokumen', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('pekerjaan')) {
            Schema::table('pekerjaan', function (Blueprint $table) {
                if (Schema::hasColumn('pekerjaan', 'tanggal_target_penyelesaian')) {
                    $table->dropIndex(['tanggal_target_penyelesaian']);
                    $table->dropColumn('tanggal_target_penyelesaian');
                }

                if (Schema::hasColumn('pekerjaan', 'tanggal_mulai_penyelesaian')) {
                    $table->dropColumn('tanggal_mulai_penyelesaian');
                }
            });
        }
    }
};
