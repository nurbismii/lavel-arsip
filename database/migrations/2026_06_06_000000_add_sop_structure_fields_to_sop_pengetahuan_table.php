<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('sop_pengetahuan')) {
            return;
        }

        Schema::table('sop_pengetahuan', function (Blueprint $table) {
            if (!Schema::hasColumn('sop_pengetahuan', 'nomor_revisi')) {
                $table->string('nomor_revisi', 50)->nullable()->after('kode');
            }

            if (!Schema::hasColumn('sop_pengetahuan', 'tujuan')) {
                $table->text('tujuan')->nullable()->after('ringkasan');
            }

            if (!Schema::hasColumn('sop_pengetahuan', 'ruang_lingkup')) {
                $table->text('ruang_lingkup')->nullable()->after('tujuan');
            }

            if (!Schema::hasColumn('sop_pengetahuan', 'definisi')) {
                $table->longText('definisi')->nullable()->after('ruang_lingkup');
            }

            if (!Schema::hasColumn('sop_pengetahuan', 'prosedur')) {
                $table->longText('prosedur')->nullable()->after('definisi');
            }

            if (!Schema::hasColumn('sop_pengetahuan', 'daftar_lampiran')) {
                $table->longText('daftar_lampiran')->nullable()->after('prosedur');
            }

            if (!Schema::hasColumn('sop_pengetahuan', 'catatan_revisi')) {
                $table->longText('catatan_revisi')->nullable()->after('daftar_lampiran');
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('sop_pengetahuan')) {
            return;
        }

        Schema::table('sop_pengetahuan', function (Blueprint $table) {
            foreach ([
                'catatan_revisi',
                'daftar_lampiran',
                'prosedur',
                'definisi',
                'ruang_lingkup',
                'tujuan',
                'nomor_revisi',
            ] as $column) {
                if (Schema::hasColumn('sop_pengetahuan', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
