<?php

namespace App\Enums;

enum EndReason: string
{
    case Replaced = 'replaced';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Replaced => 'Substituída',
            self::Overdue => 'Em atraso',
            self::Cancelled => 'Cancelada',
        };
    }
}
