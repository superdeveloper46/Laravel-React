<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;

class LeadNote extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'lead_status_id',
        'lead_id',
        'agent_id',
        'message',
        'deal_action_id',
        'recordingUrl',
    ];

    protected $appends = ['agent', 'status'];
    
    public function agent() {
        return $this->hasOne('App\Models\Agent', 'id', 'agent_id');
    }

    public function status() {
        return $this->hasOne('App\Models\LeadStatus', 'id', 'lead_status_id');
    }
    
    public function lead() {
        return $this->hasOne('App\Models\Lead', 'id', 'lead_id');
    }

    public function dealAction() {
        return $this->hasOne('App\Models\DealAction', 'id', 'deal_action_id');
    }
    
    public function getAgentAttribute() {
        return $this->agent()->first();
    }
 
    public function getStatusAttribute() {
        return $this->status()->first();
    }
    
    public static function createLeadNote(Request $request, Lead $lead) {
        try {
            \DB::beginTransaction();
            $leadStatus = LeadStatus::where('type', $request->get('status'))->firstOrFail();
    
            $request->merge([
                'agent_id' => $request->user()->id,
                'lead_status_id' => $leadStatus->id,
                'lead_id' => $lead->id
            ]);
    
            \Validator::validate($request->all(), [
                'agent_id' => 'required|int',
                'lead_status_id' => 'required|int',
                'lead_id' => 'required|int',
                'message' => 'required|string|max:240',
                'status' => 'required|string',
            ]);
        
            $lead->lead_status_id = $leadStatus->id;
            
            $lead->save();
        
            $leadNote = new LeadNote();
            $leadNote->fill($request->only([
                'agent_id',
                'lead_status_id',
                'lead_id',
                'message',
            ]));
            // $leadNote->is_status_event = 1;
            $leadNote->save(); 
            \DB::commit();

            return $leadNote;
        } catch (\Exception $exception) {
            \DB::rollBack();
            throw $exception;
        }
    }
    
    public function updateLeadNote(Request $request) {
        \Validator::validate($request->all(), [
            'message' => 'required|string|max:240',
        ]);
        
        $this->fill($request->only([
            'message',
        ]));
    
        $this->save();
    
        return $this;
    }
}
