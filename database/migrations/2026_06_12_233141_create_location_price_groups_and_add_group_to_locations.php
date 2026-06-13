<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('location_price_groups')) {
            Schema::create('location_price_groups', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->decimal('price', 8, 2)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasColumn('branch_locations', 'price_group_id')) {
            Schema::table('branch_locations', function (Blueprint $table) {
                $table->unsignedBigInteger('price_group_id')->nullable()->after('price');
            });
        }
    }

    public function down(): void
    {
        Schema::table('branch_locations', function (Blueprint $table) {
            $table->dropColumn('price_group_id');
        });

        Schema::dropIfExists('location_price_groups');
    }
};
