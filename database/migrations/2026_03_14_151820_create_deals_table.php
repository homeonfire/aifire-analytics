<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deals', function (Blueprint $table) {
            $table->id();

            // Тот самый мостик для связи с клиентом.
            // cascadeOnDelete означает, что если удалить клиента, удалятся и его заказы.
            $table->foreignId('unified_client_id')->constrained()->cascadeOnDelete();

            // Данные заказа
            $table->string('gc_number')->unique();      // Номер заказа в ГК
            $table->string('product_title')->nullable(); // Состав заказа
            $table->string('status')->nullable();        // Статус (Завершен, В работе)
            $table->decimal('cost', 12, 2)->default(0);  // Стоимость
            $table->string('manager_name')->nullable();  // Менеджер

            // Даты из ГК
            $table->timestamp('gc_created_at')->nullable();
            $table->timestamp('gc_paid_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};