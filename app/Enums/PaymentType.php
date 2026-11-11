<?php

namespace App\Enums;

enum PaymentType: string
{
    case Period = 'period';
    case Debt = 'debt';

    public function label(): string
    {
        return match ($this) {
            self::Period => 'Período',
            self::Debt => 'Dívida',
        };
    }
}
