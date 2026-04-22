<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            // Добавляем поле для чистой выручки (с копейками, поэтому decimal)
            $table->decimal('earned_value', 12, 2)->default(0)->after('payed_money');
        });
    }

    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->dropColumn('earned_value');
        });
    }
};