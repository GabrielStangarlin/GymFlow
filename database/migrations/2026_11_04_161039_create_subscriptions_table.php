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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained();
            $table->foreignId('plan_id')->constrained();
            $table->foreignId('billing_period_id')->constrained();
            $table->string('payment_method');
            $table->decimal('monthly_price', 8, 2);
            $table->decimal('discount_percent', 5, 2);
            $table->date('starts_on')->nullable();
            $table->date('paid_until')->nullable();
            $table->date('ended_on')->nullable();
            $table->string('end_reason')->nullable();
            $table->dateTime('debt_settled_at')->nullable();
            $table->string('change_type');
            $table->foreignId('previous_subscription_id')->nullable()->constrained('subscriptions');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
