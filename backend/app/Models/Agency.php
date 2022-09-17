<?php

namespace App\Models;

use App\Repositories\AgencyRepository;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\SoftDeletes;

class Agency extends User
{
    use AgencyRepository, SoftDeletes;

    public function agencyCompaniesBy($companyId)
    {
        return $this->belongsTo('App\Models\AgencyCompanyPivot', 'id')->where('company_id', $companyId)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function companies()
    {
        return $this->belongsToMany('App\Models\Company', 'agency_companies', 'agency_id')->withPivot('id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function agents()
    {
        return $this->hasMany('App\Models\Agent', 'agent_agency_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function deals()
    {
        return $this->belongsToMany(
            'App\Models\Deal',
            'agency_companies',
            'agency_id',
            'id',
            'id',
            'agency_company_id'
        );
    }

    public function getCompanies($queryParams)
    {
        switch (isset($queryParams['reduced']) && $queryParams['reduced']) {
            case true: {
                    return $this->getReducedCompaniesDetails($queryParams);
                }
            default: {
                    return $this->getCompaniesWithStats($queryParams);
                }
        }
    } 
    
    public function getCompanyLeadStatsBy($companyId, $fromDate, $toDate, $agentId = null)
    {
        // $format = 'Y-m-d';
        // $query = Lead::selectRaw('COUNT(leads.id) AS total_leads_count,
        //         SUM(IF(ls.type = \'SOLD\', 1, 0)) AS total_leads_converted,
        //         DATE_FORMAT(leads.created_at, \'%Y-%m-%d\') AS creation_date,
        //         AVG(TIME_TO_SEC(TIMEDIFF(leadNotes.created_at, leads.created_at))) AS sec_avg_lead_response')
        //     ->join('agency_companies AS ac', 'ac.id', 'leads.agency_company_id')
        //     ->join('lead_statuses AS ls', 'ls.id', 'leads.lead_status_id')
        //     ->leftJoin(\DB::raw("
        //     (SELECT lead_notes.lead_id, MIN(lead_notes.created_at) AS created_at
        //                   FROM lead_notes JOIN lead_statuses ON lead_statuses.id = lead_notes.lead_status_id
        //                   WHERE
        //                       lead_statuses.type = 'CONTACTED_SMS' OR
        //                       lead_statuses.type = 'CONTACTED_CALL' OR
        //                       lead_statuses.type = 'CONTACTED_EMAIL' GROUP BY lead_notes.lead_id) AS leadNotes
        //                   "), function ($join) {
        //         $join->on('leadNotes.lead_id', '=', 'leads.id');
        //     });

        // $query->where('ac.company_id', $companyId);

        $st_dt = Carbon::createFromFormat('Y-m-d', $fromDate)->startOfDay();
        $end_dt = Carbon::createFromFormat('Y-m-d', $toDate)->endOfDay();

        // $query->whereBetween('leads.created_at', [
        //     $st_dt,
        //     $end_dt 
        // ]);

        // $query->groupBy(['ac.company_id', 'creation_date']);

        // if ($agentId) {
        //     $query->where('leads.agent_id', '=', $agentId);
        // }
 
        $sql = "select COUNT(leads.id) AS total_leads_count, SUM(IF(ls.type = 'SOLD', 1, 0)) AS total_leads_converted,                DATE_FORMAT(leads.created_at, '%Y-%m-%d') AS creation_date,                AVG(TIME_TO_SEC(TIMEDIFF(leadNotes.created_at, leads.created_at))) AS sec_avg_lead_response            from `leads` inner join `agency_companies` as `ac` on `ac`.`id` = `leads`.`agency_company_id` inner join `lead_statuses` as `ls` on `ls`.`id` = `leads`.`lead_status_id` left join             (SELECT lead_notes.lead_id, MIN(lead_notes.created_at) AS created_at                          FROM lead_notes JOIN lead_statuses ON lead_statuses.id = lead_notes.lead_status_id                          WHERE                              lead_statuses.type = 'CONTACTED_SMS' OR                              lead_statuses.type = 'CONTACTED_CALL' OR                              lead_statuses.type = 'CONTACTED_EMAIL' GROUP BY lead_notes.lead_id) AS leadNotes                           on `leadNotes`.`lead_id` = `leads`.`id` where `ac`.`company_id` = $companyId and `leads`.`created_at` between 
        '" . $st_dt . "' and '" . $end_dt . "' and `leads`.`deleted_at` is null ";
        if ($agentId) {
            $sql .= " and `leads`.`agent_id` = $agentId ";
        }

        $temp = \DB::select(\DB::raw($sql . " group by `ac`.`company_id`, `creation_date`"));

        $datePeriod = CarbonPeriod::create( 
            $st_dt,
            $end_dt
        );

        //Carbon::parse($temp[$i]->created_date)->shortDayName;

        $datePeriod = collect($datePeriod)->map(function ($period) {
            return $period->format('Y/m/d');
        })->flip()->map(function ($date, $key) {
            return [
                'total_leads_count' => 0,
                'total_leads_converted' => 0,
                'sec_avg_lead_response' => 0,
                'creation_date' => $key
            ];
        })->toArray();

        $aaa = [];
        $acc['sec_avg_lead_response'] = 0;
        for ($i = 0; $i < count($temp); $i++) {
            $str = $temp[$i]->creation_date;
            $str = str_replace('-', '/', $str);
            //Carbon::parse($str)->shortDayName;
            $temp[$i]->creation_date = Carbon::parse($temp[$i]->creation_date)->shortDayName;
            $aaa[$str] = $temp[$i];
            $acc['total_leads_count'] = ($acc['total_leads_count'] ?? 0) + $temp[$i]->total_leads_count;
            $acc['total_leads_converted'] = ($acc['total_leads_converted'] ?? 0) + $temp[$i]->total_leads_converted;
            $acc['sec_avg_lead_response'] = ($acc['sec_avg_lead_response'] ?? 0) + $temp[$i]->sec_avg_lead_response;
        }

        $acc['avg_lead_response_formatted'] = '';
        if ($acc['sec_avg_lead_response'] > 0) {
            $acc['avg_lead_response_formatted'] = CarbonInterval::seconds(
                $acc['sec_avg_lead_response']
            )->cascade()->forHumans();
        }

        $records = array_merge($aaa, $acc ?? []);

        return array_merge(
            [
                'records' => $records,
            ],
            $acc ?? []
        );



        // $stats = $leadsStats->reduce(function ($acc, $lead) {
        //     $acc[$lead->creation_date] = $lead->only(['total_leads_count', 'total_leads_converted', 'sec_avg_lead_response', 'creation_date']);
        //     return $acc;
        // });
        // $records = array_merge($datePeriod, $stats ?? []);
        // $totals = collect($records)->reduce(function ($acc, $lead) {
        //     $acc['total_leads_count'] = ($acc['total_leads_count'] ?? 0) + $lead['total_leads_count'];
        //     $acc['total_leads_converted'] = ($acc['total_leads_converted'] ?? 0) + $lead['total_leads_converted'];
        //     $acc['sec_avg_lead_response'] = ($acc['sec_avg_lead_response'] ?? 0) + floatval($lead['sec_avg_lead_response']);

        //     $acc['avg_lead_response_formatted'] = '';
        //     if ($acc['sec_avg_lead_response'] > 0) {
        //         $acc['avg_lead_response_formatted'] = CarbonInterval::seconds(
        //             $acc['sec_avg_lead_response']
        //         )->cascade()->forHumans();
        //     }

        //     return $acc;
        // });

        // return array_merge([
        //         'records' => $records,
        //     ],
        //     $totals ?? []
        // );
    }

    /**
     * @param $queryParams
     * @return Company|\Illuminate\Database\Query\Builder
     */
    public function getCompaniesWithStats($queryParams)
    {
        $query = Company::selectRaw('
            users.id,
            users.name,
            users.phone,
            users.email,
            users.avatar_id,
            users.twilio_sid,
            users.twilio_token,
            users.twilio_mobile_number,
            agency_companies.is_locked,
            IF(users.deleted_at IS NOT NULL, 1, 0) AS is_deleted,
            COUNT(DISTINCT users.id, deals.id) as deals_count,
            COUNT(DISTINCT users.id, leads.id) as leads_count,
            COUNT(DISTINCT users.id, company_agents.id) as agents_count,
            SEC_TO_TIME(AVG(TIME_TO_SEC(TIMEDIFF(leadNotes.created_at, leads.created_at)))) AS avg_lead_response
            ')
            ->join('agency_companies', 'agency_companies.company_id', 'users.id')
            ->leftJoin('deals', 'deals.agency_company_id', 'agency_companies.id')
            ->leftJoin('company_agents', 'company_agents.company_id', 'users.id')
            ->leftJoin('leads', 'leads.agency_company_id', 'agency_companies.id')
            ->leftJoin(\DB::raw("
            (SELECT lead_notes.lead_id, MIN(lead_notes.created_at) AS created_at
                          FROM lead_notes JOIN lead_statuses ON lead_statuses.id = lead_notes.lead_status_id
                          WHERE
                              lead_statuses.type = 'CONTACTED_SMS' OR
                              lead_statuses.type = 'CONTACTED_CALL' OR
                              lead_statuses.type = 'CONTACTED_EMAIL' GROUP BY lead_notes.lead_id) AS leadNotes
                          "), function ($join) {
                $join->on('leadNotes.lead_id', '=', 'leads.id');
            })
            ->where('agency_companies.agency_id', $this->id)
            ->groupBy('agency_companies.company_id', 'agency_companies.is_locked');
        $query->with('agents');
        
        // if ( isset($queryParams['showDeleted']) ) {
        //     $query->withTrashed();
        // } else {
        //     $query->whereRaw('users.deleted_at IS NULL');
        // }
        if (isset($queryParams['showDeleted']) && $queryParams['showDeleted'] == true) {
            $query->withTrashed();
            $query->whereRaw('users.deleted_at IS NOT NULL');
        } else {
            $query->withTrashed();
            $query->whereRaw('users.deleted_at IS NULL');
        }

        if (isset($queryParams['search']) && $queryParams['search']) {
            $query->where(function ($query) use ($queryParams) {
                $query
                    ->where('users.name', 'like', "%{$queryParams['search']}%")
                    ->orWhere('users.email', 'like', "%{$queryParams['search']}%");
            });
        }

        if (isset($queryParams['name'])) {
            $query->orderBy('users.name', ($queryParams['name'] === 'true' ? 'DESC' : 'ASC'));
        }


        if (isset($queryParams['deals'])) {
            $query->orderBy('deals_acount', $queryParams['deals'] === 'true' ? 'DESC' : 'ASC');
        }

        if (isset($queryParams['leads'])) {
            $query->orderBy('leads_count', $queryParams['leads'] === 'true' ? 'DESC' : 'ASC');
        }

        if (isset($queryParams['agents'])) {
            $query->orderBy('agents_count', $queryParams['agents'] === 'true' ? 'DESC' : 'ASC');
        }
        if (isset($queryParams['avg_response'])) {
            $query->orderBy('avg_lead_response', $queryParams['avg_response'] === 'true' ? 'DESC' : 'ASC');
        }

        return $query;
    }

    public function getAgents($queryParams = [])
    {
        $query = Agent::selectRaw(
                'users.agent_agency_id, users.id, users.role, users.name, users.email,
             users.phone,
             users.twilio_mobile_number,
             users.avatar_id,
             COUNT(DISTINCT dca.id) AS campaigns_count,
             COUNT(DISTINCT ld.id) AS leads_count,
             SEC_TO_TIME(AVG(TIME_TO_SEC(TIMEDIFF(leadNotes.created_at, ld.created_at)))) AS avg_lead_response,
             users.created_at,
             users.deleted_at
            '
            )
            ->join('users as agency', 'agency.id', 'users.agent_agency_id')
            ->leftJoin('company_agents AS ca', 'ca.agent_id', 'users.id')

            // ->leftJoin('deal_campaign_agents as dca', 'dca.agent_id', 'users.id')

            ->leftJoin(
                \DB::raw("
                            (SELECT           
                            dca.*
                            FROM `deal_campaigns` 
                            LEFT JOIN deal_campaign_agents AS dca ON dca.deal_campaign_id = deal_campaigns.id
                            WHERE deal_campaigns.deleted_at IS NULL) AS dca
                            "),
                function ($join) {
                    $join->on('dca.agent_id', '=', 'users.id');
                }
            )

            ->leftJoin('leads AS ld', 'ld.agent_id', 'users.id')
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
            ->where('agency.id', $this->id)
            ->groupBy('users.id');

        if (isset($queryParams['showDeleted']) && $queryParams['showDeleted'] == 1) {
            $query->withTrashed();
            $query->whereRaw('users.deleted_at IS NOT NULL');
        } else {
            $query->withTrashed();
            $query->whereRaw('users.deleted_at IS NULL');
        }

        if (isset($queryParams['companyId']) && $queryParams['companyId']) {
            $query->where('ca.company_id', $queryParams['companyId']);
        }

        if (isset($queryParams['search'])) {
            $query->where(function ($query) use ($queryParams) {
                $query
                    ->where('users.name', 'LIKE', "%{$queryParams['search']}%")
                    ->orWhere('users.email', 'LIKE', "%{$queryParams['search']}%");
            });
        }

        if (isset($queryParams['name'])) {
            $query->orderBy('users.name', ($queryParams['name'] === 'true' ? 'DESC' : 'ASC'));
        }

        if (isset($queryParams['campaigns'])) {
            $query->orderBy('campaigns_count', $queryParams['campaigns'] === 'true' ? 'DESC' : 'ASC');
        }

        if (isset($queryParams['leads'])) {
            $query->orderBy('leads_count', $queryParams['leads'] === 'true' ? 'DESC' : 'ASC');
        }

        if (isset($queryParams['avg_response'])) {
            $query->orderBy('avg_lead_response', $queryParams['avg_response'] === 'true' ? 'DESC' : 'ASC');
        }
        return $query;
    }

    public function getLeads($queryParams = [], $format = 'Y-m-d')
    {
        $query = Lead::selectRaw('leads.*, ac.company_id, ac.agency_id, dc.deal_id')
            ->join('agency_companies AS ac', 'ac.id', 'leads.agency_company_id')
            ->join('users as cp', 'cp.id', 'ac.company_id')
            ->join('lead_statuses as ls', 'ls.id', 'leads.lead_status_id')
            ->join('deal_campaigns as dc', 'dc.id', 'leads.deal_campaign_id')
            ->where('ac.agency_id', $this->id);
        if (isset($queryParams['sort_by']) && $queryParams['sort_by']) {

            if ($queryParams['sort_by'] == 'name.asc')
                $query->orderBy('fullname', 'asc');
            if ($queryParams['sort_by'] == 'name.desc')
                $query->orderBy('fullname', 'desc');

            if ($queryParams['sort_by'] == 'created_at.asc')
                $query->orderBy('created_at', 'asc');
            if ($queryParams['sort_by'] == 'created_at.desc')
                $query->orderBy('created_at', 'desc');

            if ($queryParams['sort_by'] == 'company.asc')
                $query->orderBy('company_id', 'asc');
            if ($queryParams['sort_by'] == 'company.desc')
                $query->orderBy('ac.company_id', 'desc');

            if ($queryParams['sort_by'] == 'source.asc')
                $query->orderBy('deal_campaign_id', 'asc');
            if ($queryParams['sort_by'] == 'source.desc')
                $query->orderBy('deal_campaign_id', 'desc');
        }
        if (isset($queryParams['search'])) {
            $query->where(function ($query) use ($queryParams) {
                $query
                    ->where('leads.fullname', 'like', "%{$queryParams['search']}%")
                    ->orWhere('leads.email', 'like', "%{$queryParams['search']}%")
                    ->orWhere('leads.phone', 'like', "%{$queryParams['search']}%");
            });
        }

        if (isset($queryParams['companyId']) && $queryParams['companyId']) {
            $query->where('ac.company_id', $queryParams['companyId']);
        }

        if (
            (isset($queryParams['startDate']) && $queryParams['startDate']) &&
            (isset($queryParams['endDate']) && $queryParams['endDate'])
        ) {
            $query->whereBetween('leads.created_at', [
                Carbon::createFromFormat($format, $queryParams['startDate'])->startOfDay(),
                Carbon::createFromFormat($format, $queryParams['endDate'])->endOfDay()
            ]);
        }


        if (isset($queryParams['agentId']) && $queryParams['agentId']) {
            $query->where('leads.agent_id', $queryParams['agentId']);
        }

        if (isset($queryParams['campaignId']) && $queryParams['campaignId']) {
            $query->where('dc.id', $queryParams['campaignId']);
        }

        if (isset($queryParams['statusType']) && $queryParams['statusType']) {
            $query->where('ls.type', $queryParams['statusType']);
        }

        if (isset($queryParams['showDeleted']) &&  $queryParams['showDeleted']) {
            // if ( isset($queryParams['showDeleted'])) { old code
            $query->withTrashed();
            $query->whereRaw('leads.deleted_at IS NOT NULL');
        }

        if (isset($queryParams['name'])) {
            $query->orderBy('leads.fullname', ($queryParams['name'] === 'true' ? 'DESC' : 'ASC'));
        }

        if (isset($queryParams['email'])) {
            $query->orderBy('leads.email', ($queryParams['email'] === 'true' ? 'DESC' : 'ASC'));
        }

        if (isset($queryParams['company'])) {
            $query->orderBy('cp.name', ($queryParams['company'] === 'true' ? 'DESC' : 'ASC'));
        }
        if (isset($queryParams['campaign'])) {
            $query->orderBy('dc.name', ($queryParams['campaign'] === 'true' ? 'DESC' : 'ASC'));
        }

        return $query;
    }

    /**
     * @param $queryParams
     * @return mixed
     */
    public function getReducedCompaniesDetails($queryParams)
    {
        $query =
            $this->companies()
            ->leftJoin('company_agents', 'company_agents.company_id', 'users.id');

        if (isset($queryParams['search']) && $queryParams['search']) {
            $query->where(function ($query) use ($queryParams) {
                $query
                    ->where('users.name', 'like', "%{$queryParams['search']}%")
                    ->orWhere('users.email', 'like', "%{$queryParams['search']}%");
            });
        }

        if (isset($queryParams['name'])) {
            $query->orderBy('users.name', ($queryParams['name'] === 'true' ? 'DESC' : 'ASC'));
        }

        if (isset($queryParams['agentId']) && $queryParams['agentId']) {
            $query->where('company_agents.agent_id', $queryParams['agentId']);
        }
        $query->groupBy('users.id', 'agency_id', 'agency_companies.id');
        return $query;
    }

    public static function getMaxCompaniesCanCreateBy($subscriptionType)
    {
        $subscriptions = [
            static::$SUBSCRIPTION_TYPE_BASE => env('APP_BASE_AGENCY_MAX_COMPANIES', 5),
            static::$SUBSCRIPTION_TYPE_PREMIUM => env('APP_PREMIUM_AGENCY_MAX_COMPANIES', 100),
        ];
        return isset($subscriptions[$subscriptionType]) ? $subscriptions[$subscriptionType] : 0;
    }
}
