<?php

namespace App\Enums;

/** Jenis mutasi pada buku besar stok (stock_movements.type). */
enum MovementType: string
{
    case Penerimaan = 'penerimaan';
    case Distribusi = 'distribusi';
    case Penyesuaian = 'penyesuaian';
    case Opname = 'opname';
    case Pembatalan = 'pembatalan';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
