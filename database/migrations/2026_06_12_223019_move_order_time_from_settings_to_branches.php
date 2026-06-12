<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['order_time_from', 'order_time_to']);
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->unsignedInteger('order_time_from')->nullable()->after('tax');
            $table->unsignedInteger('order_time_to')->nullable()->after('order_time_from');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn(['order_time_from', 'order_time_to']);
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->unsignedInteger('order_time_from')->nullable()->after('description');
            $table->unsignedInteger('order_time_to')->nullable()->after('order_time_from');
        });
    }
};
