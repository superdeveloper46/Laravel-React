<?php

namespace App\Http\Controllers\Api\Management\Agent;

use App\Models\Reminder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Log;

class ReminderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param $leadId
     * @return Reminder
     */
    public function store(Request $request, $leadId)
    {
        Log::info('=============: ', array('$lead' => $request->time));
        $this->validate($request, [
            'name' => 'required|string|max:255',
            'time' => 'required|string|max:50'
        ]);
        $reminder = new Reminder();
        $lead = $request->user()->getLeadBy($leadId);
        $request->merge([
            'lead_id' => $lead->id,
            'agent_id' => $request->user()->id,
        ]);
        $reminder->fill($request->only(['name', 'lead_id', 'agent_id']));
        $reminder->fill([
            'time' => Carbon::parse($request->time)->format('Y-m-d H:i')
        ]);
        $reminder->save();
        return $reminder;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param $leadId
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $leadId, $id)
    {
        $this->validate($request, [
            'name' => 'required|string|max:255',
            'time' => 'required|string|max:50'
        ]);
        $reminder = Reminder::find($id);
        $lead = $request->user()->getLeadBy($leadId);
        $request->merge([
            'lead_id' => $lead->id,
            'agent_id' => $request->user()->id,
        ]);
        $reminder->fill($request->only(['name', 'lead_id', 'agent_id']));
        $reminder->fill([
            'time' => Carbon::parse($request->time)->format('Y-m-d H:i')
        ]);
        $reminder->save();
        return $reminder;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $leadId
     * @param $id
     * @return void
     */
    public function destroy($leadId, $id)
    {
        $reminder = Reminder::find($id);
        $reminder->delete();
        return $reminder;
    }
}
