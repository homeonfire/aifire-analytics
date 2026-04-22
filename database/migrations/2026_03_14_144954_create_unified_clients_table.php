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
        Schema::create('unified_clients', function (Blueprint $table) {
            $table->id();
            $table->string('email')->nullable()->unique()->index();
            $table->string('phone')->nullable()->index();
            $table->string('salebot_id')->nullable()->unique();
            $table->string('getcourse_id')->nullable()->unique();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->decimal('total_spent', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unified_clients');
    }
};
