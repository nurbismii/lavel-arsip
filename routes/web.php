<?php

use App\Http\Controllers\PekerjaanController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AlurKerjaController;
use App\Http\Controllers\AlurKerjaTahapController;
use App\Http\Controllers\LokasiDokumenController;
use App\Http\Controllers\JobdescController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SopPengetahuanController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Auth::routes();

Route::middleware('check.login')->group(function () {
    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/dokumen/{dokumen}/lihat', [PekerjaanController::class, 'lihatDokumen'])->name('dokumen.lihat');
    Route::get('/dokumen/{dokumen}/bukti-penyelesaian/{buktiPenyelesaian}', [PekerjaanController::class, 'lihatBuktiPenyelesaian'])->name('dokumen.bukti-penyelesaian.file');
    Route::get('/dokumen/{dokumen}/bukti-penyelesaian', [PekerjaanController::class, 'lihatBuktiPenyelesaian'])->name('dokumen.bukti-penyelesaian');
    Route::get('/pekerjaan/{pekerjaan}/tree-content', [PekerjaanController::class, 'treeContent'])->name('pekerjaan.tree-content');
    Route::delete('/pekerjaan/{pekerjaan}/dokumen/{dokumen}', [PekerjaanController::class, 'hapusDokumen'])->name('pekerjaan.dokumen.destroy');
    Route::patch('/pekerjaan/{pekerjaan}/dokumen/{dokumen}/status', [PekerjaanController::class, 'updateStatusDokumen'])->name('pekerjaan.dokumen.status');
    Route::post('/alur-kerja/{alurKerja}/tahap', [AlurKerjaTahapController::class, 'store'])->name('alur-kerja.tahap.store');
    Route::patch('/alur-kerja/{alurKerja}/tahap/{tahap}', [AlurKerjaTahapController::class, 'update'])->name('alur-kerja.tahap.update');
    Route::delete('/alur-kerja/{alurKerja}/tahap/{tahap}', [AlurKerjaTahapController::class, 'destroy'])->name('alur-kerja.tahap.destroy');
    Route::get('/alur-kerja/{alurKerja}/tahap/{tahap}/lampiran/{lampiran}', [AlurKerjaTahapController::class, 'showLampiran'])->name('alur-kerja.tahap.lampiran.show');
    Route::delete('/alur-kerja/{alurKerja}/tahap/{tahap}/lampiran/{lampiran}', [AlurKerjaTahapController::class, 'destroyLampiran'])->name('alur-kerja.tahap.lampiran.destroy');
    Route::resource('alur-kerja', AlurKerjaController::class);
    Route::post('/sop-pengetahuan/editor-image', [SopPengetahuanController::class, 'uploadEditorImage'])->name('sop-pengetahuan.editor-image.upload');
    Route::get('/sop-pengetahuan/{sopPengetahuan}/lampiran/{lampiran}', [SopPengetahuanController::class, 'showLampiran'])->name('sop-pengetahuan.lampiran.show');
    Route::delete('/sop-pengetahuan/{sopPengetahuan}/lampiran/{lampiran}', [SopPengetahuanController::class, 'destroyLampiran'])->name('sop-pengetahuan.lampiran.destroy');
    Route::resource('sop-pengetahuan', SopPengetahuanController::class);
    Route::resource('jobdesc', JobdescController::class);
    Route::resource('pekerjaan', PekerjaanController::class);
    Route::get('/lokasi-dokumen', [LokasiDokumenController::class, 'index'])->name('lokasi-dokumen.index');
    Route::get('/lokasi-dokumen/create', [LokasiDokumenController::class, 'create'])->name('lokasi-dokumen.create');
    Route::post('/lokasi-dokumen', [LokasiDokumenController::class, 'store'])->name('lokasi-dokumen.store');

    Route::middleware('admin')->group(function () {
        Route::get('/log-aktivitas', [ActivityLogController::class, 'index'])->name('activity-logs.index');
        Route::get('/lokasi-dokumen/{lokasi}/edit', [LokasiDokumenController::class, 'edit'])->name('lokasi-dokumen.edit');
        Route::put('/lokasi-dokumen/{lokasi}', [LokasiDokumenController::class, 'update'])->name('lokasi-dokumen.update');
        Route::delete('/lokasi-dokumen/{lokasi}', [LokasiDokumenController::class, 'destroy'])->name('lokasi-dokumen.destroy');
        Route::get('/tim-divisi', [TeamController::class, 'index'])->name('teams.index');
        Route::post('/tim-divisi', [TeamController::class, 'store'])->name('teams.store');
        Route::patch('/tim-divisi/{team}', [TeamController::class, 'update'])->name('teams.update');
        Route::delete('/tim-divisi/{team}', [TeamController::class, 'destroy'])->name('teams.destroy');
        Route::get('/kelola-user', [UserManagementController::class, 'index'])->name('users.index');
        Route::patch('/kelola-user/{user}', [UserManagementController::class, 'update'])->name('users.update');
    });
});
