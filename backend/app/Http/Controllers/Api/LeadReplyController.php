<?php

namespace App\Http\Controllers\Api;

use App\Models\DealAction;
use App\Models\Lead;
use App\Models\Agent;
use App\Models\LeadNote;
use App\Models\LeadStatus;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Intervention\Image\Facades\Image;

use Twilio\TwiML\VoiceResponse;

class LeadReplyController extends Controller
{

    public function onSMSReply(Request $request)
    {
        try {
            $fromNumber = ltrim(str_replace('+', '',  $request->get('From', '')), '00');
            $messageBody = $request->get('Body');

            \Log::critical('Sms reply');
            \Log::critical('number=' . $fromNumber . 'message=' . $messageBody);

            $leads = Lead::query()
                ->where('phone', 'like', '%' . $fromNumber . '%')
                ->orderBy('id', 'desc')
                ->get();

            if (empty($leads)) {
                abort(400, 'Lead not found!');
            }

            foreach ($leads as $lead) {
                $dealAction = DealAction::query()
                    ->select('deal_actions.*')
                    ->join('lead_action_histories as lah', 'lah.deal_action_id', '=', 'deal_actions.id')
                    ->join('leads', 'leads.id', '=', 'lah.lead_id')
                    ->where('deal_actions.deal_id', $lead->campaign['deal']['id'])
                    ->where('deal_actions.is_root', 1)
                    ->where('leads.id', $lead->id)
                    ->where('lah.is_completed', 1)
                    ->whereRaw('lah.deleted_at IS NULL')
                    ->whereRaw('deal_actions.deleted_at IS NULL')
                    ->where(function ($query) {
                        $query
                            ->where('deal_actions.lead_reply_type', DealAction::LEAD_REPLY_TYPE_SMS_REPLY)
                            ->orWhere('deal_actions.lead_reply_type', DealAction::LEAD_REPLY_TYPE_SMS_REPLY_CONTAIN);
                    })
                    ->orderBy('deal_actions.id', 'desc')
                    ->first();


                if (empty($dealAction)) {
                    \Log::critical('Action not found forNumber' . $fromNumber);
                    // abort(400, 'Deal action not found!');
                    // tempcode for sms reply
                    LeadNote::create([
                        'lead_status_id' => 10,
                        'lead_id' => $lead->id,
                        'agent_id' => $lead->agent_id,
                        'message' => $messageBody,
                    ]);
                } else {
                    \Log::critical('dealAction=' . $dealAction->id . " lead_reply_type=>" . $dealAction->lead_reply_type);

                    if ($dealAction->lead_reply_type === DealAction::LEAD_REPLY_TYPE_SMS_REPLY_CONTAIN) {
                        $keywords = explode(',', $dealAction->lead_reply_contains);
                        foreach ($keywords as $keyword) {
                            $contains = stripos($messageBody, $keyword) !== -1;
                            if ($contains) {
                                $this->leadReplyNote($lead, $dealAction, $fromNumber, $messageBody);
                                $dealAction->scheduleNextLeadAction($lead);
                                break;
                            }
                        }
                    } elseif ($dealAction->lead_reply_type === DealAction::LEAD_REPLY_TYPE_SMS_REPLY) {
                        $this->leadReplyNote($lead, $dealAction, $fromNumber, $messageBody);
                        $dealAction->scheduleNextLeadAction($lead);
                    }
                }
            }
            //code...
        } catch (\Throwable $th) {
            //throw $th;
            \Log::critical('------------------------------' . $th);
        }
    }

    public function leadReplyNote($lead, $dealAction, $fromNumber, $messageBody)
    {
        \Log::critical('leadReplyNote===========>' . $lead->id . "==>" . $dealAction->id . "=>" . $fromNumber . "==>" . $messageBody);

        LeadNote::create([
            'lead_status_id' => $lead->lead_status_id,
            'lead_id' => $lead->id,
            'agent_id' => $lead->agent_id,
            'deal_action_id' => $dealAction->id,
            'message' => "Lead reply: From: {$fromNumber}, message: {$messageBody}",
        ]);
    }

    public function onMailReply(Request $request, $leadId, $dealActionId)
    {
        $lead = Lead::query()->where('id', $leadId)->firstOrFail();
        $dealAction = DealAction::findOrFail($dealActionId);

        if ($dealAction) {
            LeadNote::create([
                'lead_status_id' => $lead->lead_status_id,
                'lead_id' => $lead->id,
                'agent_id' => $lead->agent_id,
                'deal_action_id' => $dealAction->id,
                'message' => "Lead mail opened!",
            ]);
            $dealAction->scheduleNextLeadAction($lead);
        }

        $img = Image::make(public_path('images/pixel.png'))->resize(1, 1);
        return $img->response('jpg');
    }

    public function onVoiceReply(Request $request)
    {
        $response = new VoiceResponse();
        $from = $request->get('Caller', '');
        $to = $request->get('To', '');

        try {
            $agent = Agent::query()->where('twilio_mobile_number', 'like', $to)->orderBy('id', 'desc')->firstOrFail();
            $lead = Lead::where('phone', 'like', $agent->phone)->orderBy('id', 'desc')->firstOrFail();

            $recordingStatus = action([TwilioController::class, 'recording'], ['leadId' => $lead->id]);
            $dial = $response->dial('', [
                'callerId' =>  $to,
                'record' => 'record-from-ringing-dual',
                'recordingStatusCallbackMethod' => 'POST',
                'recordingStatusCallbackEvent' => 'completed',
                'recordingStatusCallback' => $recordingStatus,
            ]);
            $agentPhone = ($agent->twilio_mobile_number ? $agent->phone : $lead->company['phone']);

            $dial->number($agentPhone);

             LeadNote::create([
                 'lead_status_id' => $lead->lead_status_id,
                 'lead_id' => $lead->id,
                 'agent_id' => $lead->agent_id,
                 'message' => "Lead Call back Agent!",
             ]);
        } catch (\Throwable $th) {
            \Log::critical('onVoiceReply end:::::::::agent=>' . $agent->id . "lead=>" . $lead->id . "agentPhone=>" . $agentPhone. ', Error message: ' . $th->getMessage());
        }

        return (string)$response;
    }
}
