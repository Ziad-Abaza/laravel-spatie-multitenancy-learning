<?php

namespace Modules\Landlord\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Landlord\Services\LandlordMetricsService;

class DashboardController extends Controller
{
    public function __construct(
        protected LandlordMetricsService $metricsService
    ) {}

    /**
     * Display the central Landlord metrics dashboard.
     */
    public function index(): Response
    {
        return Inertia::render('Landlord/Dashboard', [
            'metrics' => $this->metricsService->getMetrics(),
        ]);
    }
}
