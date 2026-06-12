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
Route::get('/ajax/rekom-siswa', [PusmendikController::class, 'rekomStudentSearch'])->name('students.rekom-search');
Route::get('/jadwal-ujian', [PusmendikController::class, 'schedules'])->name('schedules.index');
Route::get('/ruangan-sesi', [PusmendikController::class, 'rooms'])->name('rooms.index');
Route::get('/pengawas', [PusmendikController::class, 'supervisors'])->name('supervisors.index');
Route::get('/live-ujian', [PusmendikController::class, 'liveExam'])->name('live.index');
Route::get('/kehadiran', [PusmendikController::class, 'attendance'])->name('attendance.index');
Route::get('/hasil-ujian', [PusmendikController::class, 'examResults'])->name('results.index');
Route::get('/hasil-ujian/download', [PusmendikController::class, 'downloadExamResults'])->name('results.download');
Route::get('/panduan', [PusmendikController::class, 'guides'])->name('guides.index');
Route::get('/panduan/{guide}/ajax', [PusmendikController::class, 'getGuideAjax'])->name('guides.ajax')->whereNumber('guide');

Route::middleware('data.user')->group(function () {
    Route::get('/siswa', [PusmendikController::class, 'students'])->name('students.index');
    Route::get('/siswa/{id}', [PusmendikController::class, 'studentDetail'])->name('students.show');
    Route::post('/siswa/{id}/rekomendasi', [PusmendikController::class, 'saveRecommendation'])->name('students.recommendation');
    Route::get('/siswa/{id}/rekomendasi/cetak', [PusmendikController::class, 'printRecommendation'])->name('students.print');
    Route::get('/settings', [PusmendikController::class, 'settings'])->name('settings.index');
    Route::post('/settings', [PusmendikController::class, 'saveSettings'])->name('settings.store');
    Route::get('/settings/panduan', [PusmendikController::class, 'guideEditor'])->name('settings.guides.index');
    Route::post('/settings/panduan', [PusmendikController::class, 'storeGuide'])->name('settings.guides.store');
    Route::post('/settings/panduan/preview', [PusmendikController::class, 'previewGuideMarkdown'])->name('settings.guides.preview');
    Route::put('/settings/panduan/{guide}', [PusmendikController::class, 'updateGuide'])->name('settings.guides.update')->whereNumber('guide');
    Route::delete('/settings/panduan/{guide}', [PusmendikController::class, 'deleteGuide'])->name('settings.guides.delete')->whereNumber('guide');
    Route::post('/settings/panduan/upload-image', [PusmendikController::class, 'uploadGuideImage'])->name('settings.guides.upload-image');
    Route::post('/settings/panduan/upload-image-ajax', [PusmendikController::class, 'uploadGuideImageAjax'])->name('settings.guides.upload-image-ajax');
    Route::post('/settings/panduan/{guide}/attachments', [PusmendikController::class, 'storeGuideAttachment'])->name('settings.guides.attachments.store')->whereNumber('guide');
    Route::delete('/settings/panduan/attachments/{attachment}', [PusmendikController::class, 'deleteGuideAttachment'])->name('settings.guides.attachments.delete')->whereNumber('attachment');
});
