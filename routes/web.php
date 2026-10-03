<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Public\UmkmController;
use App\Http\Controllers\Public\BeritaController;
use App\Http\Controllers\Public\KemitraanController;
use App\Http\Controllers\Public\TentangController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Public\GoDigitalController;
use App\Http\Controllers\Public\GoGlobalController;
use App\Http\Controllers\Admin\ProdukFotoController;
use App\Http\Controllers\Admin\BiMapController;
use App\Http\Controllers\ProfileController;

// ==================== PUBLIK (tanpa login) ====================

Route::get('/', [UmkmController::class, 'index'])->name('home');

Route::get('/go-global', [GoGlobalController::class, 'index'])->name('goglobal.index');
Route::get('/go-digital', [GoDigitalController::class, 'index'])->name('godigital.index');

Route::prefix('direktori')->name('direktori.')->group(function () {
    Route::get('/', [UmkmController::class, 'index'])->name('index');
    Route::get('/{umkm:slug}', [UmkmController::class, 'show'])->name('show');
});

Route::prefix('berita')->name('berita.')->group(function () {
    Route::get('/', [BeritaController::class, 'index'])->name('index');
    Route::get('/{slug}', [BeritaController::class, 'show'])->name('show');
});

Route::get('/kemitraan', [KemitraanController::class, 'index'])->name('kemitraan.index');
Route::get('/tentang-kami', [TentangController::class, 'index'])->name('tentang.index');

// ==================== ADMIN (super-admin & admin-opd) ====================

Route::get('/dashboard', fn (Request $request) => redirect($request->user()->homeUrl()))
    ->middleware(['auth'])
    ->name('dashboard');

// Profil & kata sandi sendiri. Hapus akun mandiri tidak disediakan:
// akun dikelola Super Admin dan jejak audit log harus utuh (FR-04).
Route::middleware(['auth', 'verified', 'role:super-admin|admin-opd'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'verified', 'role:super-admin|admin-opd'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', fn () => view('admin.dashboard'))->name('dashboard');

        // Import Excel (FR-15) — Admin OPD dibatasi wilayahnya di controller
        Route::get('/import', [ImportController::class, 'index'])->name('import.index');
        Route::post('/import', [ImportController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('import.store');
        Route::get('/import/template', [ImportController::class, 'template'])->name('import.template');

        // Upload foto produk — Admin OPD hanya UMKM binaannya (FR-02)
        Route::get('/produk/upload-foto', [ProdukFotoController::class, 'index'])->name('produk.upload-foto');
        Route::post('/produk/upload-foto', [ProdukFotoController::class, 'store'])
            ->middleware('throttle:30,1')
            ->name('produk.upload-foto.store');
        Route::get('/produk/list/{umkmId}', [ProdukFotoController::class, 'listProduk'])
            ->whereNumber('umkmId')
            ->name('produk.list');

        // Peta interaktif — hanya super-admin (FR-08)
        Route::middleware('role:super-admin')->group(function () {
            Route::get('/peta-interaktif', [BiMapController::class, 'index'])->name('peta-interaktif');
            Route::get('/peta-interaktif/data', [BiMapController::class, 'dataJson'])->name('peta-interaktif.data');
        });
    });

require __DIR__ . '/auth.php';
