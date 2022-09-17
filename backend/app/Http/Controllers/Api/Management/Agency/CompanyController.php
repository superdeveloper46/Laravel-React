<?php

namespace App\Http\Controllers\Api\Management\Agency;

use App\Models\Company;
use App\Models\Lead;
use App\Models\Permission;
use App\Services\MailService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $itemsPerPage = (int)$request->get('per_page', 100);
        $page = (int)$request->get('current_page', 1);
        
        return $request->user()->getCompanies($request->only([
            'reduced',
            'agentId',
            'search',
            'name',
            'deals',
            'leads',
            'agents',
            'showDeleted',
            'avg_response',
        ]))->paginate($itemsPerPage, ['*'], 'page', $page);
    }

    public function companyLeadStats(Request $request, $companyId) {
        $companyLeadStats = $request->user()->getCompanyLeadStatsBy(
            $companyId,
            $request->get('fromDate'),
            $request->get('toDate'),
            $request->get('agentId')
        );
        
        return response()->json($companyLeadStats);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Company $company)
    {
        $user = $request->user();
        $countCompanies = $request->user()->companies()->count();
        if ($countCompanies >= $user->max_agency_companies) {
            $error = ValidationException::withMessages([
                'max_agency_companies' => ['You have exceeded the maxim number of allowed companies to create!'],
            ]);
            throw $error;
        }
        $company->handleAvatar($request);
        $company->createCompany($request->only([
            'twilio_sid',
            'twilio_token',
            'twilio_mobile_number',
            'name',
            'is_locked',
            'avatar_id',
            'phone',
            'email',
            'password',
            'password_confirmation']));
        $request->user()->companies()->attach($company);

        MailService::sendMail('emails.company-welcome', [
            'agency' => $request->user(),
            'company' => $company,
            'password' => $request->get('password'),
        ],
            $company->email,
            env('APP_COMPANY_WELCOME_EMAIL_SUBJECT', 'Welcome To ConvertLead ')
        );

        $company->setupTwilioSmsWebHook(
            $request->get('twilio_sid'),
            $request->get('twilio_token'),
            $request->get('twilio_mobile_number')
        );

        return $company;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        return $request->user()->getCompanyBy($id);
    }

    public function graph(Request $request, $companyId, $graphType)
    {
        switch ($graphType) {
            case 'contacted': {
                $startDate = $request->get('startDate', Carbon::now()->startOfWeek());
                $endDate = $request->get('endDate', Carbon::now()->endOfWeek());
                $company = $request->user()->getCompanyBy($companyId);
                if(isset($company->pivot))
                    $c_p_id = $company->pivot->id; 
                else
                    $c_p_id = null; 
                return Company::contactedLeadsGraph($startDate, $endDate, $c_p_id, $request->get('agentId'));
            }
        }
        throw new \Exception('Wrong graph type!');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $company = $request->user()->getCompanyBy($id);
        $company->handleAvatar($request);
        $company->updateUser($request->except('role'));

        $company->setupTwilioSmsWebHook(
            $request->get('twilio_sid'),
            $request->get('twilio_token'),
            $request->get('twilio_mobile_number')
        );

        return $company;
    }
    
    public function lockStatus(Request $request, $id)
    {
        $company = $request->user()->getCompanyBy($id);
        $company->pivot->is_locked = $request->get('is_locked');
        if ($company->pivot->is_locked) {
            $permissions = \App\Models\Permission::whereIn('name',
                [
                    Permission::$PERMISSION_AGENT_WRITE,
                    Permission::$PERMISSION_DEAL_WRITE
                ]
            )->get();
            $company->permissions()->detach($permissions);
        } else {
            $permissions = \App\Models\Permission::whereIn('name',
                [
                    Permission::$PERMISSION_AGENT_WRITE,
                    Permission::$PERMISSION_DEAL_WRITE
                ]
            )->get();
            $company->permissions()->attach($permissions);
        }
        $company->pivot->save();
        return $company;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        $company = $request->user()->getCompanyBy($id);
        $company->delete();
        return $company;
    }
}
