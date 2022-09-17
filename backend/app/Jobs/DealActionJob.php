<?php

namespace App\Jobs;

use App\Models\DealAction;
use App\Models\Lead;
use App\Models\LeadActionHistory;
use App\Models\LeadStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;

class DealActionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $leadActionHistory;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(LeadActionHistory $leadActionHistory)
    {
        $this->leadActionHistory = $leadActionHistory;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $leadActionHistory = LeadActionHistory::findOrFail($this->leadActionHistory->id);
            if ($leadActionHistory->is_completed) {
                Log::info("Lead action already executed lead={$this->leadActionHistory['lead_id']}, dealAction={$this->leadActionHistory['deal_action_id']}");
                return;
            }

            $dealAction = DealAction::findOrFail($this->leadActionHistory['deal_action_id']);
            $lead = Lead::findOrFail($this->leadActionHistory['lead_id']);

            if ($this->isContacted($lead->status) && $dealAction->stop_on_manual_contact) {
                Log::info("Stop on manual contact lead automation lead={$this->leadActionHistory['lead_id']}, dealAction={$this->leadActionHistory['deal_action_id']}");
                return;
            }

            try {
                $this->executeCommand($dealAction, $lead);
            } catch (\Exception $exception) {
                Log::critical("{$exception->getMessage()}");
            }
            $leadActionHistory->moveToCompleted();
            $dealAction->scheduleNextLeadAction($lead);

        } catch (\Exception $exception) {
            Log::critical("{$exception->getMessage()} : line=" . $exception->getLine());
        }
    }


    public function isContacted($status) {
        return (
            $status === LeadStatus::$STATUS_CONTACTED_CALL ||
            $status === LeadStatus::$STATUS_CONTACTED_SMS ||
            $status === LeadStatus::$STATUS_CONTACTED_EMAIL
        );
    }

    public function executeCommand(DealAction $dealAction, Lead $lead) {
        switch ($dealAction->type) {
            case DealAction::TYPE_EMAIL_MESSAGE: {
                \Artisan::call("send:email-notification", [
                    'leadId' => $lead->id,
                    'dealActionId' => $dealAction->id,
                ]);
                break;
            }
            case DealAction::TYPE_SMS_MESSAGE: {
                \Artisan::call("send:sms-notification",  [
                    'leadId' => $lead->id,
                    'dealActionId' => $dealAction->id,
                ]);
                break;
            }
            case DealAction::TYPE_CHANGE_STATUS: {
                \Artisan::call('change:lead-status', [
                    'leadId' => $lead->id,
                    'dealActionId' => $dealAction->id,
                ]);
                break;
            }
            case DealAction::TYPE_BLIND_CALL: {
                \Artisan::call('create:blind-call', [
                    'leadId' => $lead->id,
                    'dealActionId' => $dealAction->id,
                ]);
                break;
            }
            case DealAction::TYPE_PUSH_NOTIFICATION: {
                \Artisan::call('send:device-notification', [
                    'leadId' => $lead->id,
                    'dealActionId' => $dealAction->id,
                ]);
                break;
            }
            default:
        }
    }
}
