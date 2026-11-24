<?php

namespace App\Http\Controllers\Admin;

use App\Core\Response;
use App\Services\RealtimeDataService;

class ReportController extends AdminController
{
    public function index(): Response
    {
        return $this->adminView('reports', [
            'title' => 'Financial Reports — Travelbookings Admin',
            'page' => 'reports'
        ]);
    }

    public function export(): Response
    {
        $filename = 'cj-travels-report-' . date('Y-m-d-His') . '.csv';

        return new Response(RealtimeDataService::reportsCsv(), 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
