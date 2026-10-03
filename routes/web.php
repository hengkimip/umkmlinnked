<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\UmkmController;
use App\Http\Controllers\Public\BeritaController;
use App\Http\Controllers\Public\KemitraanController;
use App\Http\Controllers\Public\TentangController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Public\GoDigitalController;
use App\Http\Controllers\Public\GoGlobalController;
use App\Http\Controllers\Admin\ProdukFotoController;
use App\Http\Controllers\Admin\BiMapController;

Route::get('/produk/upload-foto', [ProdukFotoController::class, 'index'])->name('produk.upload-foto');
Route::post('/produk/upload-foto', [ProdukFotoController::class, 'store'])->name('produk.upload-foto.store');
Route::get('/produk/list/{umkmId}', [ProdukFotoController::class, 'listProduk'])->name('produk.list');

Route::get('/go-global', [GoGlobalController::class, 'index'])->name('goglobal.index');
Route::get('/go-digital', [GoDigitalController::class, 'index'])->name('godigital.index');

Route::get('/dashboard', function () {
    return redirect('/admin/dashboard');
})->middleware(['auth'])->name('dashboard');

Route::middleware(['auth', 'verified'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', function () {
            return view('admin.dashboard');
        })->name('dashboard');

        Route::get('/import', [ImportController::class, 'index'])->name('import.index');
        Route::post('/import', [ImportController::class, 'store'])->name('import.store');
        Route::get('/import/template', [ImportController::class, 'template'])->name('import.template');

        Route::get('/peta-interaktif', [BiMapController::class, 'index'])->name('peta-interaktif');
        Route::get('/peta-interaktif/data', [BiMapController::class, 'dataJson'])->name('peta-interaktif.data');
    });

Route::get('/', [UmkmController::class, 'index'])->name('home');

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

Route::middleware(['auth', 'verified'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', function () {
            return view('admin.dashboard');
        })->name('dashboard');
    });

require __DIR__ . '/auth.php';