<?php

use App\Enums\ActiveStatus;
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
        Schema::create('items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('category_id');
            $table->foreign('category_id')->on('categories')->references('id')->onDelete('cascade')->onUpdate('cascade');
            
            $table->string('title');
            $table->text('description')->nullable();
            $table->float('old_price', 2)->nullable();
            $table->string('img');

            $table->enum('active', [ActiveStatus::Inactive->value, ActiveStatus::Active->value])->default(ActiveStatus::Active->value);
            
            $table->integer('sort');

            $table->unique(['category_id', 'sort']);
            
            $table->timestamp('from')->nullable();
            $table->timestamp('to')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
