<?php

namespace App\Console\Commands;

use App\Models\DealAction;
use App\Models\Lead;
use App\Models\LeadNote;
use App\Services\MailService;
use Illuminate\Console\Command;

class ActionSendToLeadEmailNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:email-notification {leadId} {dealActionId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Email notification to Lead';

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
            /** @var Lead $lead */
            $lead = Lead::where('id', $leadId)->first();
            $dealAction = DealAction::findOrFail($dealActionId);

            $agent = $lead->agent()->first();
            $agentEmail = $agent->email;
            $agentName = $agent->name;

            LeadNote::create([
                'lead_status_id' => $lead->lead_status_id,
                'lead_id' => $lead->id,
                'agent_id' => $lead->agent_id,
                'deal_action_id' => $dealActionId,
                'message' => "Email message sent <div class='automatic-email'>" . $dealAction->object->subject . "</div>",
            ]);

            MailService::sendMail(
                'emails.lead-notification',
                [
                    'from_address' => $agentEmail,
                    'from_address_name' => $agentName,
                    'body' => $dealAction->object->message,
                    'leadId' => $lead->id,
                    'dealActionId' => $dealAction->id,
                ],
                $lead->email,
                $dealAction->object->subject
            );
        } catch (\Exception $exception) {
            \Log::critical("ActionSendToLeadEmailNotification:lead:{$leadId},dealAction:{$dealActionId}, {$exception->getMessage()}");
        }
    }
}
