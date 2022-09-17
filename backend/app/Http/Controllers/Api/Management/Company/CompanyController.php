<?php

namespace App\Http\Controllers\Api\Management\Company;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CompanyController extends Controller
{
    public function graph(Request $request, $graphType)
    {
        switch ($graphType) {
            case 'contacted': {
                $startDate = $request->get('startDate', Carbon::now()->startOfWeek());
                $endDate = $request->get('endDate', Carbon::now()->endOfWeek());
                $agencyCompanyIds = $request->user()->agencies()->get()->map(function ($agency) {
                    return $agency->pivot->id;
                });
                return Company::contactedLeadsGraph($startDate, $endDate, $agencyCompanyIds, $request->get('agentId'));
            }
        }
        throw new \Exception('Wrong graph type!');
    }
}
