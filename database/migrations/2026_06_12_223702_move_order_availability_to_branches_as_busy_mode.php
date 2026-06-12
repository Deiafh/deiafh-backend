<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['is_order_available', 'ordering_disabled_msg']);
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->boolean('is_busy')->default(false)->after('is_pickup_available');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('is_busy');
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('is_order_available')->default(true);
            $table->string('ordering_disabled_msg')->nullable();
        });
    }
};
