<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Создаем таблицу менеджеров
        Schema::create('managers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        // 2. Добавляем привязку менеджера к таблице заказов (deals)
        Schema::table('deals', function (Blueprint $table) {
            $table->unsignedBigInteger('manager_id')->nullable()->after('manager_name');
            // Если менеджера удалят, в заказе просто останется null
            $table->foreign('manager_id')->references('id')->on('managers')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->dropForeign(['manager_id']);
            $table->dropColumn('manager_id');
        });

        Schema::dropIfExists('managers');
    }
};