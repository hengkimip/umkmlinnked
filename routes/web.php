<?php

use Illuminate\Http\Request;
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
use App\Http\Controllers\Admin\ProfilUmkmController;
use App\Http\Controllers\Admin\BiMapController;
use App\Http\Controllers\ProfileController;

// ==================== PUBLIK (tanpa login) ====================

// Batas laju per IP untuk halaman publik (menahan scraping/flood; pengunjung biasa jauh di bawahnya)
Route::middleware('throttle:120,1')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');

    Route::get('/go-global', [GoGlobalController::class, 'index'])->name('goglobal.index');
    Route::get('/go-digital', [GoDigitalController::class, 'index'])->name('godigital.index');

    // Semua Brand (dulu /direktori — nama rute "direktori.*" dipertahankan)
    Route::prefix('semua-brand')->name('direktori.')->group(function () {
        Route::get('/', [UmkmController::class, 'index'])->name('index');
        Route::get('/{umkm:slug}', [UmkmController::class, 'show'])
            ->where('umkm', '[A-Za-z0-9-]+')
            ->name('show');
    });

    Route::prefix('berita')->name('berita.')->group(function () {
        Route::get('/', [BeritaController::class, 'index'])->name('index');
        Route::get('/{slug}', [BeritaController::class, 'show'])
            ->where('slug', '[a-z0-9-]{1,120}')
            ->name('show');
    });

    Route::get('/kemitraan', [KemitraanController::class, 'index'])->name('kemitraan.index');
    Route::get('/tentang-kami', [TentangController::class, 'index'])->name('tentang.index');
});

// Alamat lama /direktori → /semua-brand (tautan & bookmark lama tetap jalan, filter ikut terbawa)
Route::get('/direktori/{slug?}', function (Request $request, ?string $slug = null) {
    $tujuan = '/semua-brand' . ($slug ? '/' . $slug : '');
    $query  = $request->getQueryString();

    return redirect($tujuan . ($query ? '?' . $query : ''), 301);
})->where('slug', '[A-Za-z0-9-]+');

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

        // Ubah data & hapus foto/keterangan produk yang tampil di halaman direktori
        Route::middleware('throttle:60,1')->where(['produk' => '[0-9]+'])->group(function () {
            Route::patch('/produk/{produk}', [ProdukFotoController::class, 'update'])->name('produk.update');
            Route::delete('/produk/{produk}', [ProdukFotoController::class, 'destroy'])->name('produk.destroy');
            Route::delete('/produk/{produk}/foto', [ProdukFotoController::class, 'destroyFoto'])->name('produk.foto.destroy');
            Route::delete('/produk/{produk}/keterangan', [ProdukFotoController::class, 'destroyKeterangan'])->name('produk.keterangan.destroy');
        });

        // Kelola profil UMKM: ubah/hapus per kolom — Admin OPD hanya UMKM binaannya (FR-02)
        Route::get('/profil-umkm', [ProfilUmkmController::class, 'index'])->name('profil-umkm.index');
        Route::patch('/profil-umkm/{umkm:id}', [ProfilUmkmController::class, 'update'])
            ->middleware('throttle:120,1')
            ->name('profil-umkm.update');
    });

// ==================== SUPER ADMIN ====================

Route::middleware(['auth', 'verified', 'role:super-admin'])
    ->prefix('superadmin')
    ->name('superadmin.')
    ->group(function () {
        // Peta interaktif (FR-08)
        Route::get('/peta-interaktif', [BiMapController::class, 'index'])->name('peta-interaktif');
        Route::get('/peta-interaktif/data', [BiMapController::class, 'dataJson'])->name('peta-interaktif.data');
        Route::get('/peta-interaktif/umkm/{umkm:id}', [BiMapController::class, 'show'])->name('peta-interaktif.umkm');
    });

// Alamat lama peta interaktif → alamat baru (tautan/bookmark lama tetap jalan)
Route::permanentRedirect('/admin/peta-interaktif', '/superadmin/peta-interaktif');
Route::get('/admin/peta-interaktif/umkm/{id}', fn (string $id) => redirect("/superadmin/peta-interaktif/umkm/{$id}", 301))
    ->whereNumber('id');

require __DIR__ . '/auth.php';
