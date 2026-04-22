<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webinars', function (Blueprint $table) {
            $table->string('cohort')->nullable()->after('room_id')->comment('Привязка к потоку/запуску');
        });
    }

    public function down(): void
    {
        Schema::table('webinars', function (Blueprint $table) {
            $table->dropColumn('cohort');
        });
    }
};
