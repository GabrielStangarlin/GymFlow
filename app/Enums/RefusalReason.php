<?php

namespace App\Enums;

enum RefusalReason: string
{
    case Pending = 'pending';
    case Overdue = 'overdue';
    case OutsideHours = 'outside_hours';
    case NoSubscription = 'no_subscription';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pagamento pendente',
            self::Overdue => 'Em atraso',
            self::OutsideHours => 'Fora do horário do plano',
            self::NoSubscription => 'Sem assinatura',
            self::Unknown => 'QR desconhecido',
        };
    }
}
