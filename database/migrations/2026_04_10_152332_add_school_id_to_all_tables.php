<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = ['unified_clients', 'deals', 'products', 'webinars', 'managers', 'tilda_leads'];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->foreignId('school_id')->nullable()->constrained('schools')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        $tables = ['unified_clients', 'deals', 'products', 'webinars', 'managers', 'tilda_leads'];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropForeign(['school_id']);
                $table->dropColumn('school_id');
            });
        }
    }
};