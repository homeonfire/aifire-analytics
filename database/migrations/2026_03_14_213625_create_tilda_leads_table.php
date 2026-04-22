<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tilda_leads', function (Blueprint $table) {
            $table->id();
            // Связь с нашим главным профилем клиента (может быть пустой, если клиент еще не склеился)
            $table->foreignId('unified_client_id')->nullable()->constrained('unified_clients')->nullOnDelete();

            // Данные из формы
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            // Аналитика
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_content')->nullable();
            $table->string('utm_term')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tilda_leads');
    }
};
