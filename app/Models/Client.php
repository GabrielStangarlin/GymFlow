<?php

namespace App\Models;

use App\Enums\EndReason;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'phone', 'nif', 'birth_date', 'qr_token', 'active'])]
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function currentSubscription(): ?Subscription
    {
        return $this->subscriptions()->whereNull('ended_on')->latest('id')->first();
    }

    public function hasOutstandingDebt(): bool
    {
        return $this->subscriptions()
            ->where('end_reason', EndReason::Overdue)
            ->whereNull('debt_settled_at')
            ->exists();
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(AccessLog::class);
    }

    public function qrSvg(int $size = 280): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle($size), new SvgImageBackEnd));

        return $writer->writeString($this->qr_token);
    }

    protected function name(): Attribute
    {
        return Attribute::get(fn (): string => $this->user->name);
    }

    protected function email(): Attribute
    {
        return Attribute::get(fn (): string => $this->user->email);
    }
}
