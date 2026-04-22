<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unified_clients', function (Blueprint $table) {
            // Добавляем новые поля после фамилии (просто для красоты структуры)
            $table->string('city')->nullable()->after('last_name');
            $table->text('avatar_url')->nullable()->after('city'); // text, так как ссылки бывают длинными
            $table->string('manager_email')->nullable()->after('avatar_url');
        });
    }

    public function down(): void
    {
        Schema::table('unified_clients', function (Blueprint $table) {
            // Если захотим откатить миграцию — удаляем эти поля
            $table->dropColumn(['city', 'avatar_url', 'manager_email']);
        });
    }
};
