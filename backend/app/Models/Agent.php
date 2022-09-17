<?php

namespace App\Models;

use App\Http\Controllers\Api\LeadReplyController;
use App\Http\Controllers\Api\TwilioController;
use App\Repositories\AgentRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Twilio\Rest\Client;

class Agent extends User
{
    use AgentRepository, SoftDeletes;
    
    protected $appends = ['avatar_path', 'permissions', 'company', 'companies', 'deals'];
    
    public function companies() {
        return $this->belongsToMany('App\Models\Company', 'company_agents', 'agent_id');
    }

    public function campaigns() {
        return $this->belongsToMany('App\Models\DealCampaign', 'deal_campaign_agents', 'agent_id');
    }

    public function leads() {
        return $this->hasMany('App\Models\Lead', 'agent_id');
    }

    public function devices() {
        return $this->hasMany('App\Models\Device', 'agent_id');
    }

    public function getCompanyAttribute() {
        if ($this->company_id) {
            $company = Company::withTrashed()->find($this->company_id);
            return ($company ? $company->only(['name', 'email', 'avatar_path']) : null);
        }
        return null;
    }

    public function getCompaniesAttribute() {
        $companies = $this->companies()->get();
        if ($companies) {
            return collect($companies)->map(function ($company)  {
                return $company->only(['id', 'name', 'email', 'avatar_path']);
            });
        }
        return null;
    }

    public function getDealsAttribute() {
        $campaigns = $this->campaigns()->get();
        if ($campaigns) {
            return $campaigns->map(function ($campaign)  {
                //return $campaign->deal->only(['id', 'name']);
                return $campaign->deal(['id', 'name']);
            })->keyBy('id')->values();
        }
        return null;
    }

    public function getCampaignsBy($queryParams = []) {
        $query = $this->campaigns()
            ->leftJoin('leads', 'leads.deal_campaign_id', 'deal_campaigns.id')
            ->leftJoin(\DB::raw("
            (SELECT lead_notes.lead_id, MIN(lead_notes.created_at) AS created_at
                          FROM lead_notes JOIN lead_statuses ON lead_statuses.id = lead_notes.lead_status_id
                          WHERE
                              lead_statuses.type = 'CONTACTED_SMS' OR
                              lead_statuses.type = 'CONTACTED_CALL' OR
                              lead_statuses.type = 'CONTACTED_EMAIL' GROUP BY lead_notes.lead_id) AS leadNotes
                          "), function ($join) {
                $join->on('leadNotes.lead_id', '=', 'leads.id');
            })->leftJoin('agency_companies as ac', 'ac.id', 'deal_campaigns.agency_company_id')
        ;
    
        $query->selectRaw('
            deal_campaigns.*,
            ac.company_id,
            COUNT(DISTINCT leads.id) as leads_count,
            SEC_TO_TIME(AVG(TIME_TO_SEC(TIMEDIFF(leadNotes.created_at, leads.created_at)))) AS avg_time_response
        ');
        $query->groupBy('deal_campaigns.id', 'deal_campaign_agents.agent_id');

        if (!isset($queryParams['showDeleted']) && $queryParams['showDeleted'] === 'true') {
            $query->withTrashed();
            
        }

        if (isset($queryParams['showDeleted']) && $queryParams['showDeleted'] === 'true') {
            $query->withTrashed();
        }

        if (isset($queryParams['companyId']) && $queryParams['companyId']) {
            $query->where('ac.company_id', $queryParams['companyId']);
        }
    
        if ( isset($queryParams['name']) ) {
            $query->orderBy('name', ($queryParams['name'] === 'true' ? 'DESC' : 'ASC'));
        }
    
        if ( isset($queryParams['type']) ) {
            $query->orderBy('integration', ($queryParams['type'] === 'true' ? 'DESC' : 'ASC'));
        }
    
        if ( isset($queryParams['leads']) ) {
            $query->orderBy('leads_count', ($queryParams['leads'] === 'true' ? 'DESC' : 'ASC'));
        }
    
        if ( isset($queryParams['avg_time_response']) ) {
            $query->orderBy('avg_time_response', ($queryParams['avg_time_response'] === 'true' ? 'DESC' : 'ASC'));
        }
    
        return $query;
    }
    
    public function getLeads($queryParams = [], $format='Y-m-d') {
        $query = $this->leads()
            ->join('agency_companies AS ac', 'ac.id', 'leads.agency_company_id')
            ->join('users as cp', 'cp.id', 'ac.company_id')
            ->join('lead_statuses as ls', 'ls.id', 'leads.lead_status_id')
            ->join('deal_campaigns as dc', 'dc.id', 'leads.deal_campaign_id')
            ->selectRaw('leads.*, ac.company_id, ac.agency_id, dc.deal_id')
        ;
    
        $query->orderBy('leads.created_at', 'DESC');

        if (isset($queryParams['search'])) {
            $query->where(function ($query) use ($queryParams) {
                $query
                    ->where('leads.fullname', 'like', "%{$queryParams['search']}%")
                    ->orWhere('leads.email', 'like', "%{$queryParams['search']}%")
                    ->orWhere('leads.phone', 'like', "%{$queryParams['search']}%")
                ;
            });
        }
        
        if (
            (isset($queryParams['startDate']) && $queryParams['startDate']) &&
            (isset($queryParams['endDate']) && $queryParams['endDate'])
        ) {
            $query->whereBetween('leads.created_at', [
                Carbon::createFromFormat($format, $queryParams['startDate'])->startOfDay(),
                Carbon::createFromFormat($format, $queryParams['endDate'])->endOfDay()]);
        }
        
        
        if (isset($queryParams['campaignId']) && $queryParams['campaignId']) {
            $query->where('dc.id', $queryParams['campaignId']);
        }

        if (isset($queryParams['statuses']) && $queryParams['statuses']) {
            $query->whereIn('ls.type', $queryParams['statuses']);
        }

        if (isset($queryParams['statusType']) && $queryParams['statusType']) {
            $query->where('ls.type', $queryParams['statusType']);
        }
        
        if ( isset($queryParams['showDeleted']) ) {
            $query->withTrashed();
        }
        
        if ( isset($queryParams['name']) ) {
            $query->orderBy('leads.fullname', ($queryParams['name'] === 'true' ? 'DESC' : 'ASC'));
        }
        
        if ( isset($queryParams['email']) ) {
            $query->orderBy('leads.email', ($queryParams['email'] === 'true' ? 'DESC' : 'ASC'));
        }
        
        if ( isset($queryParams['company']) ) {
            $query->orderBy('cp.name', ($queryParams['company'] === 'true' ? 'DESC' : 'ASC'));
        }
        if ( isset($queryParams['campaign']) ) {
            $query->orderBy('dc.name', ($queryParams['campaign'] === 'true' ? 'DESC' : 'ASC'));
        }
        
        return $query;
    }

    public function countNewLeads() {
        $query = $this->leads()
            ->join('agency_companies AS ac', 'ac.id', 'leads.agency_company_id')
            ->join('users as cp', 'cp.id', 'ac.company_id')
            ->join('lead_statuses as ls', 'ls.id', 'leads.lead_status_id')
            ->join('deal_campaigns as dc', 'dc.id', 'leads.deal_campaign_id')
            ->selectRaw('leads.*, ac.company_id, ac.agency_id, dc.deal_id')
        ;
    
        $query->where('ls.type', LeadStatus::$STATUS_NEW);
        
        return $query->count();
    }

    public function getLeadBy($leadId) {
        return $this->leads()->where('leads.id', $leadId)->firstOrFail();
    }

    public function setupTwilioVoiceWebHook($twilioSid, $twilioToken, $twilioNumber, int $companyId) {
        if (!trim($twilioSid) || !trim($twilioToken) || !trim($twilioNumber) || !trim($companyId)) {
            return;
        }

        $twilioClient = new Client($twilioSid, $twilioToken);
        $result = $twilioClient->incomingPhoneNumbers->read([
            'phoneNumber' => $twilioNumber,
        ], 1);
        $number = last($result);

        if (empty($number)) {
            abort('Not valid number');
        }

        if ($number->sid) {
            $twilioClient->incomingPhoneNumbers($number->sid)->update([
                'smsMethod' => 'POST',
                'smsUrl' => action([LeadReplyController::class, 'onSMSReply']),
                'voiceMethod' => 'POST',
                'voiceUrl' => action([TwilioController::class, 'conference'], [
                    'companyId' => $companyId,
                    'agentId' => $this->id,
                ])
            ]);
        }

        $application = $twilioClient->applications
            ->create([
                    "voiceMethod" => "POST",
                    "voiceUrl" => action([TwilioController::class, 'conference'], [
                        'companyId' => $companyId,
                        'agentId' => $this->id,
                    ]),
                    "friendlyName" => $this->name,
                ]
            );

        $this->twilio_app_sid = $application->sid;
        $this->save();
    }
}
