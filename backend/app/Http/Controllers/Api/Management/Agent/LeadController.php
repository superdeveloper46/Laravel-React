<?php

namespace App\Http\Controllers\Api\Management\Agent;

use App\Models\Agent;
use App\Models\Lead;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class LeadController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return array
     */
    public function index(Request $request)
    {
        $itemsPerPage = (int)$request->get('per_page', 10);
        $page = (int)$request->get('current_page', 1);
        
        if ($request->get('statuses')) {
            $request->merge([
                'statuses' => explode(',', $request->get('statuses'))
            ]);
        }
        $data =  $request
            ->user()
            ->getLeads($request->only([
                'search',
                'statuses',
                'showDeleted',
                'startDate',
                'endDate',
                'agentId',
                'companyId',
                'campaignId',
                'statusType',
                'status',
                'name',
                'email',
                'company',
                'campaign',
            ]))
            ->paginate($itemsPerPage, ['*'], 'page', $page);

        return [
            'leads' => $data,
            'new_leads_count' => $request->user()->countNewLeads(),
        ];
    }
    
    
    public function graph(Request $request, $graphType) {
        switch ($graphType) {
            case 'contacted': {
                $startDate = $request->get('startDate', Carbon::now()->startOfWeek());
                $endDate = $request->get('endDate', Carbon::now()->endOfWeek());
                $agent = $request->user();
                
                return Agent::contactedLeadsGraph($startDate, $endDate, $agent->id, [], 'Y-m-d', true);
            }
            case 'pie': {
                $companyAgencyIds = null; 
                $startDate = $request->get('startDate', Carbon::now()->startOfWeek());
                $endDate = $request->get('endDate', Carbon::now()->endOfWeek());
                $agent = $request->user();  
                
                return Agent::contactedLeadsGraph($startDate, $endDate, $agent->id, $companyAgencyIds, null, true);
            }   
        }
        throw new \Exception('Wrong graph type!');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function store(Request $request)
    {
        return Lead::createLead($request);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        return $request->user()->getLeadBy($id)->load(
            'leadNotes', 'reminders'
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return void
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return void
     */
    public function destroy($id)
    {
        //
    }
}
