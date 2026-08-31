<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Scan;
use App\Services\AdminActivityLogger;
use App\Services\ScanRunner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class ApprovalController extends Controller
{
    public function index(): View
    {
        $pending = Scan::query()
            ->with('user:id,name,email')
            ->where('status', 'awaiting_approval')
            ->latest('id')
            ->paginate(10);

        return view('admin.approvals.index', compact('pending'));
    }

    public function approve(Scan $scan, ScanRunner $runner, AdminActivityLogger $logger): RedirectResponse
    {
        if ($scan->status !== 'awaiting_approval') {
            return back()->withErrors(['approval' => 'Bu tarama onay bekleyen durumda değil.']);
        }

        $admin = Auth::guard('admin')->user();
        $scan->update([
            'status' => 'pending',
            'approval_status' => 'approved',
            'approved_by_admin_id' => $admin?->id,
            'approved_at' => now(),
            'rejected_at' => null,
            'rejection_reason' => null,
        ]);

        $logger->log('scan.approved', "Tarama onaylandı (#{$scan->id})", $scan);

        try {
            set_time_limit(0);
            $runner->run($scan);
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('admin.scans.show', $scan)
                ->withErrors(['approval' => 'Onaylandı ancak tarama çalıştırılırken hata oluştu.']);
        }

        return redirect()
            ->route('admin.scans.show', $scan)
            ->with('status', 'Tarama onaylandı ve çalıştırıldı.');
    }

    public function reject(Request $request, Scan $scan, AdminActivityLogger $logger): RedirectResponse
    {
        if ($scan->status !== 'awaiting_approval') {
            return back()->withErrors(['approval' => 'Bu tarama onay bekleyen durumda değil.']);
        }

        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:255'],
        ]);

        $scan->update([
            'status' => 'rejected',
            'approval_status' => 'rejected',
            'rejected_at' => now(),
            'rejection_reason' => $data['rejection_reason'],
        ]);

        $logger->log('scan.rejected', "Tarama reddedildi (#{$scan->id})", $scan, $data);

        return back()->with('status', 'Tarama reddedildi.');
    }
}
