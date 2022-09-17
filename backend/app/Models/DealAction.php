<?php
namespace App\Models;

use App\Jobs\DealActionJob;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Optional;

class DealAction extends Model {
    use SoftDeletes;

    public const TYPE_NONE = 'NONE';
    public const TYPE_SMS_MESSAGE = 'SMS';
    public const TYPE_EMAIL_MESSAGE = 'EMAIL';
    public const TYPE_PUSH_NOTIFICATION  = 'PUSH_NOTIFICATION';
    public const TYPE_BLIND_CALL = 'BLIND_CALL';
    public const TYPE_CHANGE_STATUS = 'CHANGE_STATUS';

    public const LEAD_REPLY_TYPE_NONE = 'NONE';
    public const LEAD_REPLY_TYPE_SMS_REPLY = 'SMS_REPLY';
    public const LEAD_REPLY_TYPE_SMS_REPLY_CONTAIN = 'SMS_REPLY_CONTAIN';
    public const LEAD_REPLY_TYPE_MAIL_OPEN = 'MAIL_OPEN';

    protected $fillable = [
        'parent_id',
        'deal_id',
        'type',
        'lead_reply_type',
        'lead_reply_contains',
        'is_root',
        'object',
        'delay_time',
        'delay_type',
        'stop_on_manual_contact',
    ];

    protected $appends = [
        'rootParent'
    ];

    public function getObjectAttribute($object)
    {
        return $object ? json_decode($object) : json_decode('{}');
    }

    public function deal() {
        return $this->hasOne('App\Models\Deal', 'id', 'deal_id');
    }

    public function getPreviousAction() {
        return $this->newQuery()->where('id', $this->parent_id)->first();
    }

    public function getNextHorizontalAction() {
        return $this->newQuery()
            ->where('parent_id', $this->id)
            ->where('is_root', 0)
            ->first();
    }

    public function getNextVerticalAction() {
        return $this->newQuery()
            ->where('parent_id', $this->id)
            ->where('is_root', 1)
            ->first();
    }

    public function scheduleNextLeadAction(Lead $lead) {
        $nextActionHorizontal = $this->getNextHorizontalAction();
        $nextActionVertical = $this->getNextVerticalAction();
        /** @var LeadActionHistory|Optional $actionHistory */
        $actionHistory = optional(LeadActionHistory::query()
            ->where('lead_id', $lead->id)
            ->where('deal_action_id', $this->id)
            ->first());

        if ($nextActionVertical) {
            $nextRootVerticalAction = LeadActionHistory::query()
                ->where('lead_id', $lead->id)
                ->where('deal_action_id', $nextActionVertical->id)
                ->first();

            if ($nextRootVerticalAction) {
                $nextRootVerticalAction->moveToCompleted();
            } else {
                // have added a buffer time of 20 seconds to make sure the action is completed in case of system delay
                $bufferTime = 20;

                if (empty($actionHistory->created_at) || now()->subSeconds($nextActionVertical->delay_time + $bufferTime)->lessThanOrEqualTo($actionHistory->created_at)) {
                    \Log::info('Next-vertical=>'.$nextActionVertical->id);
                    return $nextActionVertical->scheduleLeadAction($lead);
                }
            }
        }

        if ($nextActionHorizontal) {
            // have added a buffer time of 20 seconds to make sure the action is completed in case of system delay
            $bufferTime = 20;

            if (empty($actionHistory->created_at) || now()->subSeconds($nextActionHorizontal->delay_time + $bufferTime)->lessThanOrEqualTo($actionHistory->created_at)) {
                \Log::info('Next-horizontal=>' . $nextActionHorizontal->id);

                return $nextActionHorizontal->scheduleLeadAction($lead);
            }
        }

        \Log::info('None for parent=>' . $this->id);
    }


    public function scheduleLeadAction(Lead $lead) {
        $leadActionHistory = LeadActionHistory::query()
            ->where('lead_id', $lead->id)
            ->where('deal_action_id', $this->id)
            ->where('is_completed', 1)->first();

        if (!$leadActionHistory) {
            $leadActionHistory = new LeadActionHistory();

            $leadActionHistory->fill([
                'lead_id' => $lead->id,
                'deal_action_id' => $this->id,
                'is_completed' => 0,
            ]);
            $leadActionHistory->save();
        }

        $minutes = ceil($this->delay_time/60);
        DealActionJob::dispatch($leadActionHistory)
            ->delay(
                now()->addMinutes($minutes)
            )
            ->onQueue('actions');
    }

    public function getRootParentAttribute() {
        if ($this->parent_id && !$this->is_root) {
            return DealAction::query()
                ->where('id', $this->parent_id)
                ->where('is_root', 1)
                ->first();
        }
        return false;
    }
}
