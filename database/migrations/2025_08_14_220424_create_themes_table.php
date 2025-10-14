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
        Schema::create('themes', function (Blueprint $table) {
            $table->id();
            $table->string('header');
            $table->string('footer');
            $table->string('icon');
            $table->string('icon_back');
            $table->string('icon_border');
            $table->string('button_back');
            $table->string('button_color');
            $table->string('cat_header_back');
            $table->string('cat_header_color');
            $table->string('cat_header_active_back');
            $table->string('cat_header_active_color');
            $table->string('order_footer_back');
            $table->string('order_footer_color');
            $table->string('order_footer_n_back');
            $table->string('order_footer_n_color');
            $table->string('footer_color');
            $table->string('radio_border');
            $table->string('radio_back');
            $table->string('radio_color');
            $table->string('text');
            $table->string('modal_header_back');
            $table->string('modal_header_color');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('themes');
    }
};
