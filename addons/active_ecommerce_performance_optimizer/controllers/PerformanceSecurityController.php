<?php

namespace App\Http\Controllers;

use App\Services\PerformanceOptimizer\SecurityAuditService;

class PerformanceSecurityController extends Controller
{
    protected SecurityAuditService $service;

    public function __construct(SecurityAuditService $service)
    {
        $this->middleware(['auth', 'admin']);
        $this->service = $service;
    }

    public function index()
    {
        $result = $this->service->run();
        return view('backend.performance_optimizer.index', [
            'tab'    => 'security',
            'audit'  => $result,
        ]);
    }

    public function run()
    {
        $this->service->run();
        flash(translate('Security audit refreshed.'))->success();
        return redirect()->route('performance_optimizer.security');
    }
}
