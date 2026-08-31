<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ExecutiveDashboardService;
use Illuminate\View\View;

class ExecutiveDashboardController extends Controller
{
    public function __invoke(ExecutiveDashboardService $dashboard): View
    {
        return view('admin.executive.index', $dashboard->build());
    }
}
