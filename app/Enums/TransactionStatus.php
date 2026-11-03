<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case Posted = 'posted';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Posted => 'Tercatat',
            self::Cancelled => 'Batal',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Posted => 'gas',
            self::Cancelled => 'flame',
        };
    }
}
