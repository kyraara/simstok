<?php

namespace App\Providers;

use App\Http\Controllers\NotificationController;
use App\Models\Adjustment;
use App\Models\CylinderType;
use App\Models\Distribution;
use App\Models\Receipt;
use App\Models\StockOpname;
use App\Models\User;
use App\Services\StockService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));

        // Nama pendek untuk kolom reference_type / model_type.
        Relation::enforceMorphMap([
            'user' => User::class,
            'receipt' => Receipt::class,
            'distribution' => Distribution::class,
            'adjustment' => Adjustment::class,
            'stock_opname' => StockOpname::class,
        ]);

        // Jenis tabung baru otomatis punya saldo isi/kosong/rusak = 0.
        CylinderType::created(fn (CylinderType $type) => app(StockService::class)->ensureStockRows($type));

        // Data awal lonceng notifikasi di top bar (selanjutnya diperbarui berkala lewat fetch).
        View::composer('components.layouts.app', function ($view) {
            if ($user = auth()->user()) {
                $view->with('notificationFeed', NotificationController::feed($user));
            }
        });

        // Format angka Indonesia: 1.240
        Blade::directive('num', fn ($expression) => "<?php echo number_format((float) ($expression), 0, ',', '.'); ?>");
    }
}
