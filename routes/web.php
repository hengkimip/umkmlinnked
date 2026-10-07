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
use App\Http\Controllers\Admin\DuplikatController;
use App\Http\Controllers\Admin\AksesController;
use App\Http\Controllers\Admin\BeritaController as AdminBeritaController;
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
            ->where('slug', '[a-z0-9-]{1,140}')
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
})->where('slug', '[A-Za-z0-9-]+')->middleware('throttle:120,1');

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
        // Dashboard Admin OPD. Super Admin memakai /superadmin/dashboard (alamat lama dialihkan)
        Route::get('/dashboard', fn (Request $request) => $request->user()->isSuperAdmin()
            ? redirect()->route('superadmin.dashboard')
            : view('admin.dashboard'))->name('dashboard');

        // Import Excel (FR-15) — Admin OPD dibatasi wilayahnya di controller
        Route::get('/import', [ImportController::class, 'index'])->name('import.index');
        Route::post('/import', [ImportController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('import.store');
        Route::get('/import/template', [ImportController::class, 'template'])->name('import.template');
        // Tambah satu UMKM secara manual (kolom sama dengan Kelola Profil UMKM)
        Route::post('/import/manual', [ImportController::class, 'manual'])
            ->middleware('throttle:30,1')
            ->name('import.manual');

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
        // Antrean review kemungkinan duplikat (import & tambah manual)
        Route::get('/duplikat', [DuplikatController::class, 'index'])->name('duplikat.index');
        Route::middleware('throttle:60,1')->whereNumber('duplikat')->group(function () {
            Route::post('/duplikat/{duplikat}/sama', [DuplikatController::class, 'sama'])->name('duplikat.sama');
            Route::post('/duplikat/{duplikat}/berbeda', [DuplikatController::class, 'berbeda'])->name('duplikat.berbeda');
            Route::delete('/duplikat/{duplikat}', [DuplikatController::class, 'hapus'])->name('duplikat.hapus');
        });

        Route::delete('/profil-umkm/{umkm:id}', [ProfilUmkmController::class, 'destroy'])
            ->middleware('throttle:30,1')
            ->name('profil-umkm.destroy');
    });

// ==================== SUPER ADMIN ====================

Route::middleware(['auth', 'verified', 'role:super-admin'])
    ->prefix('superadmin')
    ->name('superadmin.')
    ->group(function () {
        // Dashboard Super Admin (isi sama, cakupan seluruh OPD — lihat view admin.dashboard)
        Route::get('/dashboard', fn () => view('admin.dashboard'))->name('dashboard');

        // Kelola Akses: akses OPD, batas admin per OPD, akun Super Admin (kuota BI)
        Route::get('/akses', [AksesController::class, 'index'])->name('akses.index');
        Route::middleware('throttle:30,1')->group(function () {
            Route::post('/akses/opd', [AksesController::class, 'tambahOpd'])->name('akses.opd.store');
            Route::patch('/akses/opd/{opd}', [AksesController::class, 'ubahOpd'])->whereNumber('opd')->name('akses.opd.update');
            Route::delete('/akses/opd/{opd}', [AksesController::class, 'hapusOpd'])->whereNumber('opd')->name('akses.opd.destroy');
            Route::post('/akses/pengguna', [AksesController::class, 'tambahPengguna'])->name('akses.pengguna.store');
            Route::patch('/akses/pengguna/{user}', [AksesController::class, 'ubahPengguna'])->whereNumber('user')->name('akses.pengguna.update');
            Route::patch('/akses/pengguna/{user}/opd', [AksesController::class, 'pindahOpd'])->whereNumber('user')->name('akses.pengguna.opd');
            Route::delete('/akses/pengguna/{user}', [AksesController::class, 'hapusPengguna'])->whereNumber('user')->name('akses.pengguna.destroy');
        });

        // Berita official yang tampil di halaman publik /berita
        Route::get('/berita', [AdminBeritaController::class, 'index'])->name('berita.index');
        Route::get('/berita/tulis', [AdminBeritaController::class, 'create'])->name('berita.create');
        Route::get('/berita/{berita}/ubah', [AdminBeritaController::class, 'edit'])->whereNumber('berita')->name('berita.edit');
        Route::middleware('throttle:30,1')->group(function () {
            Route::post('/berita', [AdminBeritaController::class, 'store'])->name('berita.store');
            Route::put('/berita/{berita}', [AdminBeritaController::class, 'update'])->whereNumber('berita')->name('berita.update');
            Route::delete('/berita/{berita}', [AdminBeritaController::class, 'destroy'])->whereNumber('berita')->name('berita.destroy');
        });
    });

// Peta interaktif (FR-08) di /peta-interaktif — TERBUKA untuk umum.
// Pengunjung menerima data publik saja (sama dengan Semua Brand); Super Admin menerima data lengkap.
// (nama rute "superadmin.*" dipertahankan agar pemanggil route() tidak berubah)
Route::middleware('throttle:120,1')
    ->prefix('peta-interaktif')
    ->name('superadmin.peta-interaktif')
    ->group(function () {
        Route::get('/', [BiMapController::class, 'index']);
        Route::get('/data', [BiMapController::class, 'dataJson'])->name('.data');
    });

// Detail UMKM + rekomendasi program: Super Admin (semua) & Admin OPD (UMKM binaannya, dicek di controller)
Route::middleware(['auth', 'verified', 'role:super-admin|admin-opd'])
    ->get('/peta-interaktif/umkm/{umkm:id}', [BiMapController::class, 'show'])
    ->name('superadmin.peta-interaktif.umkm');

// Alamat lama peta interaktif → alamat baru (tautan/bookmark lama tetap jalan)
Route::middleware('throttle:120,1')->group(function () {
    foreach (['admin', 'superadmin'] as $lama) {
        Route::permanentRedirect("/{$lama}/peta-interaktif", '/peta-interaktif');
        Route::get("/{$lama}/peta-interaktif/umkm/{id}", fn (string $id) => redirect("/peta-interaktif/umkm/{$id}", 301))
            ->whereNumber('id');
    }
});

require __DIR__ . '/auth.php';
