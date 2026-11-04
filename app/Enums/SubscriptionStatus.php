<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Overdue = 'overdue';
    case Ended = 'ended';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Active => 'Ativa',
            self::Overdue => 'Em atraso',
            self::Ended => 'Terminada',
        };
    }
}
