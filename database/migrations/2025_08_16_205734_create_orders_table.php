<?php

use App\Enums\DiscountApproachType;
use App\Enums\DiscountPaymentType;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->enum('type', [OrderType::DELIVERY->value, OrderType::PICK_UP->value]);

            $table->string('client_name');
            $table->string('client_phone');
            $table->string('client_additional_phone')->nullable();

            $table->unsignedBigInteger('branch_id')->nullable();
            $table->foreign('branch_id')
            ->references('id')
            ->on('branches')
            ->onDelete('set null')
            ->onUpdate('cascade');

            $table->string('branch_name');

            $table->foreign(['branch_id', 'branch_name'])
            ->references(['branch_id', 'name'])
            ->on('branch_locations')
            ->onDelete('no action')
            ->onUpdate('cascade');

            $table->string('location_name');
            
            $table->text('address');
            $table->text('notice')->nullable();

            $table->enum('payment_type', [DiscountPaymentType::CASH->value, DiscountPaymentType::VISA->value]);
            
            $table->float('total_cart_price');
            $table->float('delivery_price')->default(0);
            $table->float('tax')->default(0);

            $table->unsignedBigInteger('discount_id')->nullable();
            $table->foreign('discount_id')
            ->references('id')
            ->on('discounts')
            ->onDelete('set null')
            ->onUpdate('cascade');

            $table->string('discount_name')->nullable();
            $table->string('discount_code')->nullable();
            $table->float('order_discount_amount')->default(0);
            $table->float('delivery_discount_amount')->default(0);

            // $table->string('transaction_id')->nullable();   

            // $table->unsignedBigInteger('visa_order_request_id')->nullable();
            // $table->foreign('visa_order_request_id')
            // ->references('id')
            // ->on('visa_order_requests')
            // ->onDelete('set null')
            // ->onUpdate('cascade');
            // $table->float('visa_fees_amount')->default(0);

            $table->enum('status', [OrderStatus::ACCEPTED->value, OrderStatus::PENDING->value, OrderStatus::REJECTED->value])->default('pending');

            $table->unsignedBigInteger('accepted_by')->nullable();
            $table->foreign('accepted_by')
            ->references('id')
            ->on('users')
            ->onDelete('set null')
            ->onUpdate('cascade');

            $table->string('accepted_by_name')->nullable();

            $table->timestamp('accepted_at')->nullable();

            $table->unsignedBigInteger('canceled_by')->nullable();
            $table->foreign('canceled_by')
            ->references('id')
            ->on('users')
            ->onDelete('set null')
            ->onUpdate('cascade');

            $table->string('canceled_by_name')->nullable();
            
            $table->timestamp('canceled_at')->nullable();

            $table->text('cancel_reason')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
