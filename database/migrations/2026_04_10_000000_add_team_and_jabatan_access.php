<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('teams')) {
            Schema::create('teams', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('users')) {
            DB::table('users')
                ->where('role', 'user')
                ->update(['role' => 'staff']);
        }

        if (Schema::hasTable('users') && Schema::hasTable('teams') && !Schema::hasTable('team_user')) {
            Schema::create('team_user', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('team_id');
                $table->unsignedBigInteger('user_id');
                $table->timestamps();

                $table->unique(['team_id', 'user_id']);
                $table->index('team_id');
                $table->index('user_id');
            });
        }

        if (Schema::hasTable('pekerjaan') && !Schema::hasColumn('pekerjaan', 'team_id')) {
            Schema::table('pekerjaan', function (Blueprint $table) {
                $column = $table->unsignedBigInteger('team_id')->nullable();

                if (Schema::hasColumn('pekerjaan', 'lokasi_id')) {
                    $column->after('lokasi_id');
                } elseif (Schema::hasColumn('pekerjaan', 'user_id')) {
                    $column->after('user_id');
                }

                $table->index('team_id');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('pekerjaan') && Schema::hasColumn('pekerjaan', 'team_id')) {
            Schema::table('pekerjaan', function (Blueprint $table) {
                $table->dropIndex(['team_id']);
                $table->dropColumn('team_id');
            });
        }

        Schema::dropIfExists('team_user');
        Schema::dropIfExists('teams');

        if (Schema::hasTable('users')) {
            DB::table('users')
                ->where('role', 'staff')
                ->update(['role' => 'user']);
        }
    }
};
