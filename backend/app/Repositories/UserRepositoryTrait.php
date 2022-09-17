<?php

namespace App\Repositories;

use App\Models\DealCampaign;
use App\Models\Lead;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use DB;

/**
 * Trait UserRepositoryTrait
 * @package App\Repositories
 */
trait UserRepositoryTrait
{

    /**
     * @param \Request $request
     * @return mixed
     */
    public function getCompanyDeals(Request $request)
    {
        $query =  $this
            ->deals()
            ->join('users AS company', 'company.id', '=', 'company_id')
            ->leftJoin('deal_campaigns AS dc', 'dc.deal_id', '=', 'deals.id')
            ->leftJoin('leads', 'leads.deal_campaign_id', '=', 'dc.id')

            ->leftjoin(
                DB::raw('(SELECT * FROM `leads` WHERE lead_status_id = 9) AS sold_c'),
                function ($join) {
                    $join->on('sold_c.id', '=', 'leads.id');
                }
            )

            ->leftjoin(
                DB::raw('(SELECT * FROM `leads` WHERE lead_status_id = 7) AS missed_c'),
                function ($join) {
                    $join->on('missed_c.id', '=', 'leads.id');
                }
            )

            ->leftjoin(
                DB::raw('(SELECT * FROM `leads` WHERE lead_status_id = 4 OR lead_status_id = 5 OR lead_status_id = 6) AS contacted_s'),
                function ($join) {
                    $join->on('contacted_s.id', '=', 'leads.id');
                }
            )

            ->whereRaw('company.deleted_at IS NULL');

        if ($request->has('search')) {
            $query->whereLike('deals.name', "%{$request->get('search')}%");
        }

        if ($request->has('companyId')) {
            $query->where('company.id', $request->get('companyId'));
        }

        if ($request->has('sortBy')) {
            [$field, $direction] = explode('.', $request->get('sortBy', ''));
            $query->sortBy($field, $direction);
        }

        $query->groupBy('deals.id')
            ->selectRaw('count(DISTINCT leads.id) AS leadsCount, 
                         COUNT(sold_c.id) leads_conversion,
                         COUNT(missed_c.id) leads_missed,
                         COUNT(contacted_s.id) leads_contacted
                         ')
            ->withTrashed();

        return $query;
    }

    public function getDealsStatistics(Request $request)
    {
        // $query = Lead::query()
        //     ->join('deal_campaigns AS dc', 'dc.id', '=', 'leads.deal_campaign_id')
        //     ->join('agency_companies AS ac', 'ac.id', '=', 'dc.agency_company_id');

        // if ($request->has('dealIds')) {
        //     $query->whereIn('dc.deal_id', $request->get('dealIds'));
        // }

        // // $fromDate = $request->get('fromDate');
        // // $toDate = $request->get('toDate');

        // if ($fromDate && $toDate) {
        //     $query->whereBetween('leads.created_at', [
        //         "DATE_FORMAT('$fromDate', '%Y-%m-%d')",
        //         "DATE_FORMAT('$toDate', '%Y-%m-%d')",
        //         // "'2001-08-06 00:00:00'",
        //         // "'2021-08-13 00:00:00'"
        //     ]);
        // }

        // $datePeriod = CarbonPeriod::create(
        //     now()->setTimeFromTimeString($fromDate)->toDateString(),
        //     now()->setTimeFromTimeString($toDate)->toDateString()
        // );

        // $query
        //     ->selectRaw('
        //          DATE_FORMAT(leads.created_at, \'%Y-%m-%d\') AS created_date,
        //          COUNT(DISTINCT leads.id) as leadsCount,
        //          COUNT(DISTINCT dc.integration) as integrationCount,
        //          dc.integration
        //     ')
        //     ->groupBy(['created_date', 'dc.integration']);

        // $leadsStats = $query->get() ?? [];

        $where_query = "";
        if ($request->has('dealIds')) {
            $where_query = "dc.deal_id IN (" . implode(",", $request->get('dealIds')) . ") AND ";
        }

        $fromDate = $request->get('fromDate');
        if (!$fromDate || $fromDate == 'null') $fromDate = "2000-01-01";
        $toDate = $request->get('toDate');

        $query = "SELECT DATE_FORMAT(leads.created_at, '%Y-%m-%d') AS created_date,
        COUNT(DISTINCT leads.id) AS leadsCount,
        COUNT(DISTINCT dc.integration) AS integrationCount, 
        dc.integration 
        FROM `leads` INNER JOIN `deal_campaigns` AS `dc` ON `dc`.`id` = `leads`.`deal_campaign_id` 
        INNER JOIN `agency_companies` AS `ac` ON `ac`.`id` = `dc`.`agency_company_id` 
        WHERE " . $where_query . " `leads`.`created_at` BETWEEN 
        DATE_FORMAT('" . $fromDate . "', '%Y-%m-%d') AND DATE_FORMAT('" . $toDate . "', '%Y-%m-%d') 
        AND `leads`.`deleted_at` IS NULL 
        GROUP BY `created_date`, `dc`.`integration`";

        $temp = \DB::select(\DB::raw($query));

        $aaa = [];
        $report = [];
        $totalLeadsCount = 0;
        for ($i = 0; $i < count($temp); $i++) {
            $totalLeadsCount += $temp[$i]->leadsCount;
        }

        for ($i = 0; $i < count($temp); $i++) {
            $temp[$i]->leadsPercentage = round((($temp[$i]->leadsCount / $totalLeadsCount) * 100));
            $aaa[$i]['records'] = $temp[$i];
            $aaa[$i]['name'] = Carbon::parse($temp[$i]->created_date)->shortDayName;
            $aaa[$i]['totalLeadsCount'] = $temp[$i]->leadsCount;
        }

        $report = [
            'records' => $aaa,
            'totalLeadsCount' => $totalLeadsCount
        ];

        return $report;

        // $datePeriod = collect($datePeriod)->map(function ($period) {
        //     return $period->format('Y-m-d');
        // })->flip()->map(function ($index, $date) use ($leadsStats) {
        //     $records = collect($leadsStats)->filter(function ($record) use ($date) {
        //         return $record->created_date === $date;
        //     })->map(function ($record) {
        //         return $record(['leadsCount', 'integrationCount', 'integration', 'created_date']);
        //     });

        //     $totalLeadsCount = collect($records)->reduce(function ($collect, $record) {
        //         $collect += $record['leadsCount'];
        //         return $collect;
        //     }, 0);

        //     return [
        //         'records' => collect($records)->map(function ($record) use ($totalLeadsCount) {
        //             $record['leadsPercentage'] = round((($record['leadsCount'] / $totalLeadsCount) * 100));
        //             return $record;
        //         })->values()->toArray(),
        //         'totalLeadsCount' => $totalLeadsCount,
        //         'name' => Carbon::parse($date)->shortDayName,
        //     ];
        // })->values()->toArray();

        // $report = [
        //     'records' => $datePeriod,
        //     'totalLeadsCount' => collect($leadsStats)->reduce(function ($collect, $record) {
        //         $collect += $record->leadsCount;
        //         return $collect;
        //     }, 0)
        // ];

        // return $report;
    }
}
