<?php

namespace App\Models;

use Database\Factories\BillingPeriodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'months', 'discount_percent'])]
class BillingPeriod extends Model
{
    /** @use HasFactory<BillingPeriodFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'months' => 'integer',
            'discount_percent' => 'decimal:2',
        ];
    }
}
