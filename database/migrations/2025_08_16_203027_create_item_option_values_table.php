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
        Schema::create('item_option_values', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('item_option_id');
            $table->foreign('item_option_id')->references('id')->on('item_options');

            $table->string('title');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_option_values');
    }
};
