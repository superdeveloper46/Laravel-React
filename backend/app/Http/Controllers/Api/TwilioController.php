<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Company;
use App\Models\Lead;
use App\Models\LeadNote;
use App\Models\LeadStatus;
use Twilio\Jwt\ClientToken;
use Twilio\TwiML\VoiceResponse;
use Illuminate\Http\Request;
use Twilio\Rest\Client;

class TwilioController extends Controller
{
    public function conference(Request $request, $companyId, $agentId)
    {
        $response = new VoiceResponse();
        $agent = Agent::findOrFail($agentId);
        $company = Company::findOrFail($companyId);
        $from = $request->get('Caller', '');
        $agentTwilioNumber = ($agent->twilio_mobile_number ? $agent->twilio_mobile_number : $company->twilio_mobile_number);

        // agent Call lead from client
        if (stripos($from, 'client:Anonymous') !== false) {

            if ($request->get('leadId'))
                $lead = Lead::findOrFail($request->get('leadId'));
            else
                $lead = Lead::where('phone', 'like', $request->get('number'))->orderBy('id', 'desc')->firstOrFail();
            $agentTwilioNumber = $agentTwilioNumber ?: $lead->company['twilio_mobile_number'];

            if ($lead) {
                $recordingStatus = action([TwilioController::class, 'recording'], ['leadId' => $lead->id]);
                $dial = $response->dial('', [
                    'callerId' =>  $agentTwilioNumber,
                    'record' => 'record-from-ringing-dual',
                    'recordingStatusCallbackMethod' => 'POST',
                    'recordingStatusCallbackEvent' => 'completed',
                    'recordingStatusCallback' => $recordingStatus,
                ]);
                $dial->number($request->get('number'));
            }
        } // Blind Call Agent call Lead via automation
        else if ($request->get('leadId', '')) {
            $lead = Lead::findOrFail($request->get('leadId', ''));
            $response->say(
                "You have a new lead {$lead->fullname}, lead source {$lead['campaign']['deal']['name']}",
                ['voice' => 'Man']
            );
            $response->dial($lead->phone);
        }
        // Lead call agent
        else {
            $lead = Lead::query()->where('phone', 'like', $from)->orderBy('id', 'desc')->firstOrFail();
            $recordingStatus = action([TwilioController::class, 'recording'], ['leadId' => $lead->id]);
            $dial = $response->dial('', [
                'record' => 'record-from-ringing-dual',
                'recordingStatusCallbackMethod' => 'POST',
                'recordingStatusCallbackEvent' => 'completed',
                'recordingStatusCallback' => $recordingStatus,
            ]);
            $agentPhone = ($agent->twilio_mobile_number ? $agent->phone : $lead->company['phone']);

            $dial->number($agentPhone);
        }

        return \response($response, 200, ['Content-Type' => 'text/xml']);
    }

    public function recording(Request $request, $leadId)
    {
        $recordingUrl = $request->get('RecordingUrl');
        $lead = Lead::findOrFail($leadId);

        LeadNote::create([
            'lead_status_id' => $lead->lead_status_id,
            'lead_id' => $lead->id,
            'agent_id' => $lead->agent_id,
            'message' => "Call recording!",
            'recordingUrl' => $recordingUrl,
        ]);
    }

    public function token($leadId)
    {
        $lead = Lead::findOrFail($leadId);
        $twilioSid = $lead->company['twilio_sid'];
        $twilioToken = $lead->company['twilio_token'];
        $appSid = $lead->company['twilio_app_sid'];

        if (!$twilioSid || !$twilioToken || !$appSid) {
            return;
        }

        $clientCapability = new ClientToken(
            $twilioSid,
            $twilioToken
        );
        $clientCapability->allowClientOutgoing($appSid, [
            'leadId' => $leadId,
        ]);

        return response()->json([
            'token' => $clientCapability->generateToken(),
        ]);
    }

    public function sendSMS(Request $request, $leadId)
    {
        $this->validate($request, [
            'message' => 'required'
        ]);
        try {
            $message = $request->message;
            $lead = Lead::findOrFail($leadId);
            $twilioSid = $lead->company['twilio_sid'];
            $twilioToken = $lead->company['twilio_token'];

            if (empty(trim($twilioSid)) || empty(trim($twilioToken))) {
                throw new \Exception('Missing required twilio api credentials');
            }
            $fromNumber = ($lead->company['twilio_mobile_number']);
            // \Log::critical('===============================================to=>' . $lead->phone . " :from=>" . $fromNumber . ' message=' . $message);


            $twilioClient = new Client($twilioSid, $twilioToken);
            $twilioClient->messages->create(
                $lead->phone,
                [
                    'from' => $lead->company['twilio_mobile_number'],
                    'body' => $message . "\n\nMessage sent from " . $lead->company['twilio_mobile_number']
                ]
            );


            $msg = "<div class='sms-message'>" . substr($message, 0, 70) . "...</div>";

            LeadNote::create([
                'lead_status_id' => $lead->lead_status_id,
                'lead_id' => $lead->id,
                'agent_id' => $lead->agent_id,
                'message' => "sent SMS.<br/>" . $msg,
            ]);
            echo "Message sent successfully";
        } catch (\Exception $exception) {
            \Log::critical("sendSMS:{$leadId},message:{$message}, {$exception->getMessage()}");
            echo $exception->getMessage();
        }
        return;
    }
}
