<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('webinar_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained('webinar_attendances')->cascadeOnDelete();
            $table->string('time')->nullable(); // Время сообщения (например, 19:15:00)
            $table->text('message'); // Текст сообщения
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webinar_chat_messages');
    }
};
