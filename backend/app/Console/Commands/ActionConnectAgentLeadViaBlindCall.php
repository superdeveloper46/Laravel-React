<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\TwilioController;
use App\Models\DealAction;
use App\Models\Lead;
use App\Models\LeadNote;
use Illuminate\Console\Command;
use Twilio\Rest\Client;

class ActionConnectAgentLeadViaBlindCall extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:blind-call {leadId} {dealActionId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Agent Lead Blind call';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $leadId = $this->argument('leadId');
        $dealActionId = $this->argument('dealActionId');

        try {
            $lead = Lead::findOrFail($leadId);
            $twilioSid = $lead->company['twilio_sid'];
            $twilioToken = $lead->company['twilio_token'];

            if (empty(trim($twilioSid)) || empty(trim($twilioToken))) {
                throw new \Exception('Missing required twilio api credentials');
            }

            $twilioClient = new Client($twilioSid, $twilioToken);
            $fromNumber = ($lead->agent['twilio_mobile_number'] ? $lead->agent['twilio_mobile_number'] : $lead->company['twilio_mobile_number']);

            $callLead = action([TwilioController::class, 'conference'], [
                'companyId' => $lead->company['id'],
                'agentId'   => $lead->agent_id,
                'leadId'    => $lead->id
            ]);
            $recordingStatus = action([TwilioController::class, 'recording'], ['leadId' => $lead->id]);

            $twilioClient->calls
                ->create($lead->agent['phone'] ?: $lead->company['phone'],
                    $fromNumber,
                    [
                        "url" => $callLead,
                        'record' => true,
                        'recordingStatusCallback' => $recordingStatus,
                        "recordingStatusCallbackEvent" => ["completed"],
                        "recordingChannels" => "dual"
                    ]
                );

            LeadNote::create([
                'lead_status_id' => $lead->lead_status_id,
                'lead_id' => $lead->id,
                'agent_id' => $lead->agent_id,
                'deal_action_id' => $dealActionId,
                'message' => "<div class='automatic-call'>Automatic Blind call created!</div>",
            ]);
        } catch (\Exception $exception) {
            \Log::critical("ActionConnectAgentLeadViaBlindCall:lead:{$leadId},dealAction:{$dealActionId}, line: {$exception->getLine()} - {$exception->getMessage()}");
        }
    }
}
