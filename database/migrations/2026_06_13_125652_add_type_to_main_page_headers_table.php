<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('main_page_headers', function (Blueprint $table) {
            $table->enum('type', ['image', 'video'])->default('image')->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('main_page_headers', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
