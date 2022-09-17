<?php

namespace App\Jobs;

use App\Models\Agency;
use App\Models\Company;
use App\Models\Lead;
use App\Models\Reports;
use App\Models\User;
use App\Services\MailService;
use Dompdf\Dompdf;
use Illuminate\Bus\Queueable;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class LeadExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $report;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Reports $report)
    {
        $this->report = $report;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $payload = json_decode($this->report->payload);
            $user = $this->report->user;
            $isAgency = $user->role === User::$ROLE_AGENCY;
            $isCompany = $user->role === User::$ROLE_COMPANY;

            if ($isAgency) {
                $user = Agency::find($user->id);
            } elseif ($isCompany) {
                $user = Company::find($user->id);
            }


            if ($this->report->type === Reports::$TYPE_LEADS_PDF) {
                $leads = $user->getLeads((array)$payload)->get();
                $this->exportLeadsToPDF($leads, $this->report);
            } elseif ($this->report->type === Reports::$TYPE_LEADS_CSV) {
                $leads = $user->getLeads((array)$payload)->get();
                $this->exportLeadsToCSV($leads, $this->report);
            } 
            elseif ($this->report->type === Reports::$TYPE_COMPANY_PDF) {
                $companies = $user->getCompanies((array)$payload)->get();
                $this->exportCompaniesToPDF($companies, $this->report);
            } elseif ($this->report->type === Reports::$TYPE_COMPANY_CSV) {
                $companies = $user->getCompanies((array)$payload)->get();
                $this->exportCompaniesToCSV($companies, $this->report);
            } 
            elseif ($this->report->type === Reports::$TYPE_CAMPAIGN_PDF) {
                $leads = $user->getLeads((array)$payload)->get();
                $this->exportCampaignsToPDF($leads, $this->report);
            } elseif ($this->report->type === Reports::$TYPE_CAMPAIGN_CSV) {
                $leads = $user->getLeads((array)$payload)->get();
                $this->exportCampaignsToCSV($leads, $this->report);
            }

            $this->report->status = Reports::$STATUS_COMPLETED;
            $this->report->save();

            MailService::sendMail(
                'emails.report',
                [
                    'report' => $this->report,
                    'user' => $user,
                    'query' => $payload,
                    'file' => url("/api/v1/reports/{$this->report->uuid}/download"),
                ],
                $this->report->user->email,
                env('APP_REPORT', 'Your report has been generated!')
            );
        } catch (\Exception $exception) {
            \Log::critical($exception->getMessage());
            $this->report->status = Reports::$STATUS_FAILED;
            $this->report->save();

            MailService::sendMail(
                'emails.report_failed',
                [
                    'report' => $this->report,
                    'user' => $user,
                    'query' => $payload,
                    'file' => url("/api/v1/reports/{$this->report->uuid}/download"),
                ],
                $this->report->user->email,
                env('APP_REPORT', 'Your report failed!')
            );
        }
    }

    public function exportLeadsToPDF($leads, Reports $report)
    {
        $pdf = new Dompdf();
        $pdf->setPaper('A4', 'landscape');
        $pdf->loadHtml(view('exports.leads.pdf', ['leads' => $leads, 'reportPayload' => json_decode($report->payload)]));
        $pdf->render();

        $fileName = "reports/{$report->user_id}/leads/pdf/{$report->uuid}.pdf";
        \Storage::disk('local')->put($fileName,  $pdf->output());
    }

    public function exportLeadsToCSV($leads, Reports $report)
    {
        $fileName = "reports/{$report->user_id}/leads/csv/{$report->uuid}.csv";
        \Storage::disk('local')->put($fileName,  view('exports.leads.csv', ['leads' => $leads, 'reportPayload' => json_decode($report->payload)]));
    }

    public function exportCompaniesToPDF($companies, Reports $report)
    {
        $pdf = new Dompdf();
        $pdf->setPaper('A4', 'landscape');
        $pdf->loadHtml(view('exports.companies.pdf', ['companies' => $companies, 'reportPayload' => json_decode($report->payload)]));
        $pdf->render();

        $fileName = "reports/{$report->user_id}/companies/pdf/{$report->uuid}.pdf";
        \Storage::disk('local')->put($fileName,  $pdf->output());
    }

    public function exportCompaniesToCSV($companies, Reports $report)
    {
        $fileName = "reports/{$report->user_id}/companies/csv/{$report->uuid}.csv";
        \Storage::disk('local')->put($fileName,  view('exports.companies.csv', ['companies' => $companies, 'reportPayload' => json_decode($report->payload)]));
    }

    public function exportCampaignsToPDF($leads, Reports $report)
    {
        $pdf = new Dompdf();
        $pdf->setPaper('A4', 'landscape');
        $pdf->loadHtml(view('exports.leads.pdf', ['leads' => $leads, 'reportPayload' => json_decode($report->payload)]));
        $pdf->render();

        $fileName = "reports/{$report->user_id}/campaigns/pdf/{$report->uuid}.pdf";
        \Storage::disk('local')->put($fileName,  $pdf->output());
    }

    public function exportCampaignsToCSV($leads, Reports $report)
    {
        $fileName = "reports/{$report->user_id}/campaigns/csv/{$report->uuid}.csv";
        \Storage::disk('local')->put($fileName,  view('exports.leads.csv', ['leads' => $leads, 'reportPayload' => json_decode($report->payload)]));
    }
}
