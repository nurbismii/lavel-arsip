<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('alur_kerja_pemilik_cadangan')) {
            Schema::create('alur_kerja_pemilik_cadangan', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('alur_kerja_id');
                $table->unsignedBigInteger('user_id');
                $table->timestamps();

                $table->unique(['alur_kerja_id', 'user_id'], 'alur_kerja_cadangan_unique');
                $table->index('alur_kerja_id');
                $table->index('user_id', 'alur_kerja_cadangan_user_id_index');
            });
        }

        if (!Schema::hasTable('alur_kerja') || !Schema::hasColumn('alur_kerja', 'pemilik_cadangan_user_id')) {
            return;
        }

        $now = now();

        DB::table('alur_kerja')
            ->whereNotNull('pemilik_cadangan_user_id')
            ->orderBy('id')
            ->get(['id', 'pemilik_cadangan_user_id'])
            ->each(function ($alurKerja) use ($now) {
                DB::table('alur_kerja_pemilik_cadangan')->updateOrInsert(
                    [
                        'alur_kerja_id' => $alurKerja->id,
                        'user_id' => $alurKerja->pemilik_cadangan_user_id,
                    ],
                    [
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            });
    }

    public function down()
    {
        if (!Schema::hasTable('alur_kerja_pemilik_cadangan')) {
            return;
        }

        if (Schema::hasTable('alur_kerja') && Schema::hasColumn('alur_kerja', 'pemilik_cadangan_user_id')) {
            DB::table('alur_kerja_pemilik_cadangan')
                ->select('alur_kerja_id', DB::raw('MIN(user_id) as user_id'))
                ->groupBy('alur_kerja_id')
                ->get()
                ->each(function ($cadangan) {
                    DB::table('alur_kerja')
                        ->where('id', $cadangan->alur_kerja_id)
                        ->update(['pemilik_cadangan_user_id' => $cadangan->user_id]);
                });
        }

        Schema::dropIfExists('alur_kerja_pemilik_cadangan');
    }
};
