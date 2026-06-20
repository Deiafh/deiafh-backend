<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('qnb_merchant_id')->nullable()->after('visa_provider');
            $table->string('qnb_api_password')->nullable()->after('qnb_merchant_id');
            $table->string('paymob_secret_key')->nullable()->after('qnb_api_password');
            $table->string('paymob_public_key')->nullable()->after('paymob_secret_key');
            $table->string('paymob_hmac')->nullable()->after('paymob_public_key');
            $table->string('paymob_integration_id')->nullable()->after('paymob_hmac');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->float('visa_fees')->default(0)->after('tax');
            $table->string('payment_operation_id')->nullable()->after('payment_type');
            $table->boolean('payment_verified')->default(true)->after('payment_operation_id');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'qnb_merchant_id', 'qnb_api_password',
                'paymob_secret_key', 'paymob_public_key', 'paymob_hmac', 'paymob_integration_id',
            ]);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['visa_fees', 'payment_operation_id', 'payment_verified']);
        });
    }
};
