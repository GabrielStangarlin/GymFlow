<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Multibanco = 'multibanco';
    case MbWay = 'mbway';
    case Transfer = 'transfer';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Numerário',
            self::Multibanco => 'Multibanco',
            self::MbWay => 'MB WAY',
            self::Transfer => 'Transferência',
        };
    }
}
