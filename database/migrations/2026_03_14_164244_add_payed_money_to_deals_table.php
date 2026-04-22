<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            // Добавляем поле для оплаченной суммы сразу после общей стоимости
            $table->decimal('payed_money', 12, 2)->default(0)->after('cost');
        });
    }

    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->dropColumn('payed_money');
        });
    }
};
