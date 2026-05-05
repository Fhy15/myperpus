<?php


use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Pengguna\DashboardController;
use App\Http\Controllers\Pengguna\KatalogController;
use App\Http\Controllers\Pengguna\PeminjamanController as PenggunaPeminjamanController;
use App\Http\Controllers\Pengguna\BacaOnlineController;
use App\Http\Controllers\Pengguna\ProfileController;
use App\Http\Controllers\Pengguna\UlasanController;
use App\Http\Controllers\Pengguna\PetugasController as PenggunaPetugasController;
use App\Http\Controllers\Petugas\DashboardController as PetugasDashboardController;
use App\Http\Controllers\Petugas\PeminjamanController as PetugasPeminjamanController;
use App\Http\Controllers\Petugas\BukuController as PetugasBukuController;
use App\Http\Controllers\Petugas\AnggotaController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\BukuController as AdminBukuController;
use App\Http\Controllers\Admin\AnggotaController as AdminAnggotaController;
use App\Http\Controllers\Admin\PetugasController as AdminPetugasController;
use App\Http\Controllers\Admin\PeminjamanController as AdminPeminjamanController;
use App\Http\Controllers\Admin\KategoriController;
use App\Http\Controllers\Admin\StatistikController;
use App\Http\Controllers\Admin\LaporanBukuController as AdminLaporanBukuController;
use App\Http\Controllers\Api\ReadingProgressController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ============================================================
// PUBLIC ROUTES
// ============================================================

// Health check endpoint for Railway monitoring
Route::get('/health', function () {
    try {
        // Test DB connection
        \DB::connection()->getPdo();
        $dbStatus = 'OK';
    } catch (Exception $e) {
        $dbStatus = 'ERROR: ' . $e->getMessage();
    }
    
    return response()->json([
        'status' => 'OK',
        'env' => app()->environment(),
        'debug' => config('app.debug'),
        'db' => $dbStatus,
        'cache' => config('cache.default'),
        'timestamp' => now()->toISOString()
    ]);
});

Route::get('/', function () {
    if (auth()->check()) {
        $user = auth()->user();
        if ($user->hasRole('admin'))   return redirect('/admin/dashboard');
        if ($user->hasRole('petugas')) return redirect('/petugas/dashboard');
        return redirect('/dashboard');
    }
    return Inertia::render('Welcome');
});

// Syarat & Ketentuan (publik)
Route::get('/syarat', function () {
    return Inertia::render('Syarat');
})->name('syarat');

// ============================================================
// AUTH ROUTES
// ============================================================

Route::middleware('guest')->group(function () {
    Route::get('/login',    [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login',   [AuthenticatedSessionController::class, 'store']);
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register',[RegisteredUserController::class, 'store']);
    Route::get('/forgot-password',  [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout')->middleware('auth');

// ============================================================
// PENGGUNA / ANGGOTA ROUTES
// ============================================================

Route::middleware(['auth', 'role:pengguna|petugas|admin'])->group(function () {

    // Dashboard Pengguna
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Katalog
    Route::get('/katalog',          [KatalogController::class, 'index'])->name('katalog.index');
    Route::get('/katalog/{buku}',   [KatalogController::class, 'show'])->name('katalog.show');

    // Ulasan (JSON endpoints)
    Route::get('/ulasan', [UlasanController::class, 'index'])->name('ulasan.index');
    Route::post('/ulasan', [UlasanController::class, 'store'])->name('ulasan.store');
    Route::patch('/ulasan/{ulasan}', [UlasanController::class, 'update'])->name('ulasan.update');
    Route::delete('/ulasan/{ulasan}', [UlasanController::class, 'destroy'])->name('ulasan.destroy');

    // Baca Online
    Route::get('/baca-online',            [BacaOnlineController::class, 'index'])->name('baca-online.index');
    Route::get('/baca-online/{buku}/pdf', [BacaOnlineController::class, 'pdf'])->name('baca-online.pdf');
    Route::get('/baca-online/{buku}',     [BacaOnlineController::class, 'show'])->name('baca-online.show');

    // Peminjaman Pengguna
    Route::get('/peminjaman',             [PenggunaPeminjamanController::class, 'index'])->name('peminjaman.index');
    Route::post('/peminjaman',            [PenggunaPeminjamanController::class, 'store'])->name('peminjaman.store');
    Route::get('/peminjaman/{peminjaman}',[PenggunaPeminjamanController::class, 'show'])->name('peminjaman.show');
    Route::delete('/peminjaman/{peminjaman}', [PenggunaPeminjamanController::class, 'cancel'])->name('peminjaman.cancel');
    Route::post('/peminjaman/{peminjaman}/ajukan-kembali', [PenggunaPeminjamanController::class, 'ajukanKembali'])->name('peminjaman.ajukan_kembali');
    Route::get('/peminjaman/{peminjaman}/cetak-struk-return', [\App\Http\Controllers\Pengguna\PeminjamanController::class, 'cetakStrukReturn'])->name('peminjaman.cetak_struk_return');
    Route::get('/peminjaman/{peminjaman}/cetak-struk', [\App\Http\Controllers\Pengguna\PeminjamanController::class, 'cetakStruk'])->name('peminjaman.cetak_struk');

    // Riwayat
    Route::get('/riwayat', [PenggunaPeminjamanController::class, 'riwayat'])->name('riwayat.index');

    // Profil
    Route::get('/profile',  [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile',  [ProfileController::class, 'update'])->name('profile.update');

    // Halaman Riwayat Ulasan Pengguna
    Route::get('/ulasan-saya', [\App\Http\Controllers\Pengguna\UlasanPageController::class, 'index'])->name('ulasan.saya');

    // Petugas Directory (semua role bisa lihat)
    Route::get('/petugas-perpustakaan', [PenggunaPetugasController::class, 'index'])->name('petugas.index');

    // Notifications (AJAX)
    Route::get('/notifications', [\App\Http\Controllers\NotificationsController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/mark-read', [\App\Http\Controllers\NotificationsController::class, 'markRead'])->name('notifications.mark_read');

    // Kartu (unduh PDF)
    Route::get('/kartu/{user}', [\App\Http\Controllers\KartuController::class, 'download'])->name('kartu.download');

    // API - Reading Progress
    Route::post('/api/baca-progress', [ReadingProgressController::class, 'store']);
});

// ============================================================
// PETUGAS ROUTES
// ============================================================

Route::middleware(['auth', 'role:petugas|admin'])->prefix('petugas')->name('petugas.')->group(function () {

    Route::get('/dashboard', [PetugasDashboardController::class, 'index'])->name('dashboard');

    // Kelola Peminjaman
    Route::get('/peminjaman',                           [PetugasPeminjamanController::class, 'index'])->name('peminjaman.index');
    Route::get('/peminjaman/{peminjaman}',              [PetugasPeminjamanController::class, 'show'])->name('peminjaman.show');
    Route::post('/peminjaman/{peminjaman}/setujui',     [PetugasPeminjamanController::class, 'setujui'])->name('peminjaman.setujui');
    Route::post('/peminjaman/{peminjaman}/tolak',       [PetugasPeminjamanController::class, 'tolak'])->name('peminjaman.tolak');
    Route::post('/peminjaman/{peminjaman}/serahkan',    [PetugasPeminjamanController::class, 'serahkan'])->name('peminjaman.serahkan');
    Route::post('/peminjaman/{peminjaman}/kembalikan',  [PetugasPeminjamanController::class, 'kembalikan'])->name('peminjaman.kembalikan');

    // Kelola Buku (read + stok)
    Route::get('/buku',       [PetugasBukuController::class, 'index'])->name('buku.index');
    Route::patch('/buku/{buku}/stok', [PetugasBukuController::class, 'updateStok'])->name('buku.stok');

    // Anggota
    Route::get('/anggota',         [AnggotaController::class, 'index'])->name('anggota.index');
    Route::get('/anggota/{user}',  [AnggotaController::class, 'show'])->name('anggota.show');

    // Laporan
    Route::get('/laporan', [PetugasDashboardController::class, 'laporan'])->name('laporan');
    Route::post('/laporan', [PetugasDashboardController::class, 'kirimLaporan'])->name('laporan.kirim');
});

// ============================================================
// ADMIN ROUTES
// ============================================================

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Manajemen Buku
    Route::resource('buku', AdminBukuController::class)->names([
        'index'   => 'buku.index',
        'create'  => 'buku.create',
        'store'   => 'buku.store',
        'show'    => 'buku.show',
        'edit'    => 'buku.edit',
        'update'  => 'buku.update',
        'destroy' => 'buku.destroy',
    ]);

    // Kategori
    Route::resource('kategori', KategoriController::class)->names([
        'index'   => 'kategori.index',
        'store'   => 'kategori.store',
        'update'  => 'kategori.update',
        'destroy' => 'kategori.destroy',
    ])->except(['create', 'edit', 'show']);

    // Anggota
    Route::get('/anggota',               [AdminAnggotaController::class, 'index'])->name('anggota.index');
    Route::get('/anggota/{user}',        [AdminAnggotaController::class, 'show'])->name('anggota.show');
    Route::patch('/anggota/{user}/status',[AdminAnggotaController::class, 'updateStatus'])->name('anggota.status');
    Route::delete('/anggota/{user}',     [AdminAnggotaController::class, 'destroy'])->name('anggota.destroy');

    // Petugas
    Route::get('/petugas',              [AdminPetugasController::class, 'index'])->name('petugas.index');
    Route::post('/petugas',             [AdminPetugasController::class, 'store'])->name('petugas.store');
    Route::put('/petugas/{petugas}',    [AdminPetugasController::class, 'update'])->name('petugas.update');
    Route::delete('/petugas/{petugas}', [AdminPetugasController::class, 'destroy'])->name('petugas.destroy');

    // Peminjaman
    Route::get('/peminjaman',                          [AdminPeminjamanController::class, 'index'])->name('peminjaman.index');
    Route::get('/peminjaman/{peminjaman}',             [AdminPeminjamanController::class, 'show'])->name('peminjaman.show');
    Route::post('/peminjaman/{peminjaman}/setujui',    [AdminPeminjamanController::class, 'setujui'])->name('peminjaman.setujui');
    Route::post('/peminjaman/{peminjaman}/kembalikan', [AdminPeminjamanController::class, 'kembalikan'])->name('peminjaman.kembalikan');
    Route::delete('/peminjaman/{peminjaman}',          [AdminPeminjamanController::class, 'destroy'])->name('peminjaman.destroy');

    // Statistik & Laporan
    Route::get('/statistik',           [StatistikController::class, 'index'])->name('statistik.index');
    Route::get('/statistik/export',    [StatistikController::class, 'export'])->name('statistik.export');
    Route::get('/statistik/export/users', [StatistikController::class, 'exportUsers'])->name('statistik.export.users');
    Route::get('/statistik/export/buku', [StatistikController::class, 'exportBuku'])->name('statistik.export.buku');
    Route::get('/laporan-buku',        [AdminLaporanBukuController::class, 'index'])->name('laporan-buku.index');
    Route::post('/laporan-buku/{laporanBuku}/tinjau', [AdminLaporanBukuController::class, 'tinjau'])->name('laporan-buku.tinjau');

    // Pengaturan
    Route::get('/pengaturan', function () {
        return Inertia::render('Admin/Pengaturan');
    })->name('pengaturan');

    // Pengaturan - API
    Route::get('/pengaturan/data', [\App\Http\Controllers\Admin\SettingController::class, 'index'])->name('pengaturan.data');
    Route::post('/pengaturan', [\App\Http\Controllers\Admin\SettingController::class, 'update'])->name('pengaturan.update');

    // Kelola Ulasan
    Route::get('/ulasan', [\App\Http\Controllers\Admin\UlasanController::class, 'index'])->name('ulasan.index');
    Route::patch('/ulasan/{ulasan}', [\App\Http\Controllers\Admin\UlasanController::class, 'update'])->name('ulasan.update');
    Route::delete('/ulasan/{ulasan}', [\App\Http\Controllers\Admin\UlasanController::class, 'destroy'])->name('ulasan.destroy');
});
