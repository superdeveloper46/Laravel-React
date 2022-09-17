<?php

namespace App\Console\Commands;

use App\Models\DealAction;
use App\Models\Lead;
use App\Models\LeadNote;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Twilio\Rest\Client;

class ActionSendToLeadSMSNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:sms-notification {leadId} {dealActionId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send SMS notification to Lead';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    protected function getArguments()
    {
        return [
            ['leadId', InputArgument::REQUIRED, 'Lead Id'],
            ['dealActionId', InputArgument::REQUIRED, 'Deal Action Id'],
        ];
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
            $dealAction = DealAction::findOrFail($dealActionId);
            $twilioSid = $lead->company['twilio_sid'];
            $twilioToken = $lead->company['twilio_token'];

            if (empty(trim($twilioSid)) || empty(trim($twilioToken))) {
                throw new \Exception('Missing required twilio api credentials');
            }

            $twilioClient = new Client($twilioSid, $twilioToken);
            $twilioClient->messages->create(
                $lead->phone,
                [
                    'from' => $lead->company['twilio_mobile_number'],
                    'body' => $dealAction->object->message
                ]
            );

            LeadNote::create([
                'lead_status_id' => $lead->lead_status_id,
                'lead_id' => $lead->id,
                'agent_id' => $lead->agent_id,
                'deal_action_id' => $dealActionId,
                'message' => "Automatic SMS notification sent messages:<br/><div class='automatic-sms'>" . $dealAction->object->message . "</div>",
            ]);
        } catch (\Exception $exception) {
            \Log::critical("ActionSendToLeadSMSNotification:lead:{$leadId},dealAction:{$dealActionId}, {$exception->getMessage()}");
        }
    }
}
