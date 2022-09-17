<?php

namespace App\Http\Controllers\Api\Management\Company;

use App\Models\Deal;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class DealController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $itemsPerPage = (int)$request->get('per_page', 100);
        $page = (int)$request->get('current_page', 1);
        return $request->user()->getCompanyDeals($request)->paginate($itemsPerPage, ['*'], 'deals', $page);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getDealsStatistic(Request $request) {
        return $request->user()->getDealsStatistics($request);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Deal $deal)
    {
        $this->validate($request, [
            'name' => 'required|string|max:255',
            'agency_company_id' => 'required|int'
        ]);

        $deal->fill($request->only(['name', 'description', 'timezone', 'agency_company_id', 'has_automation']));
        $deal->save();
    
        return $deal;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        return $request->user()->getDealBy($id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name' => 'required|string|max:255',
            'agency_company_id' => 'required|int',
        ]);
        $deal = $request->user()->getDealBy($id);
    
        $deal->fill($request->only(['name', 'timezone', 'description', 'has_automation']));
        $deal->save();
    
        return $deal;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        $deal = $request->user()->getDealBy($id);
        $deal->delete();
        return $deal;
    }
}
