<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Таблица посещения
        Schema::create('webinar_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webinar_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unified_client_id')->constrained()->cascadeOnDelete();

            $table->string('city')->nullable(); // Город
            $table->string('device')->nullable(); // ПК или моб
            $table->boolean('clicked_button')->default(false); // Нажали на кнопку
            $table->boolean('clicked_banner')->default(false); // Нажали на баннер

            $table->integer('total_minutes')->default(0);
            $table->timestamps();
        });

        // Таблица интервалов (каждый вход и выход отдельно)
        Schema::create('webinar_attendance_intervals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained('webinar_attendances')->cascadeOnDelete();
            $table->time('entered_at'); // Время входа
            $table->time('left_at');    // Время выхода
            $table->integer('minutes')->default(0); // Сколько минут был в этом интервале
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webinar_attendance_intervals');
        Schema::dropIfExists('webinar_attendances');
    }
};