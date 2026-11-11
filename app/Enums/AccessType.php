<?php

namespace App\Enums;

enum AccessType: string
{
    case Entry = 'entry';
    case Exit = 'exit';
    case Refused = 'refused';

    public function label(): string
    {
        return match ($this) {
            self::Entry => 'Entrada',
            self::Exit => 'Saída',
            self::Refused => 'Recusado',
        };
    }
}
