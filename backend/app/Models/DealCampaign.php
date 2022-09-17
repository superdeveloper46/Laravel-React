<?php

namespace App\Models;

use App\Services\MailService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Mockery\Exception;

class DealCampaign extends Model
{
    use SoftDeletes;
    public static $INTEGRATION_OPTIN_FORM = 'OPTIN_FORM';
    public static $INTEGRATION_FACEBOOK = 'FACEBOOK';
    public static $INTEGRATION_ZAPIER = 'ZAPIER';
    public static $INTEGRATION_WEBHOOK = 'WEBHOOK';
    public static $INTEGRATION_UNBOUNCE = 'UNBOUNCE';
    public static $INTEGRATION_INSTAPAGE = 'INSTAPAGE';
    public static $INTEGRATION_CLICKFUNNELS = 'CLICKFUNNELS';

    protected $table = 'deal_campaigns';

    protected $fillable = [
        'name',
        'uuid',
        'description',
        'deal_id',
        'agency_company_id',
        'integration',
        'integration_config',
    ];
    
    protected $appends = ['agents', 'company', 'fbIntegrations'];
    
    public function deal() {
        return $this->belongsTo('App\Models\Deal');
    }
    
    public function company() {
        return $this->belongsToMany('App\Models\Company', 'agency_companies', 'id', 'company_id', 'agency_company_id');
    }

    public function integrations() {
        return $this->hasMany(
            'App\Models\DealCampaignFacebookIntegration',
            'deal_campaign_id', 'id');
    }

    public function agents() {
        return $this->belongsToMany('App\Models\Agent', 'deal_campaign_agents');
    }
    
    public function leads() {
        return $this->belongsTo('App\Models\Lead', 'id', 'deal_campaign_id');
    }
    
    public function getAgentsAttribute() {
        $agents = $this->agents()
            ->orderBy('agent_leads_count', 'ASC')
            ->withPivot('agent_leads_count')->get();
        if ($agents) {
            return collect($agents)->map(function ($agent) {
                return $agent->only('name', 'avatar_path', 'id', 'pivot');
            });
        }
        return $agents;
    }

    public function getCompanyAttribute() {
        $company = $this->company()->first();
        if ($company) {
            return $company->only('name', 'avatar_path', 'id', 'pivot');
        }
        return null;
    }

    public function getFbIntegrationsAttribute() {
         return $this->integrations()->get();
    }
    
    public function getLeadsCountAttribute() {
        return $this->leads()->count();
    }
    
    public static function createCampaign(Request $request) {
        try {
            \DB::beginTransaction();
            \Validator::validate($request->all(), [
                'name' => 'required|string',
                'deal_id' => 'required|int',
                'uuid' => 'required|string',
                'agency_company_id' => 'required|int',
                'integration' => 'required|string',
                'agents' => 'required'
            ]);
    
            $campaign = (new DealCampaign())->fill($request->only([
                'name',
                'uuid',
                'deal_id',
                'agency_company_id',
                'integration_config',
                'integration',
                'description'
            ]));
            $campaign->save();
            $campaign->agents()->attach($request->get('agents'));
            $company = $campaign->company()->first();

            $campaign->agents()->get()->map(function ($agent) use ($campaign, $company) {
                MailService::sendMail('emails.new-agent-campaign', [
                    'agent' => $agent,
                    'campaign' => $campaign,
                    'company' => $company,
                ],
                    $agent->email,
                    env('APP_AGENT_CAMPAIGN_EMAIL_SUBJECT', "New ConvertLead Integration - {$campaign->name}")
                );
            });

            MailService::sendMail('emails.new-company-campaign', [
                'campaign' => $campaign,
                'company' => $company,
            ],
                $company->email,
                env('APP_AGENT_CAMPAIGN_EMAIL_SUBJECT', "New ConvertLead Integration - {$campaign->name}")
            );

            \DB::commit();
            return $campaign;
        } catch (\Exception $exception) {
            \DB::rollBack();
            throw $exception;
        }
    }

    public function updateCampaign(Request $request) {
        try {

            \DB::beginTransaction();
            $request->merge([
                'uuid' => ($this->uuid ? $this->uuid : Str::uuid())
            ]);
        
            $this->fill($request->only([
                'name',
                'uuid',
                'integration_config',
                'integration',
                'description'
            ]));

            $this->save();
            $this->agents()->detach($this->agents()->get());
            if ($request->get('agents')) {
                $this->agents()->attach($request->get('agents'));
            }

            \DB::commit();
        } catch (Exception $exception) {
            \DB::rollBack();
            throw $exception;
        }
    }
}
