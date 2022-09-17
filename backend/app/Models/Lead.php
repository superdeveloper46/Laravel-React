<?php

namespace App\Models;

use App\Services\MailService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;

/**
 * Class Lead
 *
 * @property Agent $agent
 */
class Lead extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'agency_company_id',
        'deal_campaign_id',
        'agent_id',
        'lead_status_id',
        'fullname',
        'email',
        'phone',
        'metadata'
    ];

    protected $appends = [
        'campaign',
        'status',
        'statusInfo',
        'company',
        'agent',
        'smsReplayCount'
    ];

    public function campaign()
    {
        return $this->hasOne('App\Models\DealCampaign', 'id', 'deal_campaign_id');
    }

    public function company()
    {
        return Company::join('agency_companies AS ac', 'ac.company_id', 'users.id');
    }

    public function agent()
    {
        return $this->hasOne('App\Models\Agent', 'id', 'agent_id');
    }

    public function status()
    {
        return $this->hasOne('App\Models\LeadStatus', 'id', 'lead_status_id');
    }

    public function leadNotes()
    {
        return $this->hasMany('App\Models\LeadNote', 'lead_id', 'id');
    }

    public function reminders()
    {
        return $this->hasMany('App\Models\Reminder', 'lead_id', 'id');
    }

    public function getCampaignAttribute()
    {
        $campaign = $this->campaign()->withTrashed()->first();
        if ($campaign) {
            return $campaign->only(['id', 'name', 'uuid', 'description', 'deal']);
        }
        return null;
    }

    public function getCompanyAttribute()
    {
        $company = $this->company()->selectRaw('users.*')->where('ac.id', $this->agency_company_id)->withTrashed()->first();
        if ($company) {
            return $company->only([
                'id',
                'name',
                'email',
                'avatar_path',
                'twilio_mobile_number',
                'phone',
                'twilio_sid',
                'twilio_token',
                'twilio_app_sid',
            ]);
        }
        return null;
    }

    public function getAgentAttribute()
    {
        $agent = $this->agent()->selectRaw('users.*')->withTrashed()->first();
        if ($agent) {
            return $agent->only(['id', 'name', 'avatar_path', 'phone', 'twilio_mobile_number', 'twilio_app_sid']);
        }
        return null;
    }

    public function getStatusAttribute()
    {
        $status = $this->status()->withTrashed()->first();
        if ($status) {
            return $status->type;
        }
        return LeadStatus::$STATUS_NEW;
    }

    public function getStatusInfoAttribute()
    {
        $status = $this->status()->withTrashed()->first();
        if ($status) {
            return $status;
        }
        $status = new LeadStatus();
        $status->name = 'New';
        $status->type = LeadStatus::$STATUS_NEW;
        return $status;
    }

    public function getSmsReplayCountAttribute()
    {
        // $notes = $this->leadNotes()
        //     ->join('deal_actions', 'deal_actions.id', '=', 'lead_notes.deal_action_id')
        //     ->where('lead_notes.is_new', 1)
        //     ->where('deal_actions.type', '=', DealAction::TYPE_SMS_MESSAGE)
        //     ->where(function ($query) {
        //         $query->where('deal_actions.lead_reply_type', '=', DealAction::LEAD_REPLY_TYPE_SMS_REPLY)
        //             ->orWhere('deal_actions.lead_reply_type', '=', DealAction::LEAD_REPLY_TYPE_SMS_REPLY);
        //     });

        $notes = $this->leadNotes()
            ->where('lead_notes.is_new', 1)
            ->where('lead_notes.lead_status_id', 10); // tempcode

        if ($notes) {
            return $notes->count();
        }
        return 0;
    }

    public function getLeadNoteBy($id)
    {
        return $this->leadNotes()->where('id', $id)->firstOrFail();
    }

    public function updateLead(Request $request)
    {
        try {
            \DB::beginTransaction();

            $oldStatus = $this->status;
            $status = $request->get('status');
            $leadStatus = LeadStatus::where('type', $status)->firstOrFail();
            $request->merge(['lead_status_id' => $leadStatus->id]);
            $hasNewStatus = $this->lead_status_id !== $leadStatus->id;
            $agencyCompanyId = DealCampaign::findOrFail($request->get('deal_campaign_id'))->agency_company_id;

            $request->merge([
                'agency_company_id' => $agencyCompanyId
            ]);

            \Validator::validate($request->all(), [
                'fullname' => 'required|string|max:255',
                'email' => 'required|string|max:255',
                'phone' => 'required|string|max:255',
                'agent_id' => 'required|int',
                'deal_campaign_id' => 'required|int',
                'agency_company_id' => 'required|int',
                'status' => 'required|string',
            ]);

            $this->fill($request->only([
                'fullname',
                'email',
                'phone',
                'agent_id',
                'metadata',
                'deal_campaign_id',
                'lead_status_id',
                'agency_company_id',
            ]));

            if ($hasNewStatus) {
                LeadNote::create([
                    'lead_status_id' => $leadStatus->id,
                    'lead_id' => $this->id,
                    'agent_id' => $request->user()->id,
                    'message' => "Status changed from {$oldStatus} to {$status}! ",
                ]);
            }
            $this->save();

            \DB::commit();
            return $this;
        } catch (\Exception $exception) {
            \DB::rollBack();
            throw $exception;
        }
    }

    public static function createLead(Request $request)
    {
        try {
            \DB::beginTransaction();
            $campaign = DealCampaign::findOrFail($request->get('deal_campaign_id'));
            $deal = $campaign->deal()->first();
            $company = $campaign->company()->first();
            $agencyCompanyId = $campaign->agency_company_id;

            $request->merge([
                'agency_company_id' => $agencyCompanyId
            ]);

            \Validator::validate($request->all(), [
                'fullname' => 'required|string|max:255',
                'email' => 'required|string|max:255',
                'phone' => 'required|string|max:255',
                'agent_id' => 'required|int',
                'deal_campaign_id' => 'required|int',
                'agency_company_id' => 'required|int',
                'status' => 'required|string',
            ]);

            $status = $request->get('status');
            $leadStatus = LeadStatus::where('type', $status)->firstOrFail();
            $request->merge(['lead_status_id' => $leadStatus->id]);

            $lead = self::create($request->only([
                'lead_status_id',
                'fullname',
                'email',
                'phone',
                'agent_id',
                'metadata',
                'deal_campaign_id',
                'agency_company_id',
            ]));

            LeadNote::create([
                'lead_status_id' => $leadStatus->id,
                'lead_id' => $lead->id,
                'agent_id' => $request->user()->id,
                'message' => 'has manually created a lead.',
            ]);

            \DB::commit();

            $notification = [
                'title' => 'New Lead',
                'body' => 'New Lead created: ' . $lead->fullname,
                'sound' => true,
                'badge' => 1
            ];

            $data = [
                'url' => '/companies/leads/all'
            ];

            $tokenList = Device::getTokenListFromAgentIds([$lead->agent_id]);
            self::notification($tokenList, $notification, $data);
            $agent = $lead->agent()->first();

            MailService::sendMail(
                'emails.new-lead',
                [
                    'deal' => $deal,
                    'lead' => $lead,
                    'campaign' => $campaign,
                    'agent' => $agent,
                ],
                [
                    $agent->email,
                    $company->email,
                ],
                env('APP_NEW_LEAD_EMAIL_SUBJECT', "New Lead Manually Created")
            );

            return $lead;
        } catch (\Exception $exception) {
            \DB::rollBack();
            throw $exception;
        }
    }

    public static function notification($tokenList, $notification, $data = null)
    {
        $fcmUrl = \Config::get('services.fcm.api_host');
        $api_key = \Config::get('services.fcm.api_key');

        $fcmNotification = [
            //'to'        => $token, //single token
            'registration_ids' => $tokenList, //multple token array
            'notification' => $notification,
            'data' => $data
        ];

        $headers = [
            'Authorization: key=' . $api_key,
            'Content-Type: application/json'
        ];


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fcmUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcmNotification));
        $result = curl_exec($ch);
        curl_close($ch);

        return true;
    }
}
