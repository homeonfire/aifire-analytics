<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('deal_id')->nullable()->constrained()->nullOnDelete();

            // 0 - ID платежа
            $table->unsignedBigInteger('gc_payment_id')->nullable()->unique();
            // 1, 2 - Клиент
            $table->string('client_name')->nullable();
            $table->string('client_email')->nullable();
            // 3 - Номер заказа (для связи с Deals)
            $table->string('gc_deal_number')->nullable();
            // 4 - Дата платежа
            $table->timestamp('gc_created_at')->nullable();
            // 5 - Платежная система
            $table->string('payment_system')->nullable();
            // 6 - Статус
            $table->string('status')->nullable();
            // 7, 8, 9 - Финансы (Очистим от текста " руб." при импорте)
            $table->decimal('amount', 12, 2)->default(0); // Грязными
            $table->decimal('commission_amount', 12, 2)->default(0); // Комиссия эквайринга
            $table->decimal('net_amount', 12, 2)->default(0); // Чистыми
            // 10 - Код операции
            $table->string('operation_id')->nullable();
            // 11 - Название предложения
            $table->string('offer_name')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};