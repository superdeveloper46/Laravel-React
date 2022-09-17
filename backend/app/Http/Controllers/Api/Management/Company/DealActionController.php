<?php

namespace App\Http\Controllers\Api\Management\Company;

use App\Models\DealAction;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class DealActionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $deal)
    {
        return $request->user()->getDealBy($deal)->actions;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $deal, DealAction $dealAction)
    {
        $request->merge([
            'deal_id' => $deal,
            'object' => json_encode($request->json('object')),
        ]);
        $dealAction->fill($request->only([
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
        ]));

        $dealAction->save();

        return $dealAction;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $deal, $id)
    {
        return $request->user()->getDealBy($deal)->getActionBy($id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $deal, $id)
    {
        $request->merge([
            'object' => json_encode($request->json('object')),
        ]);

        $dealAction = $request->user()->getDealBy($deal)->getActionBy($id);
        $dealAction->fill($request->only([
            'parent_id',
            'type',
            'lead_reply_type',
            'lead_reply_contains',
            'is_root',
            'object',
            'delay_time',
            'delay_type',
            'stop_on_manual_contact',
        ]));
        $dealAction->save();
        return $dealAction;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $deal, $id)
    {
        try {
            \DB::beginTransaction();
            $dealAction = $request->user()->getDealBy($deal)->getActionBy($id);

            $request->user()->getDealBy($deal)->updateChildrenParentIdBy($id);

            $dealAction->delete();

            \DB::commit();
        } catch (\Exception $exception) {
            \DB::rollBack();
            abort(400, $exception->getMessage());
        }
    }
}
