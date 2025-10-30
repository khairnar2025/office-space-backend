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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id')->nullable(); // guest possible
            $table->string('session_id')->nullable(); // guest cart reference

            $table->string('razorpay_order_id')->nullable();
            $table->string('razorpay_payment_id')->nullable();
            $table->string('razorpay_signature')->nullable();

            $table->decimal('total_amount', 10, 2)->default(0);
            $table->string('currency', 10)->default('INR');

            // Shipping details from checkout form
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone');
            $table->text('address');
            $table->string('pincode')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();

            $table->enum('status', [
                'pending',
                'paid',
                'failed',
                'cancelled'
            ])->default('pending');


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
