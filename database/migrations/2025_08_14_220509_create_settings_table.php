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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('logo');
            $table->string('keywords')->nullable();
            $table->string('description')->nullable();
            $table->string('background')->nullable();
            $table->string('average_order_time')->nullable();
            $table->string('whatsapp_order_phone_number')->nullable();
            $table->boolean('is_order_available')->default('0');
            $table->string('ordering_disabled_msg')->nullable();
            $table->float('order_min')->default('0');
            $table->float('tax')->default('0');
            $table->boolean('is_whatsapp_available')->default('0');
            $table->string('currency');
            $table->string('time_zone');
            $table->enum('dir', ['rtl', 'ltr']);
            $table->enum('lang' , ['en', 'ar']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
