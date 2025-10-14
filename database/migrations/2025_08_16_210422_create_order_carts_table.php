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
        Schema::create('order_carts', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('order_id');
            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->unsignedBigInteger('item_id')->nullable();
            $table->foreign('item_id')
                ->references('id')
                ->on('items')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->string('item_name');

            $table->integer('item_count');
            $table->float('item_single_price');

            $table->float('item_total_price_with_options');

            $table->unsignedBigInteger('item_size_id')->nullable();
            $table->foreign('item_size_id')
                ->references('id')
                ->on('item_sizes')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->string('item_size_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_carts');
    }
};
