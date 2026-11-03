<?php

namespace App\Exceptions;

use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Pelanggaran aturan bisnis (mis. membatalkan transaksi yang sudah batal). */
class BusinessRuleException extends DomainException
{
    public function render(Request $request): RedirectResponse|false
    {
        if ($request->expectsJson()) {
            return false;
        }

        return back()->withInput()->withErrors(['general' => $this->getMessage()]);
    }
}
