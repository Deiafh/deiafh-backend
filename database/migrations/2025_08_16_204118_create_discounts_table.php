<?php

use App\enums\ActiveStatus;
use App\Enums\DiscountApproachType;
use App\Enums\DiscountConditionsType;
use App\Enums\DiscountPaymentType;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
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
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            
            $table->string('code');
            $table->string('name');
            $table->float('min_order');
            $table->float('max_discount');
            $table->float('max_user_uses');
            $table->float('max_uses');
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->enum('active', [ActiveStatus::Inactive->value, ActiveStatus::Active->value])->default(ActiveStatus::Active->value);
            $table->enum('discount_type', [DiscountType::CART_DISCOUNT->value, DiscountType::DELIVERY_DISCOUNT->value]);
            $table->float('discount_value');
            $table->enum('discount_value_type', [DiscountValueType::PERCENTAGE->value, DiscountValueType::FIXED->value]);
            $table->boolean('public');
            $table->enum('approach', [DiscountApproachType::ALL->value, DiscountApproachType::DELIVERY->value, DiscountApproachType::PICK_UP->value]);
            $table->enum('payment_method', [DiscountPaymentType::ALL->value, DiscountPaymentType::CASH->value, DiscountPaymentType::VISA->value]);

            $table->enum('locations_type', DiscountConditionsType::getArray())->default(DiscountConditionsType::All->value);
            $table->enum('categories_type', DiscountConditionsType::getArray())->default(DiscountConditionsType::All->value);
            $table->enum('items_type', DiscountConditionsType::getArray())->default(DiscountConditionsType::All->value);
            $table->enum('phones_type', DiscountConditionsType::getArray())->default(DiscountConditionsType::All->value);
            $table->enum('branches_type', DiscountConditionsType::getArray())->default(DiscountConditionsType::All->value);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
};
