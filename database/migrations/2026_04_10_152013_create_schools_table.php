<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Таблица школ
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Название школы
            $table->uuid('uuid')->unique(); // Тот самый UUID для безопасных вебхуков
            $table->timestamps();
        });

        // 2. Сводная таблица (Пользователь <-> Школа)
        Schema::create('school_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // В будущем сюда можно добавить поле 'role' (owner, manager и т.д.)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_user');
        Schema::dropIfExists('schools');
    }
};