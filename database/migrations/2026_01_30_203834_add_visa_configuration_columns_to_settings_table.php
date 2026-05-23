<?php

use App\OnlinePayment\PaymentProviders;
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
        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('is_visa_enabled')->default(false)->after("lang");
            $table->enum("visa_provider", PaymentProviders::values())->nullable()->after("is_visa_enabled");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['is_visa_enabled', 'visa_providor']);
        });
    }
};
