<?php

namespace App\Http\Controllers\Api;

use App\Models\Lead;
use App\Models\Reports;
use Dompdf\Dompdf;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class LeadController extends Controller
{
    public function exportToPDF(Request $request, $id) {
        $lead = Lead::where('id', $id)->firstOrFail();
        $pdf = new Dompdf();
        $pdf->setPaper('A4', 'landscape');
        $pdf->loadHtml(view('exports.lead.pdf', [ 'lead' => $lead ]));
        $pdf->render();

        return $pdf->stream("lead_{$id}.pdf");
    }

    public function exportToCSV(Request $request, $id) {
        $headers = [
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Content-type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=lead_{$id}.csv",
            'Expires'             => '0',
            'Pragma'              => 'public'
        ];
        $lead = Lead::where('id', $id)->firstOrFail();
        return response(view('exports.lead.csv', [ 'lead' => $lead ]))
            ->withHeaders($headers);
    }

    public function download($uuid) {
        $report = Reports::where('uuid', $uuid)->firstOrFail();
        $fileName = "report_{$report->uuid}";
        if ($report->type === Reports::$TYPE_LEADS_PDF) {
            $headers = [
                'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
                'Content-type'        => 'application/pdf',
                'Content-Disposition' => "attachment; filename={$fileName}.pdf",
                'Expires'             => '0',
                'Pragma'              => 'public'
            ];
            return \Storage::download(
                "reports/{$report->user_id}/leads/pdf/{$report->uuid}.pdf",
                "{$fileName}.pdf", $headers)->sendHeaders();
        }
        elseif ($report->type === Reports::$TYPE_LEADS_CSV) {
            $headers = [
                'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
                'Content-type'        => 'text/csv',
                'Content-Disposition' => "attachment; filename=$fileName.csv",
                'Expires'             => '0',
                'Pragma'              => 'public'
            ];
            return \Storage::download(
                "reports/{$report->user_id}/leads/csv/{$report->uuid}.csv",
                "{$fileName}.csv", $headers)
                ->sendHeaders();
        }

        elseif ($report->type === Reports::$TYPE_COMPANY_PDF) {
            $headers = [
                'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
                'Content-type'        => 'application/pdf',
                'Content-Disposition' => "attachment; filename={$fileName}.pdf",
                'Expires'             => '0',
                'Pragma'              => 'public'
            ];
            return \Storage::download(
                "reports/{$report->user_id}/companies/pdf/{$report->uuid}.pdf",
                "{$fileName}.pdf", $headers)->sendHeaders();
        }
        elseif ($report->type === Reports::$TYPE_COMPANY_CSV) {
            $headers = [
                'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
                'Content-type'        => 'text/csv',
                'Content-Disposition' => "attachment; filename=$fileName.csv",
                'Expires'             => '0',
                'Pragma'              => 'public'
            ];
            return \Storage::download(
                "reports/{$report->user_id}/companies/csv/{$report->uuid}.csv",
                "{$fileName}.csv", $headers)
                ->sendHeaders();
        }

        elseif ($report->type === Reports::$TYPE_CAMPAIGN_PDF) {
            $headers = [
                'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
                'Content-type'        => 'application/pdf',
                'Content-Disposition' => "attachment; filename={$fileName}.pdf",
                'Expires'             => '0',
                'Pragma'              => 'public'
            ];
            return \Storage::download(
                "reports/{$report->user_id}/campaigns/pdf/{$report->uuid}.pdf",
                "{$fileName}.pdf", $headers)->sendHeaders();
        }
        elseif ($report->type === Reports::$TYPE_CAMPAIGN_CSV) {
            $headers = [
                'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
                'Content-type'        => 'text/csv',
                'Content-Disposition' => "attachment; filename=$fileName.csv",
                'Expires'             => '0',
                'Pragma'              => 'public'
            ];
            return \Storage::download(
                "reports/{$report->user_id}/campaigns/csv/{$report->uuid}.csv",
                "{$fileName}.csv", $headers)
                ->sendHeaders();
        }
        throw new \Exception('Report not found');
    }
}
