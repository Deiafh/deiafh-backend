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
        Schema::create('discount_locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('discount_id');
            $table->unsignedBigInteger('location_id');

            $table->foreign('discount_id')
                    ->on('discounts')
                    ->references('id')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');

            $table->foreign('location_id')
                    ->on('branch_locations')
                    ->references('id')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discount_locations');
    }
};
