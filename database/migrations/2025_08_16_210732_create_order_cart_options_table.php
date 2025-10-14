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
        Schema::create('order_cart_options', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('order_cart_id');
            $table->foreign('order_cart_id')
                ->references('id')
                ->on('order_carts')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->unsignedBigInteger('option_id')->nullable();
            $table->foreign('option_id')
                ->references('id')
                ->on('item_options')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->string('option_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_cart_options');
    }
};
