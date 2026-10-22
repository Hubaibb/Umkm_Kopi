<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\BomController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\AccountController;

// ─── Auth ───────────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// ─── Redirect root ──────────────────────────────────────────────────────────
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->to(auth()->user()->defaultRoute());
    }

    return redirect()->route('login');
});

// ─── Protected routes ───────────────────────────────────────────────────────
Route::middleware(['auth', 'active.account'])->group(function () {

    // POS / Transaksi
    Route::prefix('pos')->name('pos.')->middleware('permission:pos.create')->group(function () {
        Route::get('/', [PosController::class, 'index'])->name('index');
        Route::post('/checkout', [PosController::class, 'checkout'])->name('checkout');
    });

    // Bahan Baku & Stok
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', [InventoryController::class, 'index'])->name('index')->middleware('permission:inventory.view');
        Route::post('/', [InventoryController::class, 'store'])->name('store')->middleware('permission:inventory.manage');
        Route::put('/{material}', [InventoryController::class, 'update'])->name('update')->middleware('permission:inventory.manage');
        Route::delete('/{material}', [InventoryController::class, 'destroy'])->name('destroy')->middleware('permission:inventory.manage');
        Route::post('/adjust', [InventoryController::class, 'adjust'])->name('adjust')->middleware('permission:inventory.adjust');
    });

    // Resep / BOM + Menu CRUD
    Route::prefix('bom')->name('bom.')->group(function () {
        Route::get('/', [BomController::class, 'index'])->name('index')->middleware('permission:bom.view');
        Route::post('/simulate', [BomController::class, 'simulate'])->name('simulate')->middleware('permission:bom.view');

        // BOM items
        Route::post('/items', [BomController::class, 'storeBomItem'])->name('items.store')->middleware('permission:bom.manage');
        Route::put('/items/{recipeItem}', [BomController::class, 'updateBomItem'])->name('items.update')->middleware('permission:bom.manage');
        Route::delete('/items/{recipeItem}', [BomController::class, 'destroyBomItem'])->name('items.destroy')->middleware('permission:bom.manage');

        // Menu CRUD
        Route::post('/menus', [BomController::class, 'storeMenu'])->name('menus.store')->middleware('permission:menu.manage');
        Route::put('/menus/{menu}', [BomController::class, 'updateMenu'])->name('menus.update')->middleware('permission:menu.manage');
        Route::delete('/menus/{menu}', [BomController::class, 'destroyMenu'])->name('menus.destroy')->middleware('permission:menu.manage');

        // Kategori
        Route::post('/categories', [BomController::class, 'storeCategory'])->name('categories.store')->middleware('permission:menu.manage');
    });

    // Analitik Penjualan
    Route::prefix('reports')->name('reports.')->middleware('permission:reports.view')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/export', [ReportController::class, 'export'])->name('export')->middleware('permission:reports.export');
    });

    // Riwayat Transaksi
    Route::prefix('transactions')->name('transactions.')->middleware('permission:transactions.view')->group(function () {
        Route::get('/', [TransactionController::class, 'index'])->name('index');
        Route::get('/{sale}', [TransactionController::class, 'show'])->name('show');
    });

    // Manajemen Akun
    Route::prefix('accounts')->name('accounts.')->group(function () {
        Route::get('/', [AccountController::class, 'index'])->name('index')->middleware('permission:accounts.view');
        Route::post('/', [AccountController::class, 'store'])->name('store')->middleware('permission:accounts.create');
        Route::put('/{user}', [AccountController::class, 'update'])->name('update')->middleware('permission:accounts.update');
        Route::post('/{user}/reset-password', [AccountController::class, 'resetPassword'])->name('reset-password')->middleware('permission:accounts.update');
        Route::post('/{user}/toggle-active', [AccountController::class, 'toggleActive'])->name('toggle-active')->middleware('permission:accounts.delete');
    });
});
