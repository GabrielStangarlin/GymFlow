<?php

namespace App\Enums;

enum ChangeType: string
{
    case New = 'new';
    case Upgrade = 'upgrade';
    case Downgrade = 'downgrade';
    case Lateral = 'lateral';
    case Renewal = 'renewal';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Nova',
            self::Upgrade => 'Upgrade',
            self::Downgrade => 'Downgrade',
            self::Lateral => 'Mudança lateral',
            self::Renewal => 'Renovação',
        };
    }
}
