<?php
namespace App\Repositories;

use App\Models\Agent;
use App\Models\Company;
use App\Models\Lead;
use Carbon\Carbon;

trait CompanyRepository {
    public function createCompany($data) {
        $data['role'] = Company::$ROLE_COMPANY;
        return $this->createUser($data);
    }

    public function getAgentBy($agentId) {
        return $this->agents()->withTrashed()->where('agent_id', $agentId)->firstOrFail();
    }
    
    public function getDealBy($dealId) {
        return $this->deals()->where('deals.id', $dealId)->firstOrFail();
    }

    public function getLeadBy($leadId) {
        return $this->leads()->where('leads.id', $leadId)->firstOrFail();
    }

    public static function contactedLeadsGraph(
        $startDate,
        $endDate,
        $companyAgencyId = null,
        $agentId = null, $format = 'Y-m-d') {
        $query = Lead::selectRaw(
            "
          DATE(leads.created_at) as creation_date,
       SUM(time_to_sec(timediff(ln.created_at, leads.created_at)) <= (15*60)) as up15Minutes,
             SUM(
       time_to_sec(timediff(ln.created_at, leads.created_at)) >= (15*60) AND time_to_sec(timediff(ln.created_at, leads.created_at)) <= (30*60)
       ) as up30Mintes,
       SUM(
       time_to_sec(timediff(ln.created_at, leads.created_at)) >= (30*60) AND time_to_sec(timediff(ln.created_at, leads.created_at)) <= (2*60*60)
       ) as up2Hours,
       SUM(
       time_to_sec(timediff(ln.created_at, leads.created_at)) >= (2*60*60) AND time_to_sec(timediff(ln.created_at, leads.created_at)) <= (12*60*60)
       ) as up12Hours,
            SUM(
       time_to_sec(timediff(ln.created_at, leads.created_at)) >= (12*60*60)) as 12plus
     
            ")
            ->join('lead_notes AS ln', 'ln.lead_id', 'leads.id')
            ->join('lead_statuses AS ls', 'ls.id', 'ln.lead_status_id')
            ->where(function ($query) {
                $query
                    ->where('ls.type', 'CONTACTED_SMS')
                    ->orWhere('ls.type', 'CONTACTED_CALL')
                    ->orWhere('ls.type', 'CONTACTED_EMAIL')
                ;
            })
            ->groupBy('creation_date')
            ->whereBetween('leads.created_at', [
                Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay(),
                Carbon::createFromFormat('Y-m-d', $endDate)->endOfDay()]);
        
        if (is_array($companyAgencyId)) {
            $query->whereIn('leads.agency_company_id', $companyAgencyId);
        } elseif ($companyAgencyId) {
            $query->where('leads.agency_company_id', $companyAgencyId);
        }
        
        if ($agentId) {
            $query->where('leads.agent_id', $agentId);
        }
        $averageResponseTime = static::getAverageTime($startDate, $endDate, $companyAgencyId, $agentId, $format);
       return static::mapLeadsData($query->get(), $averageResponseTime, $startDate, $endDate, $format);
    }
    
    static function getAverageTime( $startDate,
                                    $endDate,
                                    $companyAgencyId = null,
                                    $agentId = null, $format = 'Y-m-d') {
        $query = Lead::selectRaw(
          "sec_to_time(AVG(time_to_sec(timediff(ln.created_at, leads.created_at)))) as avg_time")
            ->join('lead_notes AS ln', 'ln.lead_id', 'leads.id')
            ->join('lead_statuses AS ls', 'ls.id', 'ln.lead_status_id')
            ->where(function ($query) {
                $query
                    ->where('ls.type', 'CONTACTED_SMS')
                    ->orWhere('ls.type', 'CONTACTED_CALL')
                    ->orWhere('ls.type', 'CONTACTED_EMAIL')
                ;
            })
            ->whereBetween('leads.created_at', [
                Carbon::createFromFormat('Y-m-d', $startDate),
                Carbon::createFromFormat('Y-m-d', $endDate)]);
    
        if ($companyAgencyId) {
            $query->where('leads.agency_company_id', $companyAgencyId);
        }
    
        if ($agentId) {
            $query->where('leads.agent_id', $agentId);
        }
        return $query->first();
    }
    
    static public function mapLeadsData($leads, $averageResponseTime, $startDate, $endDate, $format = 'Y-m-d') {
        $interval = new \DateInterval('P1D');
        $dateRange = new \DatePeriod(new \DateTime($startDate), $interval , new \DateTime($endDate));
        $dateCollection = collect($dateRange)->map(function ($date) use ($format) {
            return $date->format($format);
        });
        $dateCollection[] = $endDate;

        $datasets = [
            [
                "label" => '15 min (0-15)',
                "data" => 'up15Minutes',
                "backgroundColor" => ['rgba(0, 0, 0, 0)'],
                "borderColor" => ['#21ba45'],
                "borderWidth" => 2,
            ],
            [
                "label" => '30 min (15-30)',
                "data" => 'up30Mintes',
                "backgroundColor" => ['rgba(0, 0, 0, 0)'],
                "borderColor" => ['#f2711c'],
                "borderWidth" => 2,
            ],
            [
                "label" => '2 hrs (30-2)',
                "data" => 'up2Hours',
                "backgroundColor" => ['rgba(0, 0, 0, 0)'],
                "borderColor" => ['#2cb3c8'],
                "borderWidth" => 2,
            ],
            [
                "label" => '12 hrs (2-12)',
                "data" => 'up12Hours',
                "backgroundColor" => ['rgba(0, 0, 0, 0)'],
                "borderColor" => ['#6435c9'],
                "borderWidth" => 2,
            ],
            [
                "label" => '12 hrs + Missed leads',
                "data" => '12plus',
                "backgroundColor" => ['rgba(0, 0, 0, 0)'],
                "borderColor" => ['#db2828'],
                "borderWidth" => 2,
            ]
        ];
    
    
        $datasets = collect($datasets)->map(function ($dataset) use ($leads, $dateCollection) {
            $fieldName = $dataset['data'];
            $dataset['data'] = collect($dateCollection)->map(function ($date) use ($leads, $fieldName) {
                return  (int)$leads->where('creation_date', $date)->first()[$fieldName];
            });
            return $dataset;
        });
    
    
        return [
            'avg_response_time' => ($averageResponseTime->avg_time ? $averageResponseTime->avg_time : '00:00:00'),
            'labels' => $dateCollection,
            'datasets' => $datasets
        ];
    }
    
    public function getAgents($queryParams = []) {
        $query = Agent::selectRaw
        (
            'users.agent_agency_id, users.id, users.role, users.name, users.email,
             users.phone,
             users.twilio_mobile_number,
              users.avatar_id,
               users.deleted_at,
            COUNT(DISTINCT ac.id, dca.id) AS campaigns_count,
            COUNT(DISTINCT ld.id) AS leads_count,
            SEC_TO_TIME(AVG(TIME_TO_SEC(TIMEDIFF(leadNotes.created_at, ld.created_at)))) AS avg_lead_response,
            users.created_at,
            users.deleted_at
            '
        )
            ->join('company_agents AS ca', 'ca.agent_id', 'users.id')
            ->join('agency_companies AS ac', 'ac.company_id', 'ca.company_id')
            ->leftJoin('leads AS ld', function ($join) {
                $join->on('ld.agency_company_id', '=', 'ac.id')
                    ->on('ld.agent_id', '=', 'users.id')
                ;
            })
            ->leftJoin(\DB::raw("
            (SELECT lead_notes.lead_id, MIN(lead_notes.created_at) AS created_at
                          FROM lead_notes JOIN lead_statuses ON lead_statuses.id = lead_notes.lead_status_id
                          WHERE
                              lead_statuses.type = 'CONTACTED_SMS' OR
                              lead_statuses.type = 'CONTACTED_CALL' OR
                              lead_statuses.type = 'CONTACTED_EMAIL' GROUP BY lead_notes.lead_id) AS leadNotes
                          "), function ($join) {
                $join->on('leadNotes.lead_id', '=', 'ld.id');
            })
            ->leftJoin('deal_campaigns as dc', 'dc.agency_company_id', 'ac.id')
            ->leftJoin('deal_campaign_agents as dca', function ($join) {
                $join->on('dca.agent_id', '=', 'ca.agent_id')
                    ->on('dca.deal_campaign_id', '=', 'dc.id')
                ;
            })
            ->where('ac.company_id', $this->id)
            ->groupBy('users.id');
        
        if ( isset($queryParams['showDeleted']) ) {
            $query->withTrashed();
        }
        
        if (isset($queryParams['search'])) {
            $query->where(function ($query) use ($queryParams) {
                $query
                    ->where('users.name', 'LIKE', "%{$queryParams['search']}%")
                    ->orWhere('users.email', 'LIKE', "%{$queryParams['search']}%");
            });
        }
        
        if ( isset($queryParams['name']) ) {
            $query->orderBy('users.name', ($queryParams['name'] === 'true' ? 'DESC' : 'ASC'));
        }
        
        if ( isset($queryParams['campaigns']) ) {
            $query->orderBy('campaigns_count', $queryParams['campaigns'] === 'true' ? 'DESC' : 'ASC');
        }
        
        if ( isset($queryParams['leads']) ) {
            $query->orderBy('leads_count', $queryParams['leads'] === 'true' ? 'DESC' : 'ASC');
        }
        
        if ( isset($queryParams['avg_response']) ) {
            $query->orderBy('avg_lead_response', $queryParams['avg_response'] === 'true' ? 'DESC' : 'ASC');
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

        if (isset($queryParams['agentId']) && $queryParams['agentId']) {
            $query->where('leads.agent_id', $queryParams['agentId']);
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
}
