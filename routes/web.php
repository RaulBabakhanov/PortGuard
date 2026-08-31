<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\ScheduledScanController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TargetController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/taramalar', [ScanController::class, 'index'])->name('scans.index');
    Route::get('/taramalar/yeni', [ScanController::class, 'create'])->name('scans.create');
    Route::post('/taramalar', [ScanController::class, 'store'])->middleware('throttle:10,1')->name('scans.store');
    Route::get('/taramalar/{scan}', [ScanController::class, 'show'])->name('scans.show');
    Route::get('/taramalar/{scan}/pdf', [ScanController::class, 'pdf'])->name('scans.pdf');
    Route::delete('/taramalar/{scan}', [ScanController::class, 'destroy'])->name('scans.destroy');

    Route::get('/hedefler', [TargetController::class, 'index'])->name('targets.index');
    Route::get('/hedefler/yeni', [TargetController::class, 'create'])->name('targets.create');
    Route::post('/hedefler', [TargetController::class, 'store'])->name('targets.store');
    Route::get('/hedefler/{target}/duzenle', [TargetController::class, 'edit'])->name('targets.edit');
    Route::put('/hedefler/{target}', [TargetController::class, 'update'])->name('targets.update');
    Route::delete('/hedefler/{target}', [TargetController::class, 'destroy'])->name('targets.destroy');

    Route::get('/zamanlanmis', [ScheduledScanController::class, 'index'])->name('scheduled.index');
    Route::post('/zamanlanmis', [ScheduledScanController::class, 'store'])->name('scheduled.store');
    Route::post('/zamanlanmis/{scheduledScan}/toggle', [ScheduledScanController::class, 'toggle'])->name('scheduled.toggle');
    Route::post('/zamanlanmis/{scheduledScan}/calistir', [ScheduledScanController::class, 'runNow'])->middleware('throttle:5,1')->name('scheduled.run');
    Route::delete('/zamanlanmis/{scheduledScan}', [ScheduledScanController::class, 'destroy'])->name('scheduled.destroy');

    Route::get('/servisler', [AnalysisController::class, 'services'])->name('services.index');
    Route::get('/cve-bulgulari', [AnalysisController::class, 'cves'])->name('cves.index');
    Route::get('/raporlar', [AnalysisController::class, 'reports'])->name('reports.index');
    Route::get('/raporlar/export', [AnalysisController::class, 'export'])->name('reports.export');

    Route::get('/aktivite', [ActivityLogController::class, 'index'])->name('activity.index');

    Route::get('/ayarlar', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/ayarlar', [SettingsController::class, 'update'])->name('settings.update');

    Route::get('/bildirimler', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/bildirimler/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/bildirimler/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
