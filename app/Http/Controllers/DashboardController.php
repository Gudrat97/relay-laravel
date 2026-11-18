<?php

namespace App\Http\Controllers;

use App\Services\StatsService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(StatsService $stats): View
    {
        // The first screen is rendered with data so it shows instantly;
        // the date-range switch then loads fresh figures from the API.
        return view('dashboard', [
            'initial' => $stats->forPeriod(30),
        ]);
    }
}
