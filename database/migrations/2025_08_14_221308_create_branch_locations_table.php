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
        Schema::create('branch_locations', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id');
            $table->foreign('branch_id')->on('branches')->references('id')->onDelete('cascade')->onUpdate('cascade');
            $table->string('name');
            $table->float('price', 2);
            $table->boolean('active');
            $table->primary(['branch_id', 'name']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_locations');
    }
};
