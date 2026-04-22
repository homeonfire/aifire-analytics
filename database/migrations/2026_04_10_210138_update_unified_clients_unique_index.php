<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unified_clients', function (Blueprint $table) {
            // 1. Удаляем старое глобальное ограничение
            $table->dropUnique('unified_clients_email_unique');

            // Если у тебя была такая же жесткая проверка для телефона, раскомментируй строку ниже:
            // $table->dropUnique('unified_clients_phone_unique');

            // 2. Создаем новое умное правило: (Школа + Email) не могут повторяться
            $table->unique(['school_id', 'email'], 'client_school_email_unique');
        });
    }

    public function down(): void
    {
        Schema::table('unified_clients', function (Blueprint $table) {
            $table->dropUnique('client_school_email_unique');
            $table->unique('email', 'unified_clients_email_unique');
        });
    }
};