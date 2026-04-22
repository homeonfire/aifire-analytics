<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webinars', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // Название из файла "Сведения"
            $table->string('room_id')->index(); // ID комнаты
            $table->timestamp('started_at')->nullable();
            $table->integer('duration_minutes')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webinars');
    }
};