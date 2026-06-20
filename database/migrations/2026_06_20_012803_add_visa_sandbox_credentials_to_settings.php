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
        Schema::table('settings', function (Blueprint $table) {
            // QNB sandbox
            $table->string('qnb_sandbox_merchant_id')->nullable()->after('qnb_api_password');
            $table->string('qnb_sandbox_api_password')->nullable()->after('qnb_sandbox_merchant_id');
            // Paymob sandbox
            $table->string('paymob_sandbox_secret_key', 1000)->nullable()->after('paymob_integration_id');
            $table->string('paymob_sandbox_public_key', 1000)->nullable()->after('paymob_sandbox_secret_key');
            $table->string('paymob_sandbox_hmac')->nullable()->after('paymob_sandbox_public_key');
            $table->string('paymob_sandbox_integration_id')->nullable()->after('paymob_sandbox_hmac');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'qnb_sandbox_merchant_id', 'qnb_sandbox_api_password',
                'paymob_sandbox_secret_key', 'paymob_sandbox_public_key',
                'paymob_sandbox_hmac', 'paymob_sandbox_integration_id',
            ]);
        });
    }
};
