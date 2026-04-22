<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('deals', function (Blueprint $table) {
            // Добавляем колонку. nullable() обязателен, чтобы старые записи не сломались
            // Если там лежат числа, используй ->unsignedBigInteger(). Если текст/хэш - ->string()
            $table->unsignedBigInteger('gc_order_id')->nullable()->after('id'); 
        });
    }

    public function down()
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->dropColumn('gc_order_id');
        });
    }
};