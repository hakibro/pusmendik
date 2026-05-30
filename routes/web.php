<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PusmendikController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PusmendikController::class, 'dashboard'])->name('dashboard');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/cek-pembayaran', [PusmendikController::class, 'paymentStatus'])->name('payments.status');
Route::get('/ajax/siswa', [PusmendikController::class, 'studentSearch'])->name('students.search');
Route::get('/jadwal-ujian', [PusmendikController::class, 'schedules'])->name('schedules.index');
Route::get('/ruangan-sesi', [PusmendikController::class, 'rooms'])->name('rooms.index');
Route::get('/pengawas', [PusmendikController::class, 'supervisors'])->name('supervisors.index');
Route::get('/live-ujian', [PusmendikController::class, 'liveExam'])->name('live.index');
Route::get('/kehadiran', [PusmendikController::class, 'attendance'])->name('attendance.index');

Route::middleware('data.user')->group(function () {
    Route::get('/siswa', [PusmendikController::class, 'students'])->name('students.index');
    Route::post('/siswa/sync', [PusmendikController::class, 'syncStudents'])->name('students.sync');
    Route::get('/siswa/{id}', [PusmendikController::class, 'studentDetail'])->name('students.show');
    Route::post('/siswa/{id}/rekomendasi', [PusmendikController::class, 'saveRecommendation'])->name('students.recommendation');
    Route::get('/siswa/{id}/rekomendasi/cetak', [PusmendikController::class, 'printRecommendation'])->name('students.print');
    Route::get('/users-data', [PusmendikController::class, 'users'])->name('users.index');
    Route::get('/settings', [PusmendikController::class, 'settings'])->name('settings.index');
    Route::post('/settings', [PusmendikController::class, 'saveSettings'])->name('settings.store');
});
