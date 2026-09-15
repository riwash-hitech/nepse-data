<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->string('country', 100)->nullable()->after('ip_address');
            $table->string('city', 100)->nullable()->after('country');
            $table->decimal('latitude', 10, 6)->nullable()->after('city');
            $table->decimal('longitude', 10, 6)->nullable()->after('latitude');
            $table->string('device_type', 20)->nullable()->after('user_agent');
            $table->string('browser', 60)->nullable()->after('device_type');
            $table->string('platform', 60)->nullable()->after('browser');
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropColumn(['country', 'city', 'latitude', 'longitude', 'device_type', 'browser', 'platform']);
        });
    }
};
