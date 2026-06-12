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
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('hasOwnWorkingPeriods');
            $table->unsignedBigInteger('working_period_group_id')->nullable();
            $table->foreign('working_period_group_id')->references('id')->on('working_period_groups')->onDelete('set null')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropForeign(['working_period_group_id']);
            $table->dropColumn('working_period_group_id');
            $table->boolean('hasOwnWorkingPeriods')->default(false);
        });
    }
};
