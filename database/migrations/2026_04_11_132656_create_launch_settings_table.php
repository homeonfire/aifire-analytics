<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('launch_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('cohort'); // Название потока
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
            $table->timestamps();

            // Жесткое правило: один набор настроек на каждый поток внутри школы
            $table->unique(['school_id', 'cohort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('launch_settings');
    }
};