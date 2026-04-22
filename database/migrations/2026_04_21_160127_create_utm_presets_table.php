<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utm_presets', function (Blueprint $table) {
            $table->id();
            // Привязка к школе, чтобы настройки не путались между проектами
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();

            // Тип метки: source (источник), medium (канал), campaign (кампания)
            $table->string('type');

            // Человекопонятное название (например: "Реклама ВКонтакте")
            $table->string('label');

            // Техническое значение, которое пойдет в ссылку (например: "vk_ads")
            $table->string('value');

            // На всякий случай добавим сортировку и активность
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('utm_presets');
    }
};