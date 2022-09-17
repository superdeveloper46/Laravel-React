<?php

namespace App\Http\Controllers\Api\Management\Agency;

use App\Models\Deal;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class DealController extends Controller
{
    public function all(Request $request) {
        $itemsPerPage = (int)$request->get('per_page', 100);
        $page = (int)$request->get('current_page', 1);
        return $request->user()->getCompanyDeals($request)
                ->paginate($itemsPerPage, ['*'], 'deals', $page);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getDealsStatistic(Request $request) {
        return $request->user()->getDealsStatistics($request);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $company)
    {
        return $request->user()->getCompanyBy($company)->deals()->paginate(100);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return Deal
     */
    public function store(Request $request, $company, Deal $deal)
    {
        $this->validate($request, [
           'name' => 'required|string|max:255'
        ]);

        $agencyCompanyId = $request->user()->getCompanyBy($company)->pivot->id;
        $request->merge(['agency_company_id' => $agencyCompanyId ]);
        $deal->fill($request->only(['name', 'description', 'timezone', 'agency_company_id']));
        $deal->save();

        return $deal;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $company, $id)
    {
        return $request->user()->getCompanyBy($company)->getDealBy($id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $company, $id)
    {
        $this->validate($request, [
            'name' => 'required|string|max:255'
        ]);
        $company = $request->user()->getCompanyBy($company);
        $request->merge(['agency_company_id' => $company->pivot->id]);
        $deal = Deal::find($id);
        $deal->fill($request->only(['name', 'timezone', 'agency_company_id', 'description']));
        $deal->save();
    
        return $deal;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $company, $id)
    {
        $deal = $request->user()->getCompanyBy($company)->getDealBy($id);
        $deal->delete();
        return $deal;
    }
}
