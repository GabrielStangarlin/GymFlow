<?php

namespace App\Models;

use App\Enums\ChangeType;
use App\Enums\EndReason;
use App\Enums\PaymentMethod;
use App\Enums\SubscriptionStatus;
use Carbon\CarbonInterface;
use Database\Factories\SubscriptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'client_id', 'plan_id', 'billing_period_id', 'payment_method', 'monthly_price', 'discount_percent',
    'starts_on', 'paid_until', 'ended_on', 'end_reason', 'debt_settled_at', 'change_type',
    'previous_subscription_id',
])]
class Subscription extends Model
{
    /** @use HasFactory<SubscriptionFactory> */
    use HasFactory;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'payment_method' => PaymentMethod::class,
            'end_reason' => EndReason::class,
            'change_type' => ChangeType::class,
            'monthly_price' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'starts_on' => 'date',
            'paid_until' => 'date',
            'ended_on' => 'date',
            'debt_settled_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function billingPeriod(): BelongsTo
    {
        return $this->belongsTo(BillingPeriod::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function previous(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_subscription_id');
    }

    public function status(?CarbonInterface $today = null): SubscriptionStatus
    {
        if ($this->ended_on !== null) {
            return SubscriptionStatus::Ended;
        }

        if ($this->paid_until === null) {
            return SubscriptionStatus::Pending;
        }

        $today ??= today();

        return $today->toDateString() <= $this->paid_until->toDateString()
            ? SubscriptionStatus::Active
            : SubscriptionStatus::Overdue;
    }

    /**
     * Uses the price and discount copied onto the subscription, not the plan's current values.
     */
    public function amountPerPeriod(): float
    {
        return round((float) $this->monthly_price * $this->billingPeriod->months * (1 - (float) $this->discount_percent / 100), 2);
    }

    public function hasOutstandingDebt(): bool
    {
        return $this->end_reason === EndReason::Overdue && $this->debt_settled_at === null;
    }
}
