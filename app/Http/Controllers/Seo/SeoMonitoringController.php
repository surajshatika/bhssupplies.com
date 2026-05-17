<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Services\Seo\Monitoring\SeoMonitoringService;
use Illuminate\Http\Request;

class SeoMonitoringController extends Controller
{
    public function __construct(protected SeoMonitoringService $monitor) {}

    public function index(Request $request)
    {
        $days = (int) $request->input('days', 30);
        $days = max(7, min($days, 90));

        $data = $this->monitor->snapshot($days);

        return view('backend.seo.monitoring.index', compact('data', 'days'));
    }
}
