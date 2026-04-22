<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('title')->unique(); // Название уникальное, чтобы не плодить дубли
            $table->decimal('price', 12, 2)->default(0); // Базовая цена

            // Здесь будем хранить категорию/тег (Лид-магнит, Регистрация, Продукт и т.д.)
            $table->string('category')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
