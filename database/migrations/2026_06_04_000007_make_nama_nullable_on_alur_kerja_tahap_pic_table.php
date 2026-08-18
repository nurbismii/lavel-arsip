<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('alur_kerja_tahap_pic')) {
            return;
        }

        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE `alur_kerja_tahap_pic` MODIFY `nama` varchar(255) NULL');
    }

    public function down()
    {
        if (!Schema::hasTable('alur_kerja_tahap_pic')) {
            return;
        }

        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::table('alur_kerja_tahap_pic')
            ->whereNull('nama')
            ->update(['nama' => 'PIC terkait']);

        DB::statement('ALTER TABLE `alur_kerja_tahap_pic` MODIFY `nama` varchar(255) NOT NULL');
    }
};
