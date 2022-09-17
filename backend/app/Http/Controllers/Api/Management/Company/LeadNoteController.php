<?php

namespace App\Http\Controllers\Api\Management\Company;

use App\Models\LeadNote;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class LeadNoteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $lead)
    {
        return $request->user()->getLeadBy($lead)->leadNotes()->get();
    }
    
    /**
     * @param Request $request
     * @param $lead
     * @return LeadNote
     * @throws \Exception
     */
    public function store(Request $request, $lead)
    {
        $lead = $request->user()->getLeadBy($lead);
        return LeadNote::createLeadNote($request, $lead);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $lead, $id)
    {
        return $request
            ->user()
            ->getLeadBy($lead)
            ->getLeadNoteBy($id)
            ->updateLeadNote($request);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $lead, $id)
    {
        $leadNote = $request->user()->getLeadBy($lead)->getLeadNoteBy($id);
        $leadNote->delete();
        return $leadNote;
    }
}
