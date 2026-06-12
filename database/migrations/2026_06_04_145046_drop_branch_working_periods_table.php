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
        Schema::dropIfExists('branch_working_periods');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('branch_working_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->foreign('branch_id')->on('branches')->references('id')->onDelete('cascade')->onUpdate('cascade');
            $table->string('from_date');
            $table->string('to_date');
            $table->timestamps();
        });
    }
};
