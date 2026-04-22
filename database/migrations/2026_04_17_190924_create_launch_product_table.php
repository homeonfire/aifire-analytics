<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('launch_product', function (Blueprint $table) {
            $table->id();
            // Привязка к запуску
            $table->foreignId('launch_id')->constrained()->cascadeOnDelete();
            // Привязка к продукту
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('launch_product');
    }
};