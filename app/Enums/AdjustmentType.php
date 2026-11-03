<?php

namespace App\Enums;

enum AdjustmentType: string
{
    case Rusak = 'rusak';
    case Hilang = 'hilang';
    case Koreksi = 'koreksi';
    case Opname = 'opname';

    public function label(): string
    {
        return match ($this) {
            self::Rusak => 'Tabung Rusak/Bocor',
            self::Hilang => 'Tabung Hilang',
            self::Koreksi => 'Koreksi Stok',
            self::Opname => 'Hasil Stok Opname',
        };
    }

    /** Jenis yang boleh dibuat manual lewat form penyesuaian. */
    public static function manual(): array
    {
        return [self::Rusak, self::Hilang, self::Koreksi];
    }
}
