<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Clear old free-text values that can't cast to integer
        DB::table('settings')->update(['average_order_time' => null]);

        Schema::table('settings', function (Blueprint $table) {
            $table->unsignedInteger('average_order_time')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('average_order_time')->nullable()->change();
        });
    }
};
