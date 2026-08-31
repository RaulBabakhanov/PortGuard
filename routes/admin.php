<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AllowedNetworkController;
use App\Http\Controllers\Admin\ApprovalController;
use App\Http\Controllers\Admin\AssetController;
use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CveController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\ExecutiveDashboardController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ScanController;
use App\Http\Controllers\Admin\ScheduledController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\TargetController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('yonetim')->name('admin.')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('/giris', [AuthController::class, 'create'])->name('login');
        Route::post('/giris', [AuthController::class, 'store'])->middleware('throttle:8,1')->name('login.store');
    });

    Route::middleware('auth:admin')->group(function () {
        Route::post('/cikis', [AuthController::class, 'destroy'])->name('logout');
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('/yonetici-ozeti', ExecutiveDashboardController::class)->name('executive');

        Route::get('/kullanicilar', [UserController::class, 'index'])->name('users.index');
        Route::get('/kullanicilar/yeni', [UserController::class, 'create'])->name('users.create');
        Route::post('/kullanicilar', [UserController::class, 'store'])->name('users.store');
        Route::get('/kullanicilar/{user}', [UserController::class, 'show'])->name('users.show');
        Route::get('/kullanicilar/{user}/duzenle', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/kullanicilar/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('/kullanicilar/{user}/sifre', [UserController::class, 'updatePassword'])->name('users.password');
        Route::delete('/kullanicilar/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('/birimler', [DepartmentController::class, 'index'])->name('departments.index');
        Route::post('/birimler', [DepartmentController::class, 'store'])->name('departments.store');
        Route::put('/birimler/{department}', [DepartmentController::class, 'update'])->name('departments.update');
        Route::delete('/birimler/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');

        Route::get('/izinli-aglar', [AllowedNetworkController::class, 'index'])->name('networks.index');
        Route::post('/izinli-aglar', [AllowedNetworkController::class, 'store'])->name('networks.store');
        Route::put('/izinli-aglar/{network}', [AllowedNetworkController::class, 'update'])->name('networks.update');
        Route::post('/izinli-aglar/{network}/toggle', [AllowedNetworkController::class, 'toggle'])->name('networks.toggle');
        Route::delete('/izinli-aglar/{network}', [AllowedNetworkController::class, 'destroy'])->name('networks.destroy');

        Route::get('/varliklar', [AssetController::class, 'index'])->name('assets.index');
        Route::post('/varliklar', [AssetController::class, 'store'])->name('assets.store');
        Route::put('/varliklar/{asset}', [AssetController::class, 'update'])->name('assets.update');
        Route::delete('/varliklar/{asset}', [AssetController::class, 'destroy'])->name('assets.destroy');

        Route::get('/onaylar', [ApprovalController::class, 'index'])->name('approvals.index');
        Route::post('/onaylar/{scan}/onayla', [ApprovalController::class, 'approve'])->name('approvals.approve');
        Route::post('/onaylar/{scan}/reddet', [ApprovalController::class, 'reject'])->name('approvals.reject');

        Route::get('/loglar', [ActivityLogController::class, 'index'])->name('logs.index');
        Route::get('/denetim', [AuditController::class, 'index'])->name('audit.index');
        Route::post('/denetim/ayarlar', [AuditController::class, 'updateSettings'])->name('audit.settings');
        Route::get('/karsilastirma', [AuditController::class, 'comparison'])->name('audit.comparison');

        Route::get('/taramalar', [ScanController::class, 'index'])->name('scans.index');
        Route::get('/taramalar/{scan}', [ScanController::class, 'show'])->name('scans.show');
        Route::post('/taramalar/{scan}/pdf', [ScanController::class, 'storePdf'])->name('scans.pdf.store');
        Route::get('/taramalar/{scan}/pdf', [ScanController::class, 'pdf'])->name('scans.pdf');
        Route::delete('/taramalar/{scan}', [ScanController::class, 'destroy'])->name('scans.destroy');

        Route::get('/hedefler', [TargetController::class, 'index'])->name('targets.index');
        Route::get('/zamanlanmis', [ScheduledController::class, 'index'])->name('scheduled.index');
        Route::get('/servisler', [ServiceController::class, 'index'])->name('services.index');
        Route::get('/cve-bulgulari', [CveController::class, 'index'])->name('cves.index');
        Route::get('/raporlar', [ReportController::class, 'index'])->name('reports.index');

        Route::get('/adminler', [AdminUserController::class, 'index'])->name('admins.index');
        Route::post('/adminler', [AdminUserController::class, 'store'])->name('admins.store');
        Route::put('/adminler/{admin}', [AdminUserController::class, 'update'])->name('admins.update');
        Route::post('/adminler/{admin}/toggle', [AdminUserController::class, 'toggle'])->name('admins.toggle');
        Route::post('/adminler/{admin}/sifre', [AdminUserController::class, 'updatePassword'])->name('admins.password');
        Route::delete('/adminler/{admin}', [AdminUserController::class, 'destroy'])->name('admins.destroy');
    });
});
