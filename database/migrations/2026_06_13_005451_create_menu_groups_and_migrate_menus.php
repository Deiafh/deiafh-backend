<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->unsignedBigInteger('menu_group_id')->nullable()->after('working_period_group_id');
        });

        Schema::table('menus', function (Blueprint $table) {
            $table->unsignedBigInteger('menu_group_id')->nullable()->after('id');
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
    }

    public function down(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->dropColumn('menu_group_id');
            $table->unsignedBigInteger('branch_id')->nullable();
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('menu_group_id');
        });

        Schema::dropIfExists('menu_groups');
    }
};
