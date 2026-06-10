<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('alur_kerja') || Schema::hasColumn('alur_kerja', 'estimasi')) {
            return;
        }

        $hasTargetTinjauanColumn = Schema::hasColumn('alur_kerja', 'target_tinjauan_berikutnya');

        Schema::table('alur_kerja', function (Blueprint $table) use ($hasTargetTinjauanColumn) {
            $column = $table->string('estimasi', 100)->nullable();

            if ($hasTargetTinjauanColumn) {
                $column->after('target_tinjauan_berikutnya');
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('alur_kerja') || !Schema::hasColumn('alur_kerja', 'estimasi')) {
            return;
        }

        Schema::table('alur_kerja', function (Blueprint $table) {
            $table->dropColumn('estimasi');
        });
    }
};
