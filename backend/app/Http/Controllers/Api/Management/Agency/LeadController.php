<?php

namespace App\Http\Controllers\Api\Management\Agency;

use App\Models\Lead;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class LeadController extends Controller
{
    public function all(Request $request) {
        $itemsPerPage = (int)$request->get('per_page', 10);
        $page = (int)$request->get('current_page', 1);
        return $request
            ->user()
            ->getLeads($request->only([
                'search',
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
                'sort_by'
            ]))
            ->paginate($itemsPerPage, ['*'], 'page', $page);
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $company, $id)
    {
        $lead = $request->user()->getCompanyBy($company)->getLeadBy($id)->load(
            'leadNotes'
        );

        $leadNotes = $lead->leadNotes;
        if ($request->get('resetIsNew') && $leadNotes) {
            collect($leadNotes)->each(function ($leadNote) {
                $leadNote->is_new = 0;
                $leadNote->save();
            });
        }

        return $lead;
    }
    
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $company, $id)
    {
        $company = $request->user()->getCompanyBy($company);
        $lead = $company->getLeadBy($id);
        $request->merge([
            'id' => $id
        ]);

        $lead->updateLead($request);

        return $lead;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function store(Request $request, $company) {
        $request->user()->getCompanyBy($company);
        return Lead::createLead($request);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $company, $id)
    {
        $company = $request->user()->getCompanyBy($company);
        $lead = $company->getLeadBy($id);
        $lead->delete();
        return $lead;
    }
}
