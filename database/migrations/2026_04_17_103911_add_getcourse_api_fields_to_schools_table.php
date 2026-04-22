<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->string('getcourse_domain')->nullable()->after('uuid');
            $table->string('getcourse_api_key')->nullable()->after('getcourse_domain');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn(['getcourse_domain', 'getcourse_api_key']);
        });
    }
};