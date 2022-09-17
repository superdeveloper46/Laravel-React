<?php

namespace App\Http\Controllers\Api\Management;

use App\Jobs\LeadExportJob;
use App\Models\Reports;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    use DispatchesJobs;

    public function store(Request $request) {
        $payload = $request->input('payload');
        $report = new Reports();
        $report->fill([
            'uuid' => Str::uuid(),
            'status' => Reports::$STATUS_NONE,
            'payload' => json_encode($payload),
            'type' => $payload['type'],
        ]);
        $report->user_id = $request->user()->id;
        $report->save();
        $this->dispatch((new LeadExportJob($report)));

        return $report;
    }

    public function poll(Request $request, $uuid) {
        $report = Reports::where('uuid', $uuid)->firstOrFail();
        $statuses = [Reports::$STATUS_COMPLETED, Reports::$STATUS_NONE, Reports::$STATUS_IN_PROGRESS];
        if (in_array($report->status, $statuses)) {
            return $report;
        }
        throw new \Exception('Was not possible to create report');
    }
}
