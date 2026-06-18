<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('cancel_reason_id')->nullable()->after('status')->constrained('cancel_reasons')->nullOnDelete();
            $table->text('cancel_notes')->nullable()->after('cancel_reason_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['cancel_reason_id']);
            $table->dropColumn(['cancel_reason_id', 'cancel_notes']);
        });
    }
};
