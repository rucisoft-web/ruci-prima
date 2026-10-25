<?php

use App\Http\Controllers\GuestLoginController;
use App\Http\Controllers\QrController;
use Illuminate\Support\Facades\Route;

// Tamu melihat halaman awal; pengguna yang sudah masuk langsung ke aplikasi.
Route::get('/', fn () => auth()->check() ? redirect('/app') : view('fitur'))->name('home');
Route::get('/login', fn () => redirect('/app/login'))->name('login');

// Penjelasan fitur untuk calon pengguna, dan pintu masuk tamu ke data contoh.
Route::view('/fitur', 'fitur')->name('features');
Route::get('/tamu', GuestLoginController::class)->name('guest.enter')->middleware('throttle:30,1');

// Halaman publik hasil pindai QR aset.
Route::get('/qr/{token}', [QrController::class, 'show'])->name('qr.show')->where('token', '[A-Za-z0-9]{20,64}');
Route::post('/qr/{token}/lapor', [QrController::class, 'report'])->name('qr.report')->middleware('throttle:qr-report')->where('token', '[A-Za-z0-9]{20,64}');

Route::middleware('auth')->group(function () {
    Route::get('/label-qr', [QrController::class, 'labels'])->name('qr.labels');
    Route::view('/tanpa-perusahaan', 'no-company')->name('no-company');
});
