<?php

use App\Enums\ItemOptionTypes;
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
        Schema::table('item_options', function (Blueprint $table) {
            $table->enum('option_type', [ItemOptionTypes::Mandatory->value, ItemOptionTypes::Optional->value])->default(ItemOptionTypes::Optional->value)->after('size_id');
            $table->boolean('is_counter')->default(false)->after('option_type');
            $table->integer('min_count')->default(0)->after('is_counter');
            $table->integer('max_count')->default(0)->afteR('min_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_options', function (Blueprint $table) {
            $table->dropColumn(['option_type', 'is_counter', 'min_count', 'max_count']);
        });
    }
};
