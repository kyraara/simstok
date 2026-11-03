<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Master\CylinderTypeController;
use App\Http\Controllers\Master\OutletController;
use App\Http\Controllers\Master\SupplierController;
use App\Http\Controllers\Master\VehicleController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockCardController;
use App\Http\Controllers\StockOpnameController;
use App\Http\Controllers\Transaction\AdjustmentController;
use App\Http\Controllers\Transaction\DistributionController;
use App\Http\Controllers\Transaction\ReceiptController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::redirect('/', '/dashboard');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::get('/notifications/{id}', [NotificationController::class, 'open'])->name('notifications.open');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'password'])->name('profile.password');

    // Master data — lihat: semua peran, kelola: admin.
    Route::prefix('master')->name('master.')->group(function () {
        Route::resource('cylinder-types', CylinderTypeController::class)->except('show');
        Route::resource('suppliers', SupplierController::class)->except('show');
        Route::resource('outlets', OutletController::class);
        Route::resource('vehicles', VehicleController::class)->except('show');
    });

    Route::resource('users', UserController::class)->except('show')->middleware('permission:users.manage');

    // Penerimaan dari SPBE
    Route::get('/receipts', [ReceiptController::class, 'index'])->name('receipts.index')->middleware('permission:transactions.view');
    Route::get('/receipts/create', [ReceiptController::class, 'create'])->name('receipts.create')->middleware('permission:transactions.create');
    Route::post('/receipts', [ReceiptController::class, 'store'])->name('receipts.store')->middleware('permission:transactions.create');
    Route::get('/receipts/{receipt}', [ReceiptController::class, 'show'])->name('receipts.show')->middleware('permission:transactions.view');
    Route::post('/receipts/{receipt}/cancel', [ReceiptController::class, 'cancel'])->name('receipts.cancel')->middleware('permission:transactions.cancel');

    // Distribusi ke pangkalan
    Route::get('/distributions', [DistributionController::class, 'index'])->name('distributions.index')->middleware('permission:transactions.view');
    Route::get('/distributions/create', [DistributionController::class, 'create'])->name('distributions.create')->middleware('permission:transactions.create');
    Route::get('/distributions/quota', [DistributionController::class, 'quota'])->name('distributions.quota')->middleware('permission:transactions.create');
    Route::post('/distributions', [DistributionController::class, 'store'])->name('distributions.store')->middleware('permission:transactions.create');
    Route::get('/distributions/{distribution}', [DistributionController::class, 'show'])->name('distributions.show')->middleware('permission:transactions.view');
    Route::get('/distributions/{distribution}/print', [DistributionController::class, 'print'])->name('distributions.print')->middleware('permission:transactions.view');
    Route::post('/distributions/{distribution}/cancel', [DistributionController::class, 'cancel'])->name('distributions.cancel')->middleware('permission:transactions.cancel');

    // Penyesuaian stok
    Route::get('/adjustments', [AdjustmentController::class, 'index'])->name('adjustments.index')->middleware('permission:transactions.view');
    Route::get('/adjustments/create', [AdjustmentController::class, 'create'])->name('adjustments.create')->middleware('permission:adjustments.create');
    Route::post('/adjustments', [AdjustmentController::class, 'store'])->name('adjustments.store')->middleware('permission:adjustments.create');
    Route::get('/adjustments/{adjustment}', [AdjustmentController::class, 'show'])->name('adjustments.show')->middleware('permission:transactions.view');
    Route::post('/adjustments/{adjustment}/approve', [AdjustmentController::class, 'approve'])->name('adjustments.approve')->middleware('permission:adjustments.approve');
    Route::post('/adjustments/{adjustment}/reject', [AdjustmentController::class, 'reject'])->name('adjustments.reject')->middleware('permission:adjustments.approve');
    Route::post('/adjustments/{adjustment}/cancel', [AdjustmentController::class, 'cancel'])->name('adjustments.cancel')->middleware('permission:adjustments.create');

    // Stok opname
    Route::get('/stock-opnames', [StockOpnameController::class, 'index'])->name('stock-opnames.index')->middleware('permission:transactions.view');
    Route::post('/stock-opnames', [StockOpnameController::class, 'store'])->name('stock-opnames.store')->middleware('permission:opname.create');
    Route::get('/stock-opnames/{stockOpname}', [StockOpnameController::class, 'show'])->name('stock-opnames.show')->middleware('permission:transactions.view');
    Route::put('/stock-opnames/{stockOpname}', [StockOpnameController::class, 'update'])->name('stock-opnames.update')->middleware('permission:opname.create');
    Route::delete('/stock-opnames/{stockOpname}', [StockOpnameController::class, 'destroy'])->name('stock-opnames.destroy')->middleware('permission:opname.create');
    Route::post('/stock-opnames/{stockOpname}/approve', [StockOpnameController::class, 'approve'])->name('stock-opnames.approve')->middleware('permission:opname.approve');
    Route::post('/stock-opnames/{stockOpname}/reject', [StockOpnameController::class, 'reject'])->name('stock-opnames.reject')->middleware('permission:opname.approve');
    Route::get('/stock-opnames/{stockOpname}/pdf', [StockOpnameController::class, 'pdf'])->name('stock-opnames.pdf')->middleware('permission:reports.view');

    // Kartu stok & laporan
    Route::middleware('permission:reports.view')->group(function () {
        Route::get('/stock-card', StockCardController::class)->name('stock-card');
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/receipts', [ReportController::class, 'receipts'])->name('reports.receipts');
        Route::get('/reports/distributions', [ReportController::class, 'distributions'])->name('reports.distributions');
        Route::get('/reports/stock-position', [ReportController::class, 'stockPosition'])->name('reports.stock-position');
    });
});
