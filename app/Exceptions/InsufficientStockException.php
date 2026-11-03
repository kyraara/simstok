<?php

namespace App\Exceptions;

use App\Enums\StockCondition;
use App\Models\CylinderType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/** Dilempar StockService ketika mutasi akan membuat stok menjadi minus. */
class InsufficientStockException extends RuntimeException
{
    public function __construct(
        public readonly CylinderType $cylinderType,
        public readonly StockCondition $condition,
        public readonly int $available,
        public readonly int $requested,
    ) {
        parent::__construct(sprintf(
            'Stok %s (%s) tidak cukup. Tersedia %s, dibutuhkan %s.',
            $cylinderType->name,
            strtolower($condition->label()),
            number_format($available, 0, ',', '.'),
            number_format($requested, 0, ',', '.'),
        ));
    }

    /** Dikembalikan ke form sebelumnya sebagai pesan error validasi. */
    public function render(Request $request): RedirectResponse|false
    {
        if ($request->expectsJson()) {
            return false;
        }

        return back()->withInput()->withErrors(['stock' => $this->getMessage()]);
    }
}
