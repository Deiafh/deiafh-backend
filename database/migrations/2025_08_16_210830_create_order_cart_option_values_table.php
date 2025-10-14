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
        Schema::create('order_cart_option_values', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('order_cart_option_id');
            $table->foreign('order_cart_option_id')
                ->references('id')
                ->on('order_cart_options')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->unsignedBigInteger('option_value_id')->nullable();
            $table->foreign('option_value_id')
                ->references('id')
                ->on('item_option_values')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->string('option_value_title');

            $table->integer('option_value_count');
            
            $table->float('option_value_single_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_cart_option_values');
    }
};
