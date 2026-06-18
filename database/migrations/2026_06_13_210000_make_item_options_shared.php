<?php

use App\Enums\ItemOptionTypes;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Pivot: links items to shared options, carries per-item settings
        Schema::create('item_option_item', function (Blueprint $table) {
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('item_option_id');
            $table->primary(['item_id', 'item_option_id']);

            $table->foreign('item_id')->references('id')->on('items')->cascadeOnDelete();
            $table->foreign('item_option_id')->references('id')->on('item_options')->cascadeOnDelete();

            $table->unsignedBigInteger('size_id')->nullable();
            $table->foreign('size_id')->references('id')->on('item_sizes')->nullOnDelete();

            $table->enum('option_type', [ItemOptionTypes::Mandatory->value, ItemOptionTypes::Optional->value])
                  ->default(ItemOptionTypes::Optional->value);
            $table->boolean('is_counter')->default(false);
            $table->integer('min_count')->default(0);
            $table->integer('max_count')->default(0);
        });

        // Migrate existing per-item data into the new pivot
        DB::statement('
            INSERT INTO item_option_item (item_id, item_option_id, size_id, option_type, is_counter, min_count, max_count)
            SELECT item_id, id, size_id, option_type, is_counter, min_count, max_count
            FROM item_options
            WHERE item_id IS NOT NULL
        ');

        // Drop per-item columns from item_options (it becomes a standalone shared entity)
        Schema::table('item_options', function (Blueprint $table) {
            $table->dropForeign(['size_id']);
            $table->dropForeign(['item_id']);
            $table->dropColumn(['item_id', 'size_id', 'option_type', 'is_counter', 'min_count', 'max_count']);
        });
    }

    public function down(): void
    {
        Schema::table('item_options', function (Blueprint $table) {
            $table->unsignedBigInteger('item_id')->nullable()->after('id');
            $table->foreign('item_id')->references('id')->on('items')->cascadeOnDelete();
            $table->unsignedBigInteger('size_id')->nullable()->after('title');
            $table->foreign('size_id')->references('id')->on('item_sizes')->nullOnDelete();
            $table->enum('option_type', [ItemOptionTypes::Mandatory->value, ItemOptionTypes::Optional->value])
                  ->default(ItemOptionTypes::Optional->value)->after('size_id');
            $table->boolean('is_counter')->default(false)->after('option_type');
            $table->integer('min_count')->default(0)->after('is_counter');
            $table->integer('max_count')->default(0)->after('min_count');
        });

        Schema::dropIfExists('item_option_item');
    }
};
