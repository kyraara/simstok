<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * Dasar controller master data: semua peran boleh melihat, hanya admin yang boleh mengubah.
 */
abstract class MasterController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:master.view', only: ['index', 'show']),
            new Middleware('permission:master.manage', except: ['index', 'show']),
        ];
    }

    /**
     * Hapus data master. Bila sudah dipakai di transaksi, tolak dan sarankan nonaktifkan.
     */
    protected function deleteOrDeactivate(Model $model, string $label, string $redirectRoute): RedirectResponse
    {
        try {
            $model->delete();
        } catch (QueryException) {
            return back()->withErrors([
                'general' => "{$label} tidak bisa dihapus karena sudah dipakai di transaksi. Nonaktifkan saja agar tidak muncul di form.",
            ]);
        }

        return redirect()->route($redirectRoute)->with('success', "{$label} berhasil dihapus.");
    }
}
