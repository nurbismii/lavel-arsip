<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('dokumen') && DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE dokumen MODIFY status_dokumen VARCHAR(40) NOT NULL DEFAULT 'draft'");
        }
    }

    public function down()
    {
        if (Schema::hasTable('dokumen') && DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE dokumen MODIFY status_dokumen ENUM('draft', 'aktif', 'arsip') NOT NULL DEFAULT 'draft'");
        }
    }
};
