<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'monthly_price', 'active'])]
class Plan extends Model
{
    /** @use HasFactory<PlanFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'monthly_price' => 'decimal:2',
            'active' => 'boolean',
        ];
    }

    public function timeWindows(): HasMany
    {
        return $this->hasMany(PlanTimeWindow::class);
    }

    public function allowsTime(CarbonInterface $at): bool
    {
        $time = $at->format('H:i');

        return $this->timeWindows->contains(
            fn (PlanTimeWindow $window): bool => $time >= substr($window->starts_at, 0, 5) && $time <= substr($window->ends_at, 0, 5)
        );
    }
}
