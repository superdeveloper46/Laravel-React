<?php

namespace App\Http\Controllers\Api\Management\Agency;

use App\Models\Agent;
use App\Models\Company;
use App\Services\MailService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AgentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $itemsPerPage = (int)$request->get('per_page', 10);
        $page = (int)$request->get('current_page', 1);
    
        return $request->user()->getAgents($request->only([
            'search',
            'companyId',
            'name',
            'campaigns',
            'leads',
            'avg_response',
            'showDeleted',
        ]))->paginate($itemsPerPage, ['*'], 'agents', $page);
    }
    
    public function campaigns(Request $request, $agentId) {
        $itemsPerPage = (int)$request->get('per_page', 10);
        $page = (int)$request->get('current_page', 1);

        $agent = $request->user()->getAgent($agentId);

        return $agent->getCampaignsBy($request->only([
            'showDeleted',
            'name',
            'type',
            'leads',
            'avg_time_response',
        ]))->paginate($itemsPerPage, ['*'], 'agents', $page);
    }
    
    public function graph(Request $request, $agentId, $graphType)
    {
        switch ($graphType) {
            case 'contacted': {
                $companyAgencyIds = null;
                $companyIds = $request->get('companyIds');
                $startDate = $request->get('startDate', Carbon::now()->startOfWeek());
                $endDate = $request->get('endDate', Carbon::now()->endOfWeek());
                $agent = $request->user()->getAgent($agentId);
                
                if ($companyIds) {
                    $companyAgencyIds = collect($companyIds)->map(function ($companyId) use ($request) {
                        return $request->user()->getCompanyBy($companyId)->pivot->id;
                    });
                }
                
                return Agent::contactedLeadsGraph($startDate, $endDate, $agent->id, $companyAgencyIds);
            }
            case 'pie': {
                $companyAgencyIds = null;
                $companyIds = $request->get('companyIds');
                $startDate = $request->get('startDate', Carbon::now()->startOfWeek());
                $endDate = $request->get('endDate', Carbon::now()->endOfWeek());
                $agent = $request->user()->getAgent($agentId);
                
                if ($companyIds) {
                    $companyAgencyIds = collect($companyIds)->map(function ($companyId) use ($request) {
                        return $request->user()->getCompanyBy($companyId)->pivot->id;
                    });
                }
                
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
    public function store(Request $request, Agent $agent)
    {
        try {
            \DB::beginTransaction();
            $agent->handleAvatar($request);
            $request->merge(['agent_agency_id' => $request->user()->id]);
            $agent = $agent->createAgent($request->only([
                'name',
                'avatar_id',
                'agent_agency_id',
                'phone',
                'email',
                'twilio_sid',
                'twilio_token',
                'twilio_mobile_number',
                'twilio_app_sid',
                'password',
                'password_confirmation'
            ]));

            $newCompanies = $request->get('new_companies');
            $companyName = '';
            if ($newCompanies) {
                foreach ($newCompanies AS $newCompany) {
                    $newCompany = $request->user()->getCompanyBy($newCompany);
                    $companyName .= ($companyName ? ", {$newCompany->name}" : "{$newCompany->name}");
                    $newCompany->agents()->attach($agent);
                }
            }

            MailService::sendMail('emails.agent-welcome', [
                'agent' => $agent,
                'companyName' => $companyName,
                'password' => $request->get('password'),
            ],
                $agent->email,
                env('APP_AGENT_WELCOME_EMAIL_SUBJECT', "Welcome To ConvertLead {$agent->name}"
            ));

            \DB::commit();
            return $agent;
        } catch (\PDOException $e) {
            // Woopsy
            \DB::rollBack();
            throw $e;
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        return $request->user()->getAgent($id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function update(Request $request, $id)
    {
        try {
            
            \DB::beginTransaction();
            $request->merge(['agent_agency_id' => $request->user()->id]);
            $agent = $request->user()->getAgent($id);
            $oldCompanies = $request->get('companies');
            $newCompanies = $request->get('new_companies');

            if(isset($newCompanies)){
                if ($oldCompanies) {
                    foreach ($oldCompanies AS $companyId) {
                        $company = $request->user()->getCompanyBy($companyId);
                        $company->agents()->detach($agent);                    
                        $campaigns = $company->campaigns()->get();
                        foreach ($campaigns as $campaign) {
                            $campaign->agents()->detach($agent);
                        }
                    }         
                }
                if(count($newCompanies) >0){
                    foreach ($newCompanies AS $newCompany) {
                        $newCompany = $request->user()->getCompanyBy($newCompany);
                        $newCompany->agents()->attach($agent);
                    }
                }                  

            }            

            $agent->handleAvatar($request);
            $agent->updateUser($request->except('role'));
            \DB::commit();
            return $agent;
        } catch (\PDOException $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function destroy(Request $request, $id)
    {
        try {
            \DB::beginTransaction();
            $agent = $request->user()->getAgent($id);
            $companies = $agent->companies()->get();
            // foreach ($companies AS $company) {
            //     $company->agents()->detach($id);
            // }
            $agent->delete();
            \DB::commit();
            return $agent;
        } catch (\PDOException $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function restore(Request $request, $id)
    {
        $agent = $request->user()->getAgent($id);
        if ($agent) {
            $agent->restore();
        }
        return $agent;
    }
}

