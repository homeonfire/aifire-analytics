<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gc_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();

            // ID задачи в ГетКурсе
            $table->string('export_id')->unique();

            // Период, за который мы запросили данные
            $table->date('date_from');
            $table->date('date_to');

            // Статус: pending (в ожидании), completed (успешно), failed (ошибка)
            $table->string('status')->default('pending');

            // Если вылезет ошибка (например, неверный ключ)
            $table->text('error_message')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gc_exports');
    }
};