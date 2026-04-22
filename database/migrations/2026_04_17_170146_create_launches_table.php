<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('launches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // Название запуска, например "Перепрошивка 1-3 апреля"

            // Окна учета дат по категориям продуктов
            $table->dateTime('tripwire_start')->nullable();
            $table->dateTime('tripwire_end')->nullable();

            $table->dateTime('booking_start')->nullable();
            $table->dateTime('booking_end')->nullable();

            $table->dateTime('flagship_start')->nullable();
            $table->dateTime('flagship_end')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('launches');
    }
};