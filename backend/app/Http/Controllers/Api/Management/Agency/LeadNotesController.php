<?php

namespace App\Http\Controllers\Api\Management\Agency;

use App\Models\LeadNote;
use App\Models\LeadStatus;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Mockery\Exception;

class LeadNotesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $company, $lead)
    {
        return $request->user()->getCompanyBy($company)->getLeadBy($lead)->leadNotes()->get();
    }
    
    /**
     * @param Request $request
     * @param $company
     * @param $lead
     * @return LeadNote
     * @throws \Exception
     */
    public function store(Request $request, $company, $lead)
    {
        $lead = $request->user()->getCompanyBy($company)->getLeadBy($lead);
        return LeadNote::createLeadNote($request, $lead);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $company, $lead, $id)
    {
        return $request
            ->user()
            ->getCompanyBy($company)
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
    public function destroy(Request $request, $company, $lead, $id)
    {
        $leadNote = $request->user()->getCompanyBy($company)->getLeadBy($lead)->getLeadNoteBy($id);
        $leadNote->delete();
        return $leadNote;
    }
}
