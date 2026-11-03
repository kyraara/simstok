<?php

namespace App\Enums;

enum StockCondition: string
{
    case Isi = 'isi';
    case Kosong = 'kosong';
    case Rusak = 'rusak';

    public function label(): string
    {
        return match ($this) {
            self::Isi => 'Isi',
            self::Kosong => 'Kosong',
            self::Rusak => 'Rusak',
        };
    }

    /** Nama kelas warna Tailwind yang konsisten di seluruh aplikasi. */
    public function color(): string
    {
        return match ($this) {
            self::Isi => 'gas',
            self::Kosong => 'steel',
            self::Rusak => 'flame',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
