<?php

namespace App\Http\Controllers\Api\Management\Company;

use App\Models\Deal;
use App\Models\DealCampaign;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Faker\Generator as Faker;

class CampaignController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $deal)
    {
        $itemsPerPage = (int)$request->get('per_page', 10);
        $page = (int)$request->get('current_page', 1);
    
        return $request
            ->user()
            ->getDealBy($deal)
            ->getCampaignsBy($request->only([
                'showDeleted',
                'name',
                'type',
                'leads',
                'avg_time_response',
            ]))
            ->paginate($itemsPerPage, ['*'], 'campaigns', $page);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $deal, Faker $faker)
    {
        $request->merge([
            'uuid' => $faker->uuid,
            'deal_id' => $deal,
            'agency_company_id' => $request->user()->getDealBy($deal)->agency_company_id,
        ]);
    
        return DealCampaign::createCampaign($request);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $deal, $id)
    {
        return $request->user()->getDealBy($deal)->getCampaignBy($id);
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
        $campaign = $request->user()->getDealBy($deal)->getCampaignBy($id);
        $campaign->updateCampaign($request);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $deal, $id)
    {
        $campaign = $request->user()->getDealBy($deal)->getCampaignBy($id);
        $campaign->delete();
        return $campaign;
    }
}
