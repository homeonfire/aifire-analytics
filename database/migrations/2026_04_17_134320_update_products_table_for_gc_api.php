<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Добавляем идентификатор предложения из ГетКурса
            $table->unsignedBigInteger('getcourse_id')->nullable()->after('id');

            // Индексируем его для быстрого поиска
            $table->index('getcourse_id');

            // Снимаем уникальность с поля title во избежание конфликтов
            $table->dropUnique('products_title_unique');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['getcourse_id']);
            $table->dropColumn('getcourse_id');
            $table->unique('title', 'products_title_unique');
        });
    }
};