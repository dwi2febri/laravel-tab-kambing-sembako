<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PortalController::class, 'home'])->name('home');
Route::get('/katalog', [PortalController::class, 'catalog'])->name('catalog');
Route::get('/chat/messages', [ChatController::class, 'index'])->middleware('throttle:180,1')->name('chat.index');
Route::post('/chat/messages', [ChatController::class, 'store'])->middleware('throttle:20,1')->name('chat.store');
Route::get('/produk/{product:slug}', [PortalController::class, 'product'])->name('products.show');
Route::view('/tentang-kami', 'about')->name('about');
Route::get('/syarat-ketentuan', [PortalController::class, 'terms'])->name('terms');
Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/masuk', [AuthController::class, 'login'])->middleware('throttle:6,1')->name('login.submit');
    Route::get('/daftar', [AuthController::class, 'registerForm'])->name('register');
    Route::post('/daftar', [AuthController::class, 'register'])->middleware('throttle:6,1')->name('register.submit');
    Route::get('/lupa-password', [AuthController::class, 'forgotForm'])->name('password.request');
    Route::post('/lupa-password', [AuthController::class, 'forgot'])->middleware('throttle:3,1')->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'reset'])->name('password.update');
});
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/laporan', [ReportController::class, 'preview'])->name('reports.preview');
    Route::get('/laporan/ekspor/{format}', [ReportController::class, 'export'])->name('reports.export');
    Route::post('/keluar', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [PortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/tabungan', [PortalController::class, 'savings'])->name('savings.index');
    Route::get('/tabungan/{saving}', [PortalController::class, 'saving'])->name('savings.show');
    Route::post('/produk/{product:slug}/menabung', [PortalController::class, 'start'])->name('savings.start');
    Route::get('/cicilan/{installment}/bayar', [PortalController::class, 'pay'])->name('payments.create');
    Route::post('/cicilan/{installment}/bayar', [PortalController::class, 'upload'])->middleware('throttle:10,1')->name('payments.store');
    Route::get('/riwayat', [PortalController::class, 'history'])->name('payments.index');
    Route::get('/bukti/{payment}', [PortalController::class, 'proof'])->name('payments.proof');
    Route::get('/notifikasi', [PortalController::class, 'notifications'])->name('notifications');
    Route::post('/notifikasi/{notification}/baca', [PortalController::class, 'read'])->name('notifications.read');
    Route::get('/profil', [PortalController::class, 'profile'])->name('profile');
    Route::put('/profil', [PortalController::class, 'updateProfile'])->name('profile.update');
    Route::post('/tabungan/{saving}/penyerahan', [PortalController::class, 'handover'])->name('handovers.request');
});
