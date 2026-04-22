<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('webinars', function (Blueprint $table) {
        // Добавляем ID запуска
        $table->unsignedBigInteger('launch_id')->nullable();
    });
}

public function down()
{
    Schema::table('webinars', function (Blueprint $table) {
        $table->dropColumn('launch_id');
    });
}
};
